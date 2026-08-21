<?php

namespace skeeks\cms\services;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsUser;
use yii\helpers\Html;

/**
 * Finds existing CRM identities for a lead and links an explicitly selected one.
 */
class CmsLeadIdentityService
{
    public function findMatches(CmsLead $lead, int $limit = 5): array
    {
        $limit = max(1, min($limit, 10));
        $clientReasons = [];
        $companyReasons = [];

        if (!$lead->cms_user_id) {
            foreach ($lead->phones as $phone) {
                $this->collectIds(
                    $this->clientQuery()->phone($phone->value)
                        ->select(CmsUser::tableName().'.id')->limit($limit)->column(),
                    $clientReasons,
                    'Совпал телефон '.$phone->value
                );
            }
            foreach ($lead->emails as $email) {
                $this->collectIds(
                    $this->clientQuery()->email($email->value)
                        ->select(CmsUser::tableName().'.id')->limit($limit)->column(),
                    $clientReasons,
                    'Совпал email '.$email->value
                );
            }
        } elseif (!$lead->cms_company_id) {
            $this->collectIds([(int)$lead->cms_user_id], $clientReasons, 'Клиент уже привязан');
        }

        if (!$lead->cms_company_id) {
            foreach ($lead->phones as $phone) {
                $this->collectIds(
                    CmsCompany::find()->forManager()->phone($phone->value)
                        ->select(CmsCompany::tableName().'.id')->limit($limit)->column(),
                    $companyReasons,
                    'Совпал телефон '.$phone->value
                );
            }
            foreach ($lead->emails as $email) {
                $emailCompanyIds = CmsCompanyEmail::find()
                    ->select('cms_company_id')
                    ->andWhere(['value' => mb_strtolower(trim($email->value), 'UTF-8')])
                    ->limit($limit)
                    ->column();
                $allowedCompanyIds = $emailCompanyIds
                    ? CmsCompany::find()->forManager()->select(CmsCompany::tableName().'.id')
                        ->andWhere([CmsCompany::tableName().'.id' => $emailCompanyIds])->column()
                    : [];
                $this->collectIds($allowedCompanyIds, $companyReasons, 'Совпал email '.$email->value);
            }
        }

        $clients = $this->loadClients($clientReasons, $limit);
        $companiesByClient = $this->loadCompaniesByClient(array_keys($clients), $limit);
        $clientMatches = [];
        foreach ($clients as $id => $client) {
            $clientMatches[] = [
                'model' => $client,
                'reasons' => array_values($clientReasons[$id]),
                'companies' => $companiesByClient[$id] ?? [],
            ];
        }

        $relatedCompanyIds = [];
        foreach ($companiesByClient as $companies) {
            foreach ($companies as $company) {
                $relatedCompanyIds[(int)$company->id] = true;
            }
        }

        $companyMatches = [];
        foreach ($this->loadCompanies($companyReasons, $limit) as $id => $company) {
            if (isset($relatedCompanyIds[(int)$id])) {
                continue;
            }
            $companyMatches[] = [
                'model' => $company,
                'reasons' => array_values($companyReasons[$id]),
            ];
        }

        return ['clients' => $clientMatches, 'companies' => $companyMatches];
    }

    public function linkExisting(CmsLead $lead, ?int $companyId, ?int $clientId): bool
    {
        $companyId = $companyId ?: null;
        $clientId = $clientId ?: null;
        if (!$companyId && !$clientId) {
            throw new \DomainException('Не выбрана компания или клиент для привязки.');
        }
        if ($lead->status !== CmsLead::STATUS_IN_WORK || $lead->isTerminal) {
            throw new \DomainException('Привязывать записи можно только к лиду в работе.');
        }
        if ($companyId && $lead->cms_company_id && (int)$lead->cms_company_id !== $companyId) {
            throw new \DomainException('К лиду уже привязана другая компания.');
        }
        if ($clientId && $lead->cms_user_id && (int)$lead->cms_user_id !== $clientId) {
            throw new \DomainException('К лиду уже привязан другой клиент.');
        }

        $company = $companyId
            ? CmsCompany::find()->forManager()->andWhere([CmsCompany::tableName().'.id' => $companyId])->one()
            : null;
        $client = $clientId
            ? $this->clientQuery()->andWhere([CmsUser::tableName().'.id' => $clientId])->one()
            : null;
        if ($companyId && !$company) {
            throw new \DomainException('Компания не найдена или недоступна.');
        }
        if ($clientId && !$client) {
            throw new \DomainException('Клиент не найден или недоступен.');
        }
        if ($company && $client && !CmsCompany2user::find()->andWhere([
            'cms_company_id' => $company->id,
            'cms_user_id' => $client->id,
        ])->exists()) {
            throw new \DomainException('Выбранный клиент не связан с этой компанией.');
        }

        $changed = (!$lead->cms_company_id && $company) || (!$lead->cms_user_id && $client);
        if (!$changed) {
            return false;
        }

        $transaction = CmsLead::getDb()->beginTransaction();
        try {
            if (!$lead->cms_company_id && $company) {
                $lead->cms_company_id = (int)$company->id;
            }
            if (!$lead->cms_user_id && $client) {
                $lead->cms_user_id = (int)$client->id;
            }
            if (!$lead->save()) {
                throw new \RuntimeException(implode('; ', $lead->getFirstErrors()));
            }

            $parts = [];
            if ($company) {
                $parts[] = 'компания «'.Html::encode($company->asText).'»';
            }
            if ($client) {
                $parts[] = 'клиент «'.Html::encode($client->shortDisplayName).'»';
            }
            $log = new CmsLog([
                'log_type' => CmsLog::LOG_TYPE_COMMENT,
                'model_code' => $lead->skeeksModelCode,
                'model_id' => (int)$lead->id,
                'model_as_text' => $lead->asText,
                'cms_company_id' => $company ? (int)$company->id : null,
                'cms_user_id' => $client ? (int)$client->id : null,
                'comment' => 'Привязаны существующие записи CRM: '.implode(', ', $parts).'.',
                'data' => ['action' => 'lead_identity_link'],
            ]);
            if (!$log->save()) {
                throw new \RuntimeException(implode('; ', $log->getFirstErrors()));
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    private function clientQuery()
    {
        return CmsUser::find()
            ->cmsSite()
            ->active()
            ->forManager()
            ->andWhere([CmsUser::tableName().'.is_worker' => 0]);
    }

    private function loadClients(array $reasons, int $limit): array
    {
        $ids = array_slice(array_keys($reasons), 0, $limit);
        if (!$ids) {
            return [];
        }
        return $this->clientQuery()
            ->andWhere([CmsUser::tableName().'.id' => $ids])
            ->with(['cmsUserPhones', 'cmsUserEmails'])
            ->indexBy('id')
            ->all();
    }

    private function loadCompanies(array $reasons, int $limit): array
    {
        $ids = array_slice(array_keys($reasons), 0, $limit);
        if (!$ids) {
            return [];
        }
        return CmsCompany::find()->forManager()
            ->andWhere([CmsCompany::tableName().'.id' => $ids])
            ->with(['phones', 'emails'])
            ->indexBy('id')
            ->all();
    }

    private function loadCompaniesByClient(array $clientIds, int $limit): array
    {
        if (!$clientIds) {
            return [];
        }
        $links = CmsCompany2user::find()
            ->andWhere(['cms_user_id' => $clientIds])
            ->orderBy(['is_root' => SORT_DESC, 'sort' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
        $companyIds = array_values(array_unique(array_map(static fn(CmsCompany2user $link) => (int)$link->cms_company_id, $links)));
        $companies = $companyIds
            ? CmsCompany::find()->forManager()
                ->andWhere([CmsCompany::tableName().'.id' => $companyIds])
                ->with(['phones', 'emails'])
                ->indexBy('id')
                ->all()
            : [];

        $result = [];
        foreach ($links as $link) {
            $clientId = (int)$link->cms_user_id;
            $companyId = (int)$link->cms_company_id;
            if (!isset($companies[$companyId]) || count($result[$clientId] ?? []) >= $limit) {
                continue;
            }
            $result[$clientId][] = $companies[$companyId];
        }
        return $result;
    }

    private function collectIds(array $ids, array &$target, string $reason): void
    {
        foreach ($ids as $id) {
            $id = (int)$id;
            if (!$id) {
                continue;
            }
            $target[$id][$reason] = $reason;
        }
    }

}

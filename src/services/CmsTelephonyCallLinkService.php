<?php

namespace skeeks\cms\services;

use DomainException;
use RuntimeException;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsUser;
use Yii;

/**
 * Atomically synchronizes canonical call links and their activity projections.
 */
class CmsTelephonyCallLinkService
{
    public function updateLinks(
        CmsTelephonyCall $call,
        ?int $leadId,
        ?int $companyId,
        ?int $userId
    ): void {
        $this->synchronize(
            $call,
            $this->resolveScopedEntity(CmsLead::class, $leadId, 'Лид'),
            $this->resolveScopedEntity(CmsCompany::class, $companyId, 'Компания'),
            $this->resolveScopedEntity(CmsUser::class, $userId, 'Клиент')
        );
    }

    public function availableLeadQuery()
    {
        return CmsLead::find()->forManager()->cmsSite();
    }

    public function availableCompanyQuery()
    {
        // Companies predate direct site ownership. Their site is derived from
        // the site-scoped users who created, manage, or belong to them.
        $siteUserIds = CmsUser::find()->cmsSite()->select(CmsUser::tableName().'.id');
        $managerCompanyIds = CmsCompany2manager::find()
            ->select('cms_company_id')
            ->andWhere(['cms_user_id' => clone $siteUserIds]);
        $clientCompanyIds = CmsCompany2user::find()
            ->select('cms_company_id')
            ->andWhere(['cms_user_id' => clone $siteUserIds]);

        return CmsCompany::find()
            ->forManager()
            ->andWhere(['or',
                [CmsCompany::tableName().'.created_by' => $siteUserIds],
                [CmsCompany::tableName().'.id' => $managerCompanyIds],
                [CmsCompany::tableName().'.id' => $clientCompanyIds],
            ]);
    }

    public function availableUserQuery()
    {
        return CmsUser::find()->forManager()->cmsSite();
    }

    /**
     * Used by the telephony ingestion flow after it has resolved and verified a lead.
     */
    public function attachLead(CmsTelephonyCall $call, CmsLead $lead): void
    {
        $this->synchronize($call, $lead, $call->company, $call->user);
    }

    private function synchronize(
        CmsTelephonyCall $call,
        ?CmsLead $lead,
        ?CmsCompany $company,
        ?CmsUser $user
    ): void {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand(
                'SELECT id FROM '.CmsTelephonyCall::tableName().' WHERE id = :id FOR UPDATE',
                [':id' => (int)$call->id]
            )->queryScalar();

            if (!$call->refresh()) {
                throw new RuntimeException('Телефонный звонок больше не существует.');
            }

            $oldTargets = [
                'cms_lead_id' => $call->lead,
                'cms_company_id' => $call->company,
                'cms_user_id' => $call->user,
            ];
            $newTargets = [
                'cms_lead_id' => $lead,
                'cms_company_id' => $company,
                'cms_user_id' => $user,
            ];

            foreach ($newTargets as $attribute => $target) {
                $call->{$attribute} = $target ? (int)$target->id : null;
            }
            if (!$call->save(false, array_keys($newTargets))) {
                throw new RuntimeException('Не удалось сохранить привязки телефонного звонка.');
            }

            foreach ($newTargets as $attribute => $newTarget) {
                $oldTarget = $oldTargets[$attribute];
                if ($oldTarget && (!$newTarget || (int)$oldTarget->id !== (int)$newTarget->id)) {
                    $this->deleteCallLogs($call, $oldTarget);
                }
                if ($newTarget) {
                    $this->ensureCallLog($call, $newTarget);
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
    }

    private function resolveScopedEntity(string $modelClass, ?int $id, string $label)
    {
        if (!$id) {
            return null;
        }

        if ($modelClass === CmsLead::class) {
            $query = $this->availableLeadQuery();
        } elseif ($modelClass === CmsCompany::class) {
            $query = $this->availableCompanyQuery();
        } else {
            $query = $this->availableUserQuery();
        }

        $model = $query
            ->andWhere([$modelClass::tableName().'.id' => $id])
            ->one();
        if (!$model) {
            throw new DomainException($label.' недоступен менеджеру на текущем сайте.');
        }

        return $model;
    }

    private function ensureCallLog(CmsTelephonyCall $call, $target): void
    {
        $matchingLogs = $this->findCallLogs($call, $target);
        if ($matchingLogs) {
            array_shift($matchingLogs);
            foreach ($matchingLogs as $duplicate) {
                if ($duplicate->delete() === false) {
                    throw new RuntimeException('Не удалось удалить дублирующий лог телефонного звонка.');
                }
            }
            return;
        }

        $log = new CmsLog();
        $log->model_code = $target->skeeksModelCode;
        $log->model_id = (int)$target->id;
        $log->model_as_text = isset($target->asText) ? (string)$target->asText : (string)$target->id;
        $log->log_type = CmsLog::LOG_TYPE_PHONE_CALL;
        $log->data = ['id' => (int)$call->id];
        $log->created_by = $call->cms_worker_user_id ?: null;
        $log->updated_by = $call->cms_worker_user_id ?: null;
        if (!$log->save()) {
            throw new RuntimeException('Не удалось сохранить лог телефонного звонка: '.json_encode(
                $log->errors,
                JSON_UNESCAPED_UNICODE
            ));
        }
    }

    private function deleteCallLogs(CmsTelephonyCall $call, $target): void
    {
        foreach ($this->findCallLogs($call, $target) as $log) {
            if ($log->delete() === false) {
                throw new RuntimeException('Не удалось удалить прежний лог телефонного звонка.');
            }
        }
    }

    /** @return CmsLog[] */
    private function findCallLogs(CmsTelephonyCall $call, $target): array
    {
        $result = [];
        $logs = CmsLog::find()
            ->andWhere([
                'model_code' => $target->skeeksModelCode,
                'model_id' => (int)$target->id,
                'log_type' => CmsLog::LOG_TYPE_PHONE_CALL,
            ])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        foreach ($logs as $log) {
            if ((int)($log->data['id'] ?? 0) === (int)$call->id) {
                $result[] = $log;
            }
        }

        return $result;
    }
}

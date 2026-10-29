<?php

namespace skeeks\cms\services;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadEmail;
use skeeks\cms\models\CmsLeadPhone;
use skeeks\cms\helpers\PhoneHelper;
use yii\base\InvalidArgumentException;
use yii\db\IntegrityException;

class CmsLeadService
{
    /** Attempts of the whole creation when MySQL rolls it back for a deadlock (1213) or a lock wait (1205). */
    public const LOCK_ATTEMPTS = 4;

    /**
     * A request of a source: a repeated event (source_type + source_ref) goes on in its deal, a new one is
     * created in one transaction with its contacts, the route of «Входящие заявки» and the notifications.
     * Requests at the same moment (the turn of a queue «по кругу») may meet in a deadlock: MySQL rolls the
     * whole transaction back, and the whole creation runs again from the start (the repeat check included),
     * a few times with a short random pause. Inside a transaction of the caller nothing is repeated: that
     * transaction is gone, its owner decides.
     */
    public function createFromSource(array $attributes): CmsLead
    {
        $phones = $this->normalizeContacts($attributes['phones'] ?? []);
        $emails = $this->normalizeContacts($attributes['emails'] ?? []);
        unset($attributes['phones'], $attributes['emails']);
        $attributes = $this->normalizeUtmAttributes($attributes);

        $outer = CmsLead::getDb()->getTransaction();
        $retry = !($outer && $outer->isActive);
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->createOnce($attributes, $phones, $emails);
            } catch (\yii\db\Exception $e) {
                if (!$retry || $attempt >= self::LOCK_ATTEMPTS || !self::isLockConflict($e)) {
                    throw $e;
                }
                \Yii::warning('Сделка из источника: конфликт блокировок, попытка '.($attempt + 1).': '.$e->getMessage(), self::class);
                usleep(random_int(20000, 80000) * $attempt);
            }
        }
    }

    /** MySQL rolled the statement or the transaction back for a deadlock or a lock wait timeout. */
    public static function isLockConflict(\yii\db\Exception $e): bool
    {
        $info = (array)$e->errorInfo;

        return in_array((int)($info[1] ?? 0), [1213, 1205], true) || ($info[0] ?? null) === '40001';
    }

    private function createOnce(array $attributes, array $phones, array $emails): CmsLead
    {
        $lead = new CmsLead();
        $lead->setAttributes($attributes);
        $lead->deferManagerNotifications = true;

        if ($lead->cms_site_id === null
            && \Yii::$app->has('skeeks')
            && \Yii::$app->skeeks->site
        ) {
            $lead->cms_site_id = (int)\Yii::$app->skeeks->site->id;
        }

        $sourceType = (string)$lead->source_type;
        $sourceRef = $lead->source_ref !== null ? trim((string)$lead->source_ref) : null;
        $siteId = $lead->cms_site_id !== null ? (int)$lead->cms_site_id : null;

        if ($sourceRef !== null && $sourceRef !== '') {
            $existing = CmsLead::find()->fromSource($sourceType, $sourceRef, $siteId)->one();
            if ($existing) {
                // A request added to another deal: the repeated event goes on there.
                $existing = $existing->resolveMerged();
                $this->syncContacts($existing, $phones, $emails);
                return $existing;
            }
        }

        $transaction = $this->beginTransactionIfNeeded();
        try {
            // «Входящие заявки» (п. 13б): the main responsible of a found client, or the next one of the queue;
            // inside the transaction, so the turn of the queue stays locked until the deal is saved.
            $route = \skeeks\cms\crm\InboundQueue::route($lead, $phones, $emails);
            if (!$lead->save()) {
                throw new InvalidArgumentException('Не удалось создать сделку: '.implode('; ', $lead->getFirstErrors()));
            }
            $this->saveContacts($lead, CmsLeadPhone::class, $phones);
            $this->saveContacts($lead, CmsLeadEmail::class, $emails);
            \skeeks\cms\crm\InboundQueue::afterRoute($lead, $route);
            if ($lead->status === CmsLead::STATUS_NEW && (!$lead->executor_id || $route === 'round')) {
                $lead->notifyAvailableManagers();
            }
            $lead->deferManagerNotifications = false;
            if ($sourceType === CmsLead::SOURCE_FORM) {
                // The lead lifecycle deliberately skips this source: only here
                // are the submitted contacts already part of the same
                // transaction, so the entry can name them.
                $lead->recordCreationActivity();
            }
            if ($transaction) {
                $transaction->commit();
            }
        } catch (IntegrityException $e) {
            if ($transaction && $transaction->isActive) {
                $transaction->rollBack();
            }
            if ($sourceRef !== null && $sourceRef !== '') {
                $existing = CmsLead::find()->fromSource($sourceType, $sourceRef, $siteId)->one();
                if ($existing) {
                    $existing = $existing->resolveMerged();
                    $this->syncContacts($existing, $phones, $emails);
                    return $existing;
                }
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($transaction && $transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }

        return $lead;
    }

    public function syncContacts(CmsLead $lead, array $phones, array $emails): void
    {
        $transaction = $this->beginTransactionIfNeeded();
        try {
            $this->saveContacts($lead, CmsLeadPhone::class, $this->normalizeContacts($phones));
            $this->saveContacts($lead, CmsLeadEmail::class, $this->normalizeContacts($emails));
            // Some consumers create the row through backend actions and then
            // synchronize contacts. Re-evaluate evidence without duplicate notices.
            if ($lead->status === CmsLead::STATUS_NEW && !$lead->executor_id) {
                $lead->notifyAvailableManagers();
            }
            if ($transaction) {
                $transaction->commit();
            }
        } catch (\Throwable $e) {
            if ($transaction && $transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
    }

    private function beginTransactionIfNeeded(): ?\yii\db\Transaction
    {
        $db = CmsLead::getDb();
        $transaction = $db->getTransaction();

        return $transaction && $transaction->isActive ? null : $db->beginTransaction();
    }

    private function saveContacts(CmsLead $lead, string $modelClass, array $contacts): void
    {
        foreach ($contacts as $contact) {
            $value = $modelClass === CmsLeadEmail::class
                ? mb_strtolower($contact['value'], 'UTF-8')
                : $contact['value'];
            $query = $modelClass::find()->andWhere(['cms_lead_id' => (int)$lead->id]);
            $query->andWhere($modelClass === CmsLeadPhone::class
                ? PhoneHelper::equalCondition('value', $value)
                : ['value' => $value]);
            if ($query->exists()) {
                continue;
            }

            $model = new $modelClass();
            $model->cms_lead_id = (int)$lead->id;
            $model->value = $value;
            $model->name = $contact['name'];
            $model->sort = $contact['sort'];
            if (!$model->save()) {
                throw new InvalidArgumentException('Не удалось сохранить контакт сделки: '.implode('; ', $model->getFirstErrors()));
            }
        }
    }

    private function normalizeContacts($contacts): array
    {
        if (!is_array($contacts)) {
            $contacts = [$contacts];
        }

        $result = [];
        foreach ($contacts as $index => $contact) {
            $data = is_array($contact) ? $contact : ['value' => $contact];
            $value = trim((string)($data['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $key = mb_strtolower($value, 'UTF-8');
            $result[$key] = [
                'value' => $value,
                'name' => trim((string)($data['name'] ?? '')) ?: null,
                'sort' => isset($data['sort']) ? (int)$data['sort'] : 500 + (int)$index,
            ];
        }

        return array_values($result);
    }

    /**
     * Keep canonical attribution columns populated for every source adapter.
     * The original URL and source payload remain untouched for audit/debugging.
     */
    private function normalizeUtmAttributes(array $attributes): array
    {
        $urlValues = [];
        $sourceUrl = trim((string)($attributes['source_url'] ?? ''));
        if ($sourceUrl !== '') {
            $query = parse_url($sourceUrl, PHP_URL_QUERY);
            if (is_string($query) && $query !== '') {
                parse_str($query, $urlValues);
            }
        }

        $sourceData = is_array($attributes['source_data'] ?? null)
            ? $attributes['source_data']
            : [];
        $payloadValues = is_array($sourceData['utm'] ?? null)
            ? $sourceData['utm']
            : [];

        $aliases = [
            'utm_source' => 'source',
            'utm_medium' => 'medium',
            'utm_campaign' => 'campaign',
            'utm_content' => 'content',
            'utm_term' => 'term',
        ];

        foreach (CmsLead::UTM_ATTRIBUTES as $attribute) {
            $alias = $aliases[$attribute];
            $candidates = [
                $attributes[$attribute] ?? null,
                $payloadValues[$attribute] ?? null,
                $payloadValues[$alias] ?? null,
                $urlValues[$attribute] ?? null,
            ];

            foreach ($candidates as $candidate) {
                if (!is_scalar($candidate)) {
                    continue;
                }
                $value = trim((string)$candidate);
                if ($value === '') {
                    continue;
                }
                $attributes[$attribute] = mb_substr($value, 0, 255);
                break;
            }
        }

        return $attributes;
    }
}

<?php

namespace skeeks\cms\services;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadEmail;
use skeeks\cms\models\CmsLeadPhone;
use skeeks\cms\helpers\PhoneHelper;
use yii\base\InvalidArgumentException;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\db\IntegrityException;

class CmsLeadService
{
    /** Attempts of the whole creation when MySQL rolls it back for a deadlock (1213) or a lock wait (1205). */
    public const LOCK_ATTEMPTS = 4;

    /** Before routing a new request: a package names its client or the deal it goes on in (crm\LeadOriginEvent). */
    public const EVENT_LEAD_ORIGIN = 'leadOrigin';

    /** After the new request of a known origin is saved, in its transaction (crm\LeadOriginEvent::$target). */
    public const EVENT_LEAD_ORIGIN_SAVED = 'leadOriginSaved';

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
            // Where the request came from, by evidence of a package (the visit of a letter link).
            $origin = $this->origin($lead, $phones, $emails);
            $continue = $origin ? $this->continueLeadOf($lead, $origin) : null;
            $route = null;
            if ($continue) {
                $this->closeAsAdded($lead, $continue);
            } else {
                // «Входящие заявки» (п. 13б): the main responsible of a found client, or the next one of the queue;
                // inside the transaction, so the turn of the queue stays locked until the deal is saved.
                $route = \skeeks\cms\crm\InboundQueue::route($lead, $phones, $emails, $origin);
            }
            if (!$lead->save()) {
                throw new InvalidArgumentException('Не удалось создать сделку: '.implode('; ', $lead->getFirstErrors()));
            }
            $this->saveContacts($lead, CmsLeadPhone::class, $phones);
            $this->saveContacts($lead, CmsLeadEmail::class, $emails);
            if (!$continue) {
                \skeeks\cms\crm\InboundQueue::afterRoute($lead, $route, $origin);
                if ($lead->status === CmsLead::STATUS_NEW && (!$lead->executor_id || $route === 'round')) {
                    $lead->notifyAvailableManagers();
                }
            }
            $lead->deferManagerNotifications = false;
            if ($lead->isFormSubmission()) {
                // The lead lifecycle deliberately skips this source: only here
                // are the submitted contacts already part of the same
                // transaction, so the entry can name them.
                $lead->recordCreationActivity();
            }
            if ($continue) {
                // The deal where the work goes on gets the contacts too, as a repeated event of this request does
                // (syncContacts() of the lookup above): a new phone of the form is not left in the closed row only.
                $this->saveContacts($continue, CmsLeadPhone::class, $phones);
                $this->saveContacts($continue, CmsLeadEmail::class, $emails);
                $this->afterAdded($lead, $continue, $origin);
            }
            if ($origin) {
                $origin->lead = $lead;
                $origin->target = $continue ?: $lead;
                \yii\base\Event::trigger(self::class, self::EVENT_LEAD_ORIGIN_SAVED, $origin);
            }
            if ($transaction) {
                $transaction->commit();
            }
            if ($continue) {
                return $continue;
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

    /**
     * Asks the packages where a new request came from (crm\LeadOriginEvent). Null — nobody knows:
     * the request is routed as usual.
     */
    private function origin(CmsLead $lead, array $phones, array $emails): ?\skeeks\cms\crm\LeadOriginEvent
    {
        $event = new \skeeks\cms\crm\LeadOriginEvent(['lead' => $lead, 'phones' => $phones, 'emails' => $emails]);
        \yii\base\Event::trigger(self::class, self::EVENT_LEAD_ORIGIN, $event);

        return $event->isKnown() ? $event : null;
    }

    /** The open deal of the same site the request goes on in; a partner request only into a deal of its partner. */
    private function continueLeadOf(CmsLead $lead, \skeeks\cms\crm\LeadOriginEvent $origin): ?CmsLead
    {
        $target = $origin->continueLead ? $origin->continueLead->resolveMerged() : null;
        if (!$target || !$target->id || !CmsLead::triageReady()
            || !in_array($target->status, [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK], true)
            || (int)$target->cms_site_id !== (int)$lead->cms_site_id
            || ($lead->partner_id && (int)$lead->partner_id !== (int)$target->partner_id)) {
            return null;
        }

        return $target;
    }

    /** The request keeps its row and source reference, closed as added to the deal (as LeadTriage::merge()). */
    private function closeAsAdded(CmsLead $lead, CmsLead $target): void
    {
        $lead->executor_id = $target->executor_id ? (int)$target->executor_id : null;
        $lead->cms_company_id = $target->cms_company_id ? (int)$target->cms_company_id : null;
        $lead->cms_user_id = $target->cms_user_id ? (int)$target->cms_user_id : null;
        $lead->status = CmsLead::STATUS_REJECTED;
        $lead->triage_result = CmsLead::TRIAGE_MERGED;
        $lead->merged_into_id = (int)$target->id;
        $lead->triaged_at = time();
        $lead->processed_at = time();
        // Not a new deal for anybody: the responsible of the target is told once below.
        $lead->suppressNotifications = true;
    }

    /** The feed keeps only absolute links (helpers\CommentHtml). */
    private function afterAdded(CmsLead $lead, CmsLead $target, \skeeks\cms\crm\LeadOriginEvent $origin): void
    {
        $excerpt = trim(mb_substr(trim((string)$lead->description), 0, 500));
        $target->addSystemActivity('Добавлено обращение '.Html::a('№'.(int)$lead->id, Url::to(['/cms/admin-cms-lead/view', 'pk' => (int)$lead->id], true))
            .' «'.Html::encode((string)$lead->name).'» ('.Html::encode($lead->sourceNameAsText).')'
            .($origin->reason !== '' ? '. '.Html::encode($origin->reason) : '')
            .($excerpt !== '' ? ':<br>'.nl2br(Html::encode($excerpt)) : '.'));
        $lead->addSystemActivity('Обращение добавлено в сделку '.Html::a('№'.(int)$target->id, Url::to(['/cms/admin-cms-lead/view', 'pk' => (int)$target->id], true))
            .' «'.Html::encode((string)$target->name).'»'.($origin->reason !== '' ? ': '.Html::encode(mb_strtolower(mb_substr($origin->reason, 0, 1)).mb_substr($origin->reason, 1)) : '').'.');
        if ($origin->notifyContinue && $target->executor_id) {
            \Yii::$app->notifications->notify((int)$target->executor_id, 'lead.merged', [
                'name' => 'В сделку добавлено обращение',
                'comment' => (string)$lead->name,
                'model' => $target,
                'eventKey' => 'lead.merged:'.(int)$lead->id,
            ]);
        }
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

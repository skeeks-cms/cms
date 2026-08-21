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
    public function createFromSource(array $attributes): CmsLead
    {
        $phones = $this->normalizeContacts($attributes['phones'] ?? []);
        $emails = $this->normalizeContacts($attributes['emails'] ?? []);
        unset($attributes['phones'], $attributes['emails']);
        $attributes = $this->normalizeUtmAttributes($attributes);

        $lead = new CmsLead();
        $lead->setAttributes($attributes);

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
                $this->syncContacts($existing, $phones, $emails);
                return $existing;
            }
        }

        $transaction = $this->beginTransactionIfNeeded();
        try {
            if (!$lead->save()) {
                throw new InvalidArgumentException('Не удалось создать лид: '.implode('; ', $lead->getFirstErrors()));
            }
            $this->saveContacts($lead, CmsLeadPhone::class, $phones);
            $this->saveContacts($lead, CmsLeadEmail::class, $emails);
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
                throw new InvalidArgumentException('Не удалось сохранить контакт лида: '.implode('; ', $model->getFirstErrors()));
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

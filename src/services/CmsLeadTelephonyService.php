<?php

namespace skeeks\cms\services;

use RuntimeException;
use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPhone;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsTelephonyUser;
use Yii;
use yii\db\IntegrityException;

/**
 * Connects a provider call to one lead and exposes the live call in its activity log.
 */
class CmsLeadTelephonyService
{
    public function registerOutgoingCall(
        CmsTelephonyUser $telephonyUser,
        string $providerCallId,
        string $phone,
        int $leadId
    ): ?CmsTelephonyCall {
        $lead = CmsLead::find()
            ->forManager()
            ->cmsSite()
            ->andWhere([CmsLead::tableName().'.id' => $leadId])
            ->one();
        if (!$lead || !$this->leadHasPhone($lead, $phone)) {
            return null;
        }

        $call = CmsTelephonyCall::find()
            ->andWhere([
                'cms_telephony_provider_id' => $telephonyUser->cms_telephony_provider_id,
                'provider_call_id' => $providerCallId,
            ])
            ->one();

        if (!$call) {
            $call = new CmsTelephonyCall();
            $call->cms_telephony_provider_id = $telephonyUser->cms_telephony_provider_id;
            $call->provider_call_id = $providerCallId;
            $call->direction = CmsTelephonyCall::DIRECTION_OUT;
            $call->status = CmsTelephonyCall::STATUS_NEW;
            $call->client_phone = $phone;
            $call->provider_user_num = $telephonyUser->provider_user_num;
            $call->cms_worker_user_id = $telephonyUser->cms_worker_user_id;

            try {
                $saved = $call->save();
            } catch (IntegrityException $e) {
                $call = CmsTelephonyCall::find()
                    ->andWhere([
                        'cms_telephony_provider_id' => $telephonyUser->cms_telephony_provider_id,
                        'provider_call_id' => $providerCallId,
                    ])
                    ->one();
                $saved = (bool)$call;
            }
            if (!$saved) {
                throw new RuntimeException('Unable to save outgoing telephony call: '.json_encode($call ? $call->errors : [], JSON_UNESCAPED_UNICODE));
            }
        }

        return $this->attachToLead($call, $lead) ? $call : null;
    }

    /**
     * Explicit context wins. Phone fallback is accepted only for one active lead,
     * because assigning a shared number to an arbitrary recent lead corrupts CRM history.
     */
    public function attachToLead(CmsTelephonyCall $call, ?CmsLead $explicitLead = null): ?CmsLead
    {
        $lead = $call->cms_lead_id ? CmsLead::findOne($call->cms_lead_id) : null;
        if (!$lead && $explicitLead && $this->leadHasPhone($explicitLead, $call->client_phone)) {
            $lead = $explicitLead;
        }
        if (!$lead) {
            $lead = $this->findUnambiguousActiveLead($call->client_phone);
        }
        if (!$lead) {
            return null;
        }

        if (!(int)$call->cms_lead_id) {
            $call->cms_lead_id = (int)$lead->id;
            if (!$call->save(false, ['cms_lead_id'])) {
                throw new RuntimeException('Unable to attach telephony call to lead #'.$lead->id);
            }
        }

        $this->ensureLog($call, $lead);

        return $lead;
    }

    protected function findUnambiguousActiveLead(?string $phone): ?CmsLead
    {
        $condition = PhoneHelper::equalCondition('leadPhones.value', $phone);
        if (!$condition) {
            return null;
        }

        $leads = CmsLead::find()->alias('lead')
            ->cmsSite()
            ->innerJoin(['leadPhones' => CmsLeadPhone::tableName()], 'leadPhones.cms_lead_id = lead.id')
            ->andWhere($condition)
            ->andWhere(['lead.status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]])
            ->groupBy('lead.id')
            ->orderBy(['lead.id' => SORT_DESC])
            ->limit(2)
            ->all();

        return count($leads) === 1 ? $leads[0] : null;
    }

    protected function ensureLog(CmsTelephonyCall $call, CmsLead $lead): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand(
                'SELECT id FROM '.CmsTelephonyCall::tableName().' WHERE id = :id FOR UPDATE',
                [':id' => (int)$call->id]
            )->queryScalar();

            $logs = CmsLog::find()
                ->andWhere([
                    'model_code' => $lead->skeeksModelCode,
                    'model_id' => $lead->id,
                    'log_type' => CmsLog::LOG_TYPE_PHONE_CALL,
                ])
                ->all();

            foreach ($logs as $log) {
                if ((int)($log->data['id'] ?? 0) === (int)$call->id) {
                    $transaction->commit();
                    return;
                }
            }

            $log = new CmsLog();
            $log->model_code = $lead->skeeksModelCode;
            $log->model_id = (int)$lead->id;
            $log->log_type = CmsLog::LOG_TYPE_PHONE_CALL;
            $log->data = $call->toArray(['id', 'direction', 'client_phone']);
            $log->created_by = $call->cms_worker_user_id ?: null;
            $log->updated_by = $call->cms_worker_user_id ?: null;
            if (!$log->save()) {
                throw new RuntimeException('Unable to save lead call log: '.json_encode($log->errors, JSON_UNESCAPED_UNICODE));
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
    }

    protected function isSamePhone(?string $first, ?string $second): bool
    {
        $firstDigits = PhoneHelper::searchDigits($first);
        $secondDigits = PhoneHelper::searchDigits($second);

        return strlen($firstDigits) >= 10 && $firstDigits === $secondDigits;
    }

    protected function leadHasPhone(CmsLead $lead, ?string $phone): bool
    {
        foreach ($lead->phones as $leadPhone) {
            if ($this->isSamePhone($leadPhone->value, $phone)) {
                return true;
            }
        }
        return false;
    }
}

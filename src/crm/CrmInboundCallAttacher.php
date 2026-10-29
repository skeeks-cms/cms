<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\services\CmsTelephonyCallLinkService;
use yii\db\ActiveRecord;

/**
 * Calls for CrmInbound: the clients are the company and the person the call
 * was matched to by the phone; the deal link is the call's own (cms_lead_id,
 * CmsTelephonyCallLinkService::attachLead() with its feed record).
 */
class CrmInboundCallAttacher implements CrmInboundAttacherInterface
{
    public function find(int $id): ?ActiveRecord
    {
        return CmsTelephonyCall::findOne($id);
    }

    public function clients(ActiveRecord $inbound): array
    {
        /** @var CmsTelephonyCall $inbound */
        return array_values(array_filter([$inbound->company, $inbound->user && !$inbound->user->is_worker ? $inbound->user : null]));
    }

    public function isLinked(ActiveRecord $inbound, CmsLead $lead): bool
    {
        return (int)$inbound->cms_lead_id === (int)$lead->id;
    }

    public function attach(ActiveRecord $inbound, CmsLead $lead, int $actorId): void
    {
        (new CmsTelephonyCallLinkService())->attachLead($inbound, $lead);
    }

    public function describe(ActiveRecord $inbound): string
    {
        /** @var CmsTelephonyCall $inbound */
        return ($inbound->status === CmsTelephonyCall::STATUS_FAILED ? 'Пропущенный звонок' : 'Звонок').' с '.(string)$inbound->client_phone
            .($inbound->created_at ? ', '.\Yii::$app->formatter->asDatetime((int)$inbound->created_at, 'short') : '');
    }
}

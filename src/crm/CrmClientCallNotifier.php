<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsUser;

/**
 * П2а+: the main responsible of a client learns about an incoming call of
 * the client (the company of the call, else its person) — answered by another
 * employee or missed. Not when he took the call himself. One notification per
 * call (event key), the kind `crm.client_call` of «Клиенты и предложения»:
 * the employee's notification settings decide the channels.
 */
class CrmClientCallNotifier
{
    public function notify(CmsTelephonyCall $call): void
    {
        if ($call->direction !== CmsTelephonyCall::DIRECTION_IN
            || !in_array($call->status, [CmsTelephonyCall::STATUS_ANSWERED, CmsTelephonyCall::STATUS_FAILED], true)
            || !\Yii::$app->has('notifications') || !CrmAccess::mainReady()) {
            return;
        }
        $record = $call->cms_company_id ? CmsCompany::findOne((int)$call->cms_company_id)
            : ($call->cms_user_id ? CmsUser::findOne((int)$call->cms_user_id) : null);
        if (!$record || ($record instanceof CmsUser && $record->is_worker)) {
            return;
        }
        $code = $record instanceof CmsCompany ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT;
        $mainId = CrmAccess::instance()->mainResponsibleId($code, (int)$record->id);
        if (!$mainId || $mainId === (int)$call->cms_worker_user_id) {
            return;
        }
        $client = '«'.($record instanceof CmsCompany ? $record->name : $record->shortDisplayName).'»';
        $worker = $call->cms_worker_user_id ? CmsUser::findOne((int)$call->cms_worker_user_id) : null;
        \Yii::$app->notifications->notify($mainId, 'crm.client_call', [
            'name' => $call->status === CmsTelephonyCall::STATUS_ANSWERED
                ? 'Звонок клиента '.$client.($worker ? ', разговор: '.$worker->shortDisplayName : '')
                : 'Пропущенный звонок клиента '.$client,
            'model' => $record,
            'eventKey' => 'crm.client_call:'.(int)$call->id,
        ]);
    }
}

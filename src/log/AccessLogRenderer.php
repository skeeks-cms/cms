<?php

namespace skeeks\cms\log;

use skeeks\cms\models\CmsLog;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * A record of the journal of the access to a client in its feed
 * (crm\CrmAccessLog, log type `crm_access`): «Петрова Анна: доступ выдан —
 * Смирнов Олег: нет доступа → Свои сделки».
 */
class AccessLogRenderer extends CmsLogEntryRenderer
{
    public function entry(CmsLog $log): CmsLogEntry
    {
        $data = is_array($log->data) ? $log->data : [];
        $leadId = (int)($data['cms_lead_id'] ?? 0);

        return new CmsLogEntry([
            'icon' => 'shield',
            'tone' => CmsLogEntry::TONE_NEUTRAL,
            'title' => 'Доступ к клиенту',
            'summary' => Html::encode((string)$log->comment),
            // The deal of the event (a change of its responsible); its card checks the access itself.
            'details' => $leadId ? [Html::a('Сделка №'.$leadId, Url::to(['/cms/admin-cms-lead/view', 'pk' => $leadId]), ['data-pjax' => 0, 'target' => '_blank'])] : [],
        ]);
    }
}

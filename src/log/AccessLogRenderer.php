<?php

namespace skeeks\cms\log;

use skeeks\cms\models\CmsLog;
use yii\helpers\Html;

/**
 * A record of the journal of the access to a client in its feed
 * (crm\CrmAccessLog, log type `crm_access`): «Фирстова Елизавета: клиент
 * взят в работу из общего пула».
 */
class AccessLogRenderer extends CmsLogEntryRenderer
{
    public function entry(CmsLog $log): CmsLogEntry
    {
        return new CmsLogEntry([
            'icon' => 'shield',
            'tone' => CmsLogEntry::TONE_NEUTRAL,
            'title' => 'Доступ к клиенту',
            'summary' => Html::encode((string)$log->comment),
        ]);
    }
}

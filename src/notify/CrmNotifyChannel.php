<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\notify;

/**
 * The bell of the CRM (CmsWebNotify): always there, it keeps every notification
 * and is the target of links from other channels. A category switched off here
 * still gets its row, read and silent.
 */
class CrmNotifyChannel extends NotifyChannel
{
    public $code = Notifications::CHANNEL_CRM;
    public $name = 'Веб и браузер';
    public $priority = 0;

    public function isConnected(int $userId): bool
    {
        return true;
    }

    public function status(int $userId): string
    {
        return 'Колокольчик в шапке и уведомления браузера — всегда';
    }
}

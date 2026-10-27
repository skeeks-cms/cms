<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\notify;

use skeeks\cms\models\CmsWebNotify;
use yii\base\Event;

/**
 * A notification was stored (Notifications::EVENT_CREATED): a new row, or a
 * burst row updated by one more event ($isNew false). Runs inside the caller's
 * transaction: handlers only queue their work.
 */
class NotifyEvent extends Event
{
    /** @var CmsWebNotify */
    public $notify;

    /** @var bool */
    public $isNew = true;
}

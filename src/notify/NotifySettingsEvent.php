<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\notify;

use yii\base\Event;

/**
 * The personal settings page asks packages for extra blocks
 * (Notifications::EVENT_RENDER_SETTINGS): a handler appends trusted HTML.
 */
class NotifySettingsEvent extends Event
{
    /** @var int */
    public $userId;

    /** @var \yii\web\View */
    public $view;

    /** @var string[] */
    public $html = [];
}

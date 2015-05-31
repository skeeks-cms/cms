<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.05.2015
 */
namespace skeeks\cms\modules\admin\controllers\events;

use skeeks\cms\modules\admin\controllers\AdminController;
use yii\base\Event;

class AdminInitEvent extends Event
{
    /**
     * @var AdminController
     */
    public $controller = null;
}
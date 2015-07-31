<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.07.2015
 */
namespace skeeks\cms\events;

use skeeks\cms\base\CmsEvent;
use yii\base\Event;

/**
 * Class CmsEvent
 * @package skeeks\cms\base
 */
class CmsEventAfterRegister extends CmsEvent
{
    /**
     * @var bool Событие может порождать email уведомление
     */
    public $name    = "cms.event.afterRegister";
}
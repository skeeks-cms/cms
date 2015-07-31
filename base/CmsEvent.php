<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.07.2015
 */
namespace skeeks\cms\base;

use yii\base\Event;

/**
 * Class CmsEvent
 * @package skeeks\cms\base
 */
class CmsEvent extends Event
{
    CONST EVENT_NAME = 'cms.event';

    /**
     * @var bool Событие может порождать email уведомление
     */
    public $hasEmailNotify    = true;

    /**
     * @var bool Событие может порождать sms уведомление
     */
    public $hasSmsNotify      = false;

    /**
     * @var bool Событие может порождать уведомление пользователя
     */
    public $hasUserNotify     = false;
}
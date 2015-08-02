<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.07.2015
 */
namespace skeeks\cms\mail;

/**
 * Class Message
 * @package skeeks\cms\mail
 */
class Message extends \yii\swiftmailer\Message
{
    /**
     * @var string
     */
    public $eventName = '';

    /**
     * @var string
     */
    public $eventDesctiption = '';

    /**
     * @var string
     */
    public $view = '';


    /**
     * @param $eventName
     * @return $this
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
        return $this;
    }

    /**
     * @param $eventDesctiption
     * @return $this
     */
    public function setEventDesctiption($eventDesctiption)
    {
        $this->eventDesctiption = $eventDesctiption;
        return $this;
    }
}
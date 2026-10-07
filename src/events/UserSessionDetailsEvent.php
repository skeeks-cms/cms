<?php
namespace skeeks\cms\events;

/** Extensions append trusted rendered markup for an already authorized session. */
class UserSessionDetailsEvent extends \yii\base\Event
{
    public $session;
    public $view;
    public $html = '';
}

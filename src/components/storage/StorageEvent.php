<?php
/**
 * StorageEvent
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components\storage;

use yii\base\Event;

/**
 * Class StorageEvent
 * @package skeeks\cms\components\storage
 */
class StorageEvent extends Event
{
    /**
     * @var boolean if message was sent successfully.
     */
    public $isSuccessful;
    /**
     * @var boolean whether to continue sending an email. Event handlers of
     * [[\yii\mail\BaseMailer::EVENT_BEFORE_SEND]] may set this property to decide whether
     * to continue send or not.
     */
    public $isValid = true;
}

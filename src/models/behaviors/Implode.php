<?php
/**
 * implode / explode before after save
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models\behaviors;

use yii\db\BaseActiveRecord;
use \yii\base\Behavior;
use yii\base\Event;

/**
 * Class Implode
 * @package skeeks\cms\models\behaviors
 */
class Implode extends Behavior
{
    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var string
     */
    public $delimetr = ',';

    /**
     * @return array
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => "implodeFields",
            BaseActiveRecord::EVENT_BEFORE_UPDATE => "implodeFields",
            BaseActiveRecord::EVENT_AFTER_FIND => "explodeFields",
            BaseActiveRecord::EVENT_AFTER_UPDATE => "explodeFields",
            BaseActiveRecord::EVENT_AFTER_INSERT => "explodeFields",
        ];
    }

    /**
     * @param Event $event
     */
    public function implodeFields($event)
    {
        foreach ($this->fields as $fielName) {
            if ($this->owner->{$fielName}) {
                if (is_array($this->owner->{$fielName})) {
                    $this->owner->{$fielName} = implode($this->delimetr, $this->owner->{$fielName});
                }
            } else {
                $this->owner->{$fielName} = "";
            }
        }
    }


    /**
     * @param Event $event
     */
    public function explodeFields($event)
    {
        foreach ($this->fields as $fielName) {
            if ($this->owner->{$fielName}) {
                if (is_string($this->owner->{$fielName})) {
                    $this->owner->{$fielName} = explode($this->delimetr, $this->owner->{$fielName});
                }
            } else {
                $this->owner->{$fielName} = [];
            }
        }
    }


}

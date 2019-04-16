<?php
/**
 * HasJsonFieldsBehavior
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 19.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models\behaviors;

use yii\db\BaseActiveRecord;
use \yii\base\Behavior;
use yii\base\Event;
use yii\helpers\Json;

/**
 * Class Serialize
 * @package skeeks\cms\models\behaviors
 */
class HasJsonFieldsBehavior extends Behavior
{
    /**
     * @var array
     */
    public $fields = [];

    /**
     * @return array
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => "jsonEncodeFields",
            BaseActiveRecord::EVENT_BEFORE_UPDATE => "jsonEncodeFields",
            BaseActiveRecord::EVENT_AFTER_FIND => "jsonDecodeFields",
            BaseActiveRecord::EVENT_AFTER_UPDATE => "jsonDecodeFields",
            BaseActiveRecord::EVENT_AFTER_INSERT => "jsonDecodeFields",
        ];
    }

    /**
     * @param Event $event
     */
    public function jsonEncodeFields($event)
    {
        foreach ($this->fields as $fielName) {
            if ($this->owner->{$fielName}) {
                if (is_array($this->owner->{$fielName})) {
                    $this->owner->{$fielName} = Json::encode((array)$this->owner->{$fielName});
                }
            } else {
                $this->owner->{$fielName} = "";
            }
        }
    }


    /**
     * @param Event $event
     */
    public function jsonDecodeFields($event)
    {
        foreach ($this->fields as $fielName) {
            if ($this->owner->{$fielName}) {
                if (is_string($this->owner->{$fielName})) {
                    try {
                        $this->owner->{$fielName} = Json::decode($this->owner->{$fielName});

                    } catch (\Exception $e) {
                        $r = new \ReflectionClass($this->owner);
                        \Yii::warning("Json::decode error — {$e->getMessage()} value for decode={$this->owner->{$fielName}} model={$r->getName()} modeldata=" . print_r($this->owner->toArray(), true), self::class);
                        $this->owner->{$fielName} = [];
                    }

                }
            } else {
                $this->owner->{$fielName} = [];
            }
        }
    }
}

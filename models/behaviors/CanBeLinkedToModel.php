<?php
/**
 * CanBeLinkedToModel
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\Exception;
use skeeks\cms\models\behaviors\events\AfterLinkedModel;
use skeeks\cms\models\behaviors\events\AfterUnLinkedModel;
use skeeks\cms\models\helpers\ModelRef;
use yii\base\Event;
use yii\db\BaseActiveRecord;

/**
 * Class HasLinkedModels
 * @package skeeks\cms\models\behaviors
 */
class CanBeLinkedToModel extends \skeeks\cms\base\behaviors\ActiveRecord
{
    const EVENT_AFTER_LINKED    = "linkedModel";
    const EVENT_AFTER_UN_LINKED = "unLinkedModel";


    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT      => "insertedModel",
            BaseActiveRecord::EVENT_AFTER_UPDATE      => "updatedModel",
            BaseActiveRecord::EVENT_AFTER_DELETE      => "deletedModel",
        ];
    }


    public function insertedModel(Event $event)
    {
        $model = $event->sender;
        if ($linkedModel = $model->findLinkedToModel())
        {
            $linkedModel->trigger(self::EVENT_AFTER_LINKED, new AfterLinkedModel([
                "model" => $event->sender
            ]));
        }
    }

    public function updatedModel(Event $event)
    {
        //TODO:: доработать
        $model = $event->sender;
        if ($linkedModel = $model->findLinkedToModel())
        {
            $linkedModel->trigger(self::EVENT_AFTER_LINKED, new AfterLinkedModel([
                "model" => $event->sender
            ]));
        }
    }

    public function deletedModel(Event $event)
    {
        $model = $event->sender;
        if ($linkedModel = $model->findLinkedToModel())
        {
            $linkedModel->trigger(self::EVENT_AFTER_UN_LINKED, new AfterUnLinkedModel([
                "model" => $event->sender
            ]));
        }
    }


    /**
     * @param ActiveRecord $modelForLink
     * @throws Exception
     */
    public function linkToModel(ActiveRecord $modelForLink)
    {
        if (!$ref = $modelForLink->getRef())
        {
            throw new Exception("У модели нет ссылки, к ней не получается привязаться");
        }

        $this->owner->linked_to_model = $ref->getCode();
        $this->owner->linked_to_value = $ref->getValue();

        $this->owner->save(false);

        $modelForLink->trigger(self::EVENT_AFTER_LINKED, new AfterLinkedModel([
            "sender"    => $this
        ]));
    }

    /**
     *
     * Текущая сущьность привязана к моделе?
     *
     * @param ActiveRecord $modelForLink
     * @return bool
     */
    public function isLinkedToModel(ActiveRecord $modelForLink)
    {
        if (!$ref = $modelForLink->getRef())
        {
            return false;
        }

        if ($this->owner->linked_to_model == $ref->getCode() && $this->owner->linked_to_value == $ref->getValue())
        {
            return true;
        }

        return false;
    }

    /**
     * Пригреплен ли вообще?
     * @return bool
     */
    public function isLinked()
    {
        if ($this->owner->linked_to_model && $this->owner->linked_to_value)
        {
            return true;
        }

        return false;
    }
    /**
     *
     * Найти модель к которой привязана текущая модель.
     *
     * @return array|null|\yii\db\ActiveRecord
     */
    public function findLinkedToModel()
    {
        $ref = new ModelRef($this->owner->linked_to_model, $this->owner->linked_to_value);
        return $ref->findModel();
    }

}
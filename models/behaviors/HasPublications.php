<?php
/**
 * Поведение которое перед удалением сущьности, собирается удалить комментарии связанные с ней.
 * TODO: добавить опции наподобии как в БД
 * ON_DELETE ON_UPDATE
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\App;
use skeeks\cms\models\behaviors\events\AfterLinkedModel;
use skeeks\cms\models\behaviors\events\AfterUnLinkedModel;
use skeeks\cms\models\Publication;
use skeeks\cms\models\Subscribe;
use skeeks\cms\models\User;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 * Class HasSubscribes
 * @package skeeks\cms\models\behaviors
 */
class HasPublications extends HasLinkedModels
{
    public $canBeLinkedModels       = ['skeeks\cms\models\Publication'];
    public $restrictMessageError    = "Невозможно удалить запись, для начала необходимо удалить все связанные публикации на эту запись.";

    public function events()
    {
        return array_merge(parent::events(), [
            CanBeLinkedToModel::EVENT_AFTER_LINKED          => "linkedModel",
            CanBeLinkedToModel::EVENT_AFTER_UN_LINKED       => "unLinkedModel",
        ]);
    }

    /**
     * Если привязана новая сущьность голос, то пересчитываем количество голосов
     * @param AfterLinkedModel $event
     */
    public function linkedModel(AfterLinkedModel $event)
    {
        if ($event->model)
        {
            if ($event->model instanceof Publication)
            {
                //$this->calculateCountPublications();
            }
        }
    }

    /**
     * Если отвязана сущьность голос, то пересчитываем количество голосов
     * @param AfterUnLinkedModel $event
     */
    public function unLinkedModel(AfterUnLinkedModel $event)
    {
        if ($event->model)
        {
            if ($event->model instanceof Subscribe)
            {
                //$this->calculateCountPublications();
            }
        }
    }



    /**
     * Найти все подписки
     * @return \yii\db\ActiveQuery
     * @throws \skeeks\sx\Exception
     */
    public function findPublications()
    {
        return Publication::find()->where($this->owner->getRef()->toArray());
    }
}
<?php
/**
 * TODO: is depricated (1.2.0)
 *
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

use skeeks\cms\models\behaviors\events\AfterLinkedModel;
use skeeks\cms\models\behaviors\events\AfterUnLinkedModel;
use skeeks\cms\models\Comment;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 * Class HasComments
 * @package skeeks\cms\models\behaviors
 */
class HasComments extends HasLinkedModels
{
    public $canBeLinkedModels       = ['skeeks\cms\models\Comment'];
    public $restrictMessageError    = "Невозможно удалить запись, для начала необходимо удалить все связанные комментарии";

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
            if ($event->model instanceof Comment)
            {
                $this->calculateCountComments();
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
            if ($event->model instanceof Comment)
            {
                $this->calculateCountComments();
            }
        }
    }




    /**
     * @param $content
     * @return bool|Comment
     * @throws \skeeks\sx\Exception
     */
    public function addComment($content)
    {
        $comment = new Comment(array_merge(
            $this->owner->getRef()->toArray(),
            [
                "content"           => $content,
            ]
        ));
        $comment = $comment->save(false);
        return $comment;
    }

    /**
     * @return $this
     */
    public function calculateCountComments()
    {
        $this->owner->setAttribute("count_comment", count($this->findComments()->all()));
        $this->owner->save();
        return $this;
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \skeeks\sx\Exception
     */
    public function findComments()
    {
        return Comment::find()->where($this->owner->getRef()->toArray());
    }
}
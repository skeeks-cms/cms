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
use skeeks\cms\models\Subscribe;
use skeeks\cms\models\User;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 * Class HasSubscribes
 * @package skeeks\cms\models\behaviors
 */
class HasSubscribes extends HasLinkedModels
{
    public $canBeLinkedModels       = ['skeeks\cms\models\Subscribe'];
    public $restrictMessageError    = "Невозможно удалить запись, для начала необходимо удалить все связанные подписки на эту запись.";

    public function attach($owner)
    {
        $owner->attachBehavior("implode_subscribes",  [
            "class"  => Implode::className(),
            "fields" =>  [
                "users_subscribers",
            ]
        ]);

        parent::attach($owner);
    }

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
            if ($event->model instanceof Subscribe)
            {
                $this->calculateCountSubscribes();
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
                $this->calculateCountSubscribes();
            }
        }
    }





    /**
     * @return int
     */
    public function getSubscribeCount()
    {
        return (int) $this->owner->count_subscribe;
    }

    /**
     * id пользователей которые подписались.
     * @return array
     */
    public function getUserIdsSubscribes()
    {
        return (array) $this->owner->users_subscribers;
    }




    /**
     * Подписаться
     * @return bool|Subscribe
     */
    public function addSubscribe()
    {
        $subscribe = new Subscribe($this->owner->getRef()->toArray());
        $subscribe = $subscribe->save(false);
        return $subscribe;
    }

    /**
     * Обновление счетчика подписок
     * @return $this
     */
    public function calculateCountSubscribes()
    {
        $result = [];
        if ($subscrs = $this->findSubscribes()->all())
        {
            /**
             * @var Subscribe $subscribe
             */
            foreach ($subscrs as $subscribe)
            {
                $result[] = $subscribe->created_by;
            }

            $result = array_unique($result);
        }
        $this->owner->setAttribute("count_subscribe", count($result));
        $this->owner->setAttribute("users_subscribers", $result);
        $this->owner->save();
        return $this;
    }

    /**
     * Найти все подписки
     * @return \yii\db\ActiveQuery
     * @throws \skeeks\sx\Exception
     */
    public function findSubscribes()
    {
        return Subscribe::find()->where($this->owner->getRef()->toArray());
    }

    /**
     * получить модель подписки на текущую сущьность
     * @param User $user
     * @return array|null|\yii\db\ActiveRecord
     */
    public function findUserSubscribe(User $user = null)
    {
        if ($user === null)
        {
            if (!$user = \Yii::$app->cms->getAuthUser())
            {
                return null;
            }
        }

        return Subscribe::find()->where($this->owner->getRef()->toArray())->andWhere(["created_by" => $user->getId()])->one();
    }

    /**
     * Пользователь подпиан на эту сущьность
     * @param User $user
     * @return bool
     */
    public function userIsSubscribe(User $user = null)
    {
        if ($user === null)
        {
            if (!$user = \Yii::$app->cms->getAuthUser())
            {
                return false;
            }
        }

        return (bool) in_array($user->getId(), $this->getUserIdsSubscribes());
    }
}
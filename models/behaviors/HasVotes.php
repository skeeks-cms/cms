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
use skeeks\cms\Exception;
use skeeks\cms\models\behaviors\events\AfterLinkedModel;
use skeeks\cms\models\behaviors\events\AfterUnLinkedModel;
use skeeks\cms\models\User;
use \skeeks\cms\models\Vote;
use yii\base\Event;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 * Class HasVotes
 * @package skeeks\cms\models\behaviors
 */
class HasVotes extends HasLinkedModels
{
    public $canBeLinkedModels       = ['skeeks\cms\models\Vote'];
    public $restrictMessageError    = "Невозможно удалить запись, для начала необходимо удалить все связанные голоса.";

    public function attach($owner)
    {
        $owner->attachBehavior("implode_subscribes",  [
            "class"  => Implode::className(),
            "fields" =>  [
                "users_votes_up", "users_votes_down"
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
            if ($event->model instanceof Vote)
            {
                $this->calculateVotes();
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
            if ($event->model instanceof Vote)
            {
                $this->calculateVotes();
            }
        }
    }


    /**
     * Найти все голоса
     * @return \yii\db\ActiveQuery
     * @throws \skeeks\sx\Exception
     */
    public function findVotes()
    {
        return Vote::find()->where($this->owner->getRef()->toArray());
    }

    /**
     * TODO: позже отимизировать, не нужно постоянно все пересчитывать. Но пока сгодится.
     * Обновление счетчика подписок
     * @return $this
     */
    public function calculateVotes()
    {
        $votes = $this->findVotes()->all();

        $result = 0;
        $users_votes_up = [];
        $users_votes_down = [];
        if ($votes)
        {
            /**
             * @var Vote $vote
             */
            foreach ($votes as $vote)
            {
                $result = $result + $vote->value;

                if ($vote->value > 0)
                {
                    $users_votes_up[] = $vote->created_by;
                } else if ($vote->value < 0)
                {
                    $users_votes_down[] = $vote->created_by;
                }
            }
        }
        $this->owner->setAttribute("count_vote", count($votes));
        $this->owner->setAttribute("result_vote", $result);
        $this->owner->setAttribute("users_votes_up",   $users_votes_up);
        $this->owner->setAttribute("users_votes_down", $users_votes_down);

        $this->owner->save();
        return $this;
    }









    /**
     * @param $value
     * @return bool|Vote
     */
    public function vote($value)
    {
        if ($vote = $this->findUserVote())
        {
            $vote->delete();
        }

        $vote = new Vote($this->owner->getRef()->toArray());
        $vote->setAttribute("value", $value);
        $vote = $vote->save(false);
        return $vote;
    }
    /**
     * голос плюс
     * @return bool|Vote
     */
    public function voteUp()
    {
        return $this->vote(1);
    }

    /**
     * голос плюс
     * @return bool|Vote
     */
    public function voteDown()
    {
        return $this->vote(-1);
    }


    /**
     *
     * Найти голос пользователя, для текущей сущьности
     *
     * @param User $user
     * @return array|null|\yii\db\ActiveRecord
     */
    public function findUserVote(User $user = null)
    {
        if ($user === null)
        {
            if (!$user = \Yii::$app->cms->getAuthUser())
            {
                return null;
            }
        }

        return Vote::find()->where($this->owner->getRef()->toArray())->andWhere(["created_by" => $user->getId()])->one();
    }


    /**
     *
     * Получение массив id пользователей которые проголосовали +
     *
     * @return array
     */
    public function getUserIdsVoteUp()
    {
        return (array) $this->owner->users_votes_up;
    }

    /**
     *
     * Получение массив id пользователей которые проголосовали -
     *
     * @return array
     */
    public function getUserIdsVoteDown()
    {
        return (array) $this->owner->users_votes_down;
    }

    /**
     * @return mixed
     */
    public function getVoteResult()
    {
        return $this->owner->result_vote;
    }

    /**
     * @return int
     */
    public function getVoteCount()
    {
        return (int) $this->owner->count_vote;
    }

    /**
     *
     * Значение голоса пользователя для текущей сущьности, без запросов в базу данных.
     *
     * @param User $user
     * @return int
     */
    public function getUserVoteValue(User $user = null)
    {
        if ($user === null)
        {
            if (!$user = \Yii::$app->cms->getAuthUser())
            {
                return 0;
            }
        }

        if (in_array($user->getId(), $this->getUserIdsVoteUp()))
        {
            return 1;
        } else if (in_array($user->getId(), $this->getUserIdsVoteDown()))
        {
            return -1;
        }

        return 0;
    }

}
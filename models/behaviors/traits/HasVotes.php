<?php
/**
 * HasSubscribes
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors\traits;
use \skeeks\cms\models\Vote;

/**
 * Class HasVotes
 * @package skeeks\cms\models\behaviors\traits
 */
trait HasVotes
{
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

        $vote = new Vote($this->getRef()->toArray());
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
        $this->setAttribute("count_vote", count($votes));
        $this->setAttribute("result_vote", $result);
        $this->setAttribute("users_votes_up",   $users_votes_up);
        $this->setAttribute("users_votes_down", $users_votes_down);

        $this->save();
        return $this;
    }

    /**
     * Найти все голоса
     * @return \yii\db\ActiveQuery
     * @throws \skeeks\sx\Exception
     */
    public function findVotes()
    {
        return Vote::find()->where($this->getRef()->toArray());
    }
    /**
     *
     * Найти голос пользователя, для текущей сущьности
     *
     * @param \common\models\User $user
     * @return array|null|Vote
     */
    public function findUserVote(\common\models\User $user = null)
    {
        if ($user === null)
        {
            if (!$user = \skeeks\cms\App::user())
            {
                return null;
            }
        }

        return Vote::find()->where($this->getRef()->toArray())->andWhere(["created_by" => $user->getId()])->one();
    }






    /**
     *
     * Получение массив id пользователей которые проголосовали +
     *
     * @return array
     */
    public function getUserIdsVoteUp()
    {
        return (array) $this->users_votes_up;
    }

    /**
     *
     * Получение массив id пользователей которые проголосовали -
     *
     * @return array
     */
    public function getUserIdsVoteDown()
    {
        return (array) $this->users_votes_down;
    }

    /**
     * @return mixed
     */
    public function getVoteResult()
    {
        return $this->result_vote;
    }

    /**
     * @return int
     */
    public function getVoteCount()
    {
        return (int) $this->count_vote;
    }

    /**
     *
     * Значение голоса пользователя для текущей сущьности, без запросов в базу данных.
     *
     * @param \common\models\User $user
     * @return int
     */
    public function getUserVoteValue(\common\models\User $user = null)
    {
        if ($user === null)
        {
            if (!$user = \skeeks\cms\App::user())
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
<?php
/**
 * HasSubscribes
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace \skeeks\cms\modules\subscribe\behaviors\traits;

use \skeeks\cms\modules\subscribe\models\Subscribe;
use \skeeks\cms\modules\user\models\User;

/**
 * Class HasSubscribes
 */
trait HasSubscribes
{

    /**
     * @return int
     */
    public function getSubscribeCount()
    {
        return (int) $this->count_subscribe;
    }

    /**
     * id пользователей которые подписались.
     * @return array
     */
    public function getUserIdsSubscribes()
    {
        return (array) $this->users_subscribers;
    }




    /**
     * Подписаться
     * @return bool|Subscribe
     */
    public function addSubscribe()
    {
        $subscribe = new Subscribe($this->getRef()->toArray());
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
        $this->setAttribute("count_subscribe", count($result));
        $this->setAttribute("users_subscribers", $result);
        $this->save();
        return $this;
    }

    /**
     * Найти все подписки
     * @return \yii\db\ActiveQuery
     * @throws \skeeks\sx\Exception
     */
    public function findSubscribes()
    {
        return Subscribe::find()->where($this->getRef()->toArray());
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
            if (!$user = \skeeks\cms\App::user())
            {
                return null;
            }
        }

        return Subscribe::find()->where($this->getRef()->toArray())->andWhere(["created_by" => $user->getId()])->one();
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
            if (!$user = \skeeks\cms\App::user())
            {
                return false;
            }
        }

        return (bool) in_array($user->getId(), $this->getUserIdsSubscribes());
    }
}
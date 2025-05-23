<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
use yii\helpers\ArrayHelper;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsCompanyQuery extends CmsActiveQuery
{
    /**
     * Поиск компаний доступных сотруднику
     *
     * @param User|null $user
     * @return $this
     */
    public function forManager(User $user = null)
    {
        if ($user === null) {
            $user = \Yii::$app->user->identity;
            $isCanAdmin = \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        } else {
            $isCanAdmin = \Yii::$app->authManager->checkAccess($user->id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        }

        if (!$user) {
            return $this;
        }


        //Если нет прав админа, нужно показать только доступные компании
        if (!$isCanAdmin) {

            $managers = [];
            $managers[] = $user->id;

            if ($subordinates = $user->subordinates) {
                $managers = ArrayHelper::merge($managers, ArrayHelper::map($subordinates, "id", "id"));
            }

            $this->joinWith("managers as managers");
            $this->andWhere(["managers.id" => $managers]);
        }

        return $this;
    }
    
    /**
     * Поиск компаний доступных клиенту
     *
     * @param User|null $user
     * @return $this
     */
    public function forClient(User $user = null)
    {
        if ($user === null) {
            $user = \Yii::$app->user->identity;
        }

        if (!$user) {
            return $this;
        }
        
        $this->andWhere([$this->getPrimaryTableName() . ".id" => $user->getCompanies()->select('id')]);

        return $this;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function search($word = '')
    {
        /*$this->joinWith("cmsUserPhones as cmsUserPhones");
        $this->joinWith("cmsUserEmails as cmsUserEmails");*/
        $this->groupBy($this->getPrimaryTableName().'.id');

        $this->joinWith('addresses as addresses');
        $this->joinWith('emails as emails');
        $this->joinWith('phones as phones');
        $this->joinWith('links as links');
        $this->joinWith('contractors as contractors');
        $this->joinWith('users as users');

        return $this->andWhere([
            'or',
            ['like', $this->getPrimaryTableName().'.name', $word],
            ['like', $this->getPrimaryTableName().'.description', $word],
            ['like', 'emails.value', $word],
            ['like', 'phones.value', $word],
            ['like', 'addresses.name', $word],
            ['like', 'addresses.value', $word],
            ['like', 'links.url', $word],

            ['like', 'contractors.name', $word],
            ['like', 'contractors.first_name', $word],
            ['like', 'contractors.last_name', $word],
            ['like', 'contractors.patronymic', $word],
            ['like', 'contractors.inn', $word],

            ['like', 'users.first_name', $word],
            ['like', 'users.last_name', $word],
            ['like', 'users.patronymic', $word],
        ]);
    }
}
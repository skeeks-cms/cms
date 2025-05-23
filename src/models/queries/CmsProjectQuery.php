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
class CmsProjectQuery extends CmsActiveQuery
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
            
            $this->andWhere([
                "or",
                [self::getPrimaryTableName() . ".is_private" => 0],
                [
                    "and",
                    [self::getPrimaryTableName() . ".is_private" => 1],
                    ["managers.id" => $managers]
                ],
            ]);
        }

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

        return $this->andWhere([
            'or',
            ['like', $this->getPrimaryTableName().'.name', $word],
            ['like', $this->getPrimaryTableName().'.description', $word],
        ]);
    }
}
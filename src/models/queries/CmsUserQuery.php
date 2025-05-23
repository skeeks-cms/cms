<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
use yii\helpers\ArrayHelper;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsUserQuery extends CmsActiveQuery
{
    /**
     * @param string $username
     * @return $this
     */
    public function username(string $username)
    {
        return $this->andWhere([$this->getPrimaryTableName().'.username' => $username]);
    }

    /**
     * @param string $username
     * @return $this
     */
    public function isWorker(bool $value = true)
    {
        return $this->andWhere([$this->getPrimaryTableName().'.is_worker' => (int) $value]);
    }

    /**
     * @param string $username
     * @return $this
     */
    public function email(string $email)
    {
        $this->joinWith("cmsUserEmails as cmsUserEmails");
        $this->groupBy($this->getPrimaryTableName().'.id');

        return $this->andWhere(['cmsUserEmails.value' => trim($email)]);
    }

    /**
     * @param string $username
     * @return $this
     */
    public function phone(string $phone)
    {
        $this->joinWith("cmsUserPhones as cmsUserPhones");
        $this->groupBy($this->getPrimaryTableName().'.id');

        return $this->andWhere(['cmsUserPhones.value' => $phone]);
    }

    /**
     * @param string $username
     * @return $this
     */
    public function search($word = '')
    {
        $this->joinWith("cmsUserPhones as cmsUserPhones");
        $this->joinWith("cmsUserEmails as cmsUserEmails");
        $this->groupBy($this->getPrimaryTableName().'.id');

        return $this->andWhere([
            'or',
            ['like', 'cmsUserPhones.value', $word],
            ['like', 'cmsUserEmails.value', $word],
            ['like', $this->getPrimaryTableName() . '.first_name', $word],
            ['like', $this->getPrimaryTableName() . '.last_name', $word],
            ['like', $this->getPrimaryTableName() . '.patronymic', $word],
            ['like', $this->getPrimaryTableName() . '.company_name', $word],
        ]);
    }


    /**
     * Поиск компаний доступных пользователю
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

            $cmsCompanyQuery = CmsCompany::find()->forManager()->select(CmsCompany::tableName() . '.id');
            $this->joinWith("companiesAll as companies");
            $this->joinWith("managers as managers");

            //Поиск клиентов с которыми связан сотрудник + все дочерние сотрудники
            $this->andWhere([
                'or',
                //Связь клиентов с менеджерами
                ["managers.id" => $managers],
                //Искать конткты по всем доступным компаниям
                ["companies.id" => $cmsCompanyQuery],
            ]);
        }

        return $this;
    }
}
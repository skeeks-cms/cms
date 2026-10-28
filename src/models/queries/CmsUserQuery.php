<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\helpers\PhoneHelper;
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

        if ($condition = PhoneHelper::equalCondition('cmsUserPhones.value', $phone)) {
            return $this->andWhere($condition);
        }

        return $this->andWhere(['cmsUserPhones.value' => $phone]);
    }

    /**
     * Поиск клиента по произвольной строке: ФИО, телефон, email, логин.
     *
     * @param string $word
     * @return $this
     */
    public function search($word = '')
    {
        $words = $this->searchWords($word);
        if (!$words) {
            return $this;
        }

        $this->joinWith("cmsUserPhones as cmsUserPhones");
        $this->joinWith("cmsUserEmails as cmsUserEmails");
        $this->groupBy($this->getPrimaryTableName().'.id');

        $conditions = ['and'];
        foreach ($words as $one) {
            $conditions[] = $this->searchWordCondition($one);
        }

        return $this->andWhere($conditions);
    }

    /**
     * @param string $word
     * @return array
     */
    protected function searchWordCondition($word)
    {
        $table = $this->getPrimaryTableName();

        $condition = [
            'or',
            ['like', 'cmsUserPhones.value', $word],
            ['like', 'cmsUserEmails.value', $word],
            ['like', $table.'.first_name', $word],
            ['like', $table.'.last_name', $word],
            ['like', $table.'.patronymic', $word],
            ['like', $table.'.company_name', $word],
            ['like', $table.'.username', $word],
        ];

        //Телефон в базе лежит в международном формате, а ищут его как придётся
        if ($phone = PhoneHelper::likeCondition('cmsUserPhones.value', $word)) {
            $condition[] = $phone;
        }

        if ($id = $this->searchIdCondition($word)) {
            $condition[] = $id;
        }

        return $condition;
    }


    /**
     * Сотрудники, чьи задачи и лиды доступны пользователю: администратору — все сотрудники,
     * остальным — он сам и его подчиненные (User::getSubordinates()). Для фильтров «Исполнитель».
     *
     * @param User|null $user
     * @return $this
     */
    public function managedBy(User $user = null)
    {
        if ($user === null) {
            $user = \Yii::$app->user->identity;
            $isCanAdmin = \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        } else {
            $isCanAdmin = \Yii::$app->authManager->checkAccess($user->id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        }

        $this->isWorker();

        if (!$user) {
            return $this->andWhere('0=1');
        }

        if (!$isCanAdmin) {
            $ids = \skeeks\cms\models\CmsDepartment::subordinateIds((int)$user->id);
            $ids[] = (int)$user->id;
            $this->andWhere([$this->getPrimaryTableName().'.id' => $ids]);
        }

        return $this;
    }

    /**
     * Поиск компаний доступных пользователю
     *
     * @param User|null $user
     * @return $this
     */
    /**
     * П2д: the clients the employee sees plus client persons of the general pool
     * (no employee of their own and no company that has employees: a person of
     * a company is its data, CrmAccess::inPool()), read only until one takes it
     * into work. For the client list, its card and the API only.
     */
    public function forManagerOrPool(User $user = null)
    {
        if ($user === null) {
            $user = \Yii::$app->user->identity;
            $isCanAdmin = \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        } else {
            $isCanAdmin = \Yii::$app->authManager->checkAccess($user->id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        }
        if (!$user || $isCanAdmin) {
            return $this;
        }
        $managers = [(int)$user->id];
        if ($subordinates = $user->subordinates) {
            $managers = ArrayHelper::merge($managers, ArrayHelper::map($subordinates, "id", "id"));
        }
        $cmsCompanyQuery = CmsCompany::find()->forManager($user)->select(CmsCompany::tableName() . '.id');
        $this->joinWith("companiesAll as companies");
        $this->joinWith("managers as managers");
        $this->andWhere([
            'or',
            ["managers.id" => $managers],
            ["companies.id" => $cmsCompanyQuery],
            ['and', ['managers.id' => null], [$this->getPrimaryTableName().'.is_worker' => 0],
                ['not exists', \skeeks\cms\crm\CrmAccess::personCompanyLinks($this->getPrimaryTableName().'.id')],
                // A person with a history has a main responsible, he is not in the pool.
                ['not', \skeeks\cms\crm\CrmAccess::historyCondition('client', $this->getPrimaryTableName().'.id')]],
        ]);

        return $this;
    }

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

            $cmsCompanyQuery = CmsCompany::find()->forManager($user)->select(CmsCompany::tableName() . '.id');
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

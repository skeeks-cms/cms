<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\models\CmsUserPhone;
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

            // IN over the links, not a join: a company linked to him and to a subordinate is one row
            // (the grid count and the pages equal the distinct companies, model-access-queries).
            $this->andWhere([$this->getPrimaryTableName().'.id' => self::linkedCompanyIds($managers)]);
        }

        return $this;
    }

    /** The companies linked to these employees (cms_company2manager), as a subquery. */
    private static function linkedCompanyIds(array $managerIds): \yii\db\Query
    {
        return (new \yii\db\Query())->select('cms_company_id')->from('{{%cms_company2manager}}')
            ->andWhere(['cms_user_id' => array_values(array_unique(array_map('intval', $managerIds)))]);
    }

    /**
     * The companies the employee sees plus those without any employee (old
     * data, owner's decision 29.10.2026, п. 13а: there is no general pool) —
     * for a head of a department, who gives them a main responsible
     * (crm\CrmAccess::applyClientVisibility(), canSeeOrphan()). For lists, the
     * search of a card and the API only: deals, bills, payments and other
     * records of a company keep forManager().
     */
    public function forManagerOrOrphans(User $user = null)
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
        // Linked to him or his subordinates, or with nobody linked at all — subqueries, not a join (one row per company).
        $table = $this->getPrimaryTableName();
        $this->andWhere(['or',
            [$table.'.id' => self::linkedCompanyIds($managers)],
            ['not exists', (new \yii\db\Query())->select(new \yii\db\Expression('1'))->from(['anyLink' => '{{%cms_company2manager}}'])
                ->where('anyLink.cms_company_id = '.$table.'.id')],
        ]);

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
     * @param string $phone
     * @return $this
     */
    public function phone(string $phone)
    {
        $this->joinWith("phones as phones");
        $this->groupBy($this->getPrimaryTableName().'.id');

        if ($condition = PhoneHelper::equalCondition('phones.value', $phone)) {
            return $this->andWhere($condition);
        }

        return $this->andWhere(['phones.value' => $phone]);
    }

    /**
     * Поиск компании по произвольной строке: название, телефон, email, ИНН,
     * адрес, сайт, контактное лицо.
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

        $this->groupBy($this->getPrimaryTableName().'.id');

        $this->joinWith('addresses as addresses');
        $this->joinWith('emails as emails');
        $this->joinWith('phones as phones');
        $this->joinWith('links as links');
        $this->joinWith('contractors as contractors');
        $this->joinWith('users as users');

        //Контакты сотрудников компании — это ещё два LEFT JOIN, они заметно
        //увеличивают соединение, поэтому подключаются только когда их ищут.
        //Вложенный joinWith тут не годится: он повторно присоединяет cms_user
        //уже без алиаса.
        $withContacts = false;
        foreach ($words as $one) {
            if (PhoneHelper::isSearchablePhone($one) || strpos($one, '@') !== false) {
                $withContacts = true;
                break;
            }
        }

        if ($withContacts) {
            $this->leftJoin(['usersPhones' => CmsUserPhone::tableName()], 'usersPhones.cms_user_id = users.id');
            $this->leftJoin(['usersEmails' => CmsUserEmail::tableName()], 'usersEmails.cms_user_id = users.id');
        }

        $conditions = ['and'];
        foreach ($words as $one) {
            $conditions[] = $this->searchWordCondition($one, $withContacts);
        }

        return $this->andWhere($conditions);
    }

    /**
     * @param string $word
     * @param bool $withContacts подключены ли телефоны и email сотрудников компании
     * @return array
     */
    protected function searchWordCondition($word, $withContacts = false)
    {
        $table = $this->getPrimaryTableName();

        $condition = [
            'or',
            ['like', $table.'.name', $word],
            ['like', $table.'.description', $word],
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
        ];

        $phoneColumns = ['phones.value'];

        if ($withContacts) {
            $condition[] = ['like', 'usersPhones.value', $word];
            $condition[] = ['like', 'usersEmails.value', $word];
            $phoneColumns[] = 'usersPhones.value';
        }

        //Телефон в базе лежит в международном формате, а ищут его как придётся
        foreach ($phoneColumns as $column) {
            if ($phone = PhoneHelper::likeCondition($column, $word)) {
                $condition[] = $phone;
            }
        }

        if ($id = $this->searchIdCondition($word)) {
            $condition[] = $id;
        }

        return $condition;
    }
}
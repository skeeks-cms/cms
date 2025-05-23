<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsUser;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsContractorQuery extends CmsActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return CmsContractor[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return CmsContractor|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @param $type
     * @return CmsContractorQuery
     */
    public function type($type)
    {
        return $this->andWhere(['contractor_type' => $type]);
    }
    /**
     * @return CmsContractorQuery
     */
    public function typeLegal()
    {
        return $this->type(CmsContractor::TYPE_LEGAL);
    }

    /**
     * @return CmsContractorQuery
     */
    public function typeIndividual()
    {
        return $this->type(CmsContractor::TYPE_INDIVIDUAL);
    }


    /**
     * @return CmsContractorQuery
     */
    public function typeIndividualAndLegal()
    {
        return $this->type([
            CmsContractor::TYPE_INDIVIDUAL,
            CmsContractor::TYPE_LEGAL,
        ]);
    }

    /**
     * @param string $q
     * @return CmsContractorQuery
     */
    public function search($q = '')
    {
        return $this->andWhere([
            'or',
            ['like', 'first_name', $q],
            ['like', 'last_name', $q],
            ['like', 'email', $q],
            ['like', 'phone', $q],
            ['like', 'name', $q],
            ['like', 'inn', $q],
        ]);
    }

    /**
     * @param string $inn
     * @return CmsContractorQuery
     */
    public function inn($inn)
    {
        return $this->andWhere([
            'inn' => $inn
        ]);
    }
    
    
    /**
     * @param $value
     * @return CrmContractorQuery
     */
    public function our($value = 1)
    {
        return $this->andWhere(['is_our' => (int) $value]);
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


        //Если нет прав админа, нужно показать только доступные сделки
        if (!$isCanAdmin) {

            $cmsCompanyQuery = CmsCompany::find()->forManager()->select(CmsCompany::tableName() . '.id');
            $cmsUserQuery = CmsUser::find()->forManager()->select(CmsUser::tableName() . '.id');

            $this->joinWith("users as users");
            $this->joinWith("companies as companies");

            //Поиск клиентов с которыми связан сотрудник + все дочерние сотрудники
            $this->andWhere([
                'or',
                //Связь клиентов с менеджерами
                ["companies.id" => $cmsCompanyQuery],
                //Искать конткты по всем доступным компаниям
                ["users.id" => $cmsUserQuery],
            ]);
        }

        return $this;
    }


}
<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
use yii\helpers\ArrayHelper;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsDealQuery extends CmsActiveQuery
{
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

            //Поиск клиентов с которыми связан сотрудник + все дочерние сотрудники
            $this->andWhere([
                'or',
                //Связь клиентов с менеджерами
                ["cms_company_id" => $cmsCompanyQuery],
                //Искать конткты по всем доступным компаниям
                ["cms_user_id" => $cmsUserQuery],
            ]);
        }

        return $this;
    }

    /**
     * Поиск компаний доступных пользователю
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

        $cmsCompanyQuery = CmsCompany::find()->forClient()->select(CmsCompany::tableName() . '.id');

        //Поиск клиентов с которыми связан сотрудник + все дочерние сотрудники
        $this->andWhere([
            'or',
            //Связь клиентов с менеджерами
            ["cms_company_id" => $cmsCompanyQuery],
            //Искать конткты по всем доступным компаниям
            ["cms_user_id" => $user->id],
        ]);

        return $this;
    }
}
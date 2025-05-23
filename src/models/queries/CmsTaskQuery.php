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
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsTaskQuery extends CmsActiveQuery
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

            $this->andWhere([
                "or",
                [self::getPrimaryTableName() . ".cms_project_id" => CmsProject::find()->forManager()->select(CmsProject::tableName() . '.id')],
                [self::getPrimaryTableName() . ".cms_company_id" => CmsCompany::find()->forManager()->select(CmsCompany::tableName() . '.id')],
                [self::getPrimaryTableName() . ".cms_user_id" => CmsUser::find()->forManager()->select(CmsUser::tableName() . '.id')],
                [self::getPrimaryTableName() . ".executor_id" => $user->id],
                [self::getPrimaryTableName() . ".created_by" => $user->id],
            ]);
        }

        return $this;
    }

    /**
     * @param string|array $types
     * @return $this
     */
    public function executor($user)
    {
        $user_id = null;
        
        if ($user instanceof CmsUser) {
            $user_id = $user->id;
        } else {
            $user_id = (int) $user;
        }
        
        $this->andWhere(['executor_id' => $user_id]);
        return $this;
    }
    
    /**
     * @return CmsUserScheduleQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function status($status)
    {
        return $this->andWhere([
            "status" => $status,
        ]);
    }
    
    /**
     * @return CmsUserScheduleQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function statusInWork()
    {
        return $this->status(CmsTask::STATUS_IN_WORK);
    }

    /**
     * Просроченные задачи
     * 
     * @return CmsTaskQuery
     */
    public function expired()
    {
        return $this->andWhere([
            '<', $this->getPrimaryTableName() . '.plan_start_at', time()
        ]);
    }

    /**
     * @param $sort
     * @return CmsTaskQuery
     */
    public function orderPlanStartAt($sort = SORT_ASC)
    {
        return $this->orderBy([$this->getPrimaryTableName() . '.plan_start_at' => $sort]);
    }

}
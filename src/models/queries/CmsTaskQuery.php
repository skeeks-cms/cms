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
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsProject2user;
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

            $condition = [
                "or",
                [self::getPrimaryTableName() . ".cms_project_id" => CmsProject::find()->forManager($user)->select(CmsProject::tableName() . '.id')],
                [self::getPrimaryTableName() . ".cms_company_id" => CmsCompany::find()->forManager($user)->select(CmsCompany::tableName() . '.id')],
                [self::getPrimaryTableName() . ".cms_user_id" => CmsUser::find()->forManager($user)->select(CmsUser::tableName() . '.id')],
                //Свои задачи и задачи подчиненных: исполнитель или постановщик
                [self::getPrimaryTableName() . ".executor_id" => array_values($managers)],
                [self::getPrimaryTableName() . ".created_by" => array_values($managers)],
            ];
            if (static::hasDealColumn()) {
                $condition[] = [self::getPrimaryTableName() . ".cms_deal_id" => CmsDeal::find()->forManager($user)->select(CmsDeal::tableName() . '.id')];
            }
            $this->andWhere($condition);
        }

        return $this;
    }

    /** @var bool|null */
    private static $_hasDealColumn;

    /**
     * cms_task.cms_deal_id exists ("Сделка как услуга", code may run before the migration).
     */
    public static function hasDealColumn(): bool
    {
        if (self::$_hasDealColumn === null) {
            $schema = \Yii::$app->db->getTableSchema(CmsTask::tableName());
            self::$_hasDealColumn = $schema && $schema->getColumn('cms_deal_id') && CmsDealQuery::dealAccessReady();
        }
        return self::$_hasDealColumn;
    }

    /**
     * Tasks a client person sees ("Сделка как услуга"); an internal task (is_internal) never:
     * - a task of a deal: only when "Задачи" is open in the deal and granted to the person at $level,
     *   whoever created it and whatever project it is in;
     * - a task without a deal: the person created it, or (for now) takes part in its project (CmsProject2user).
     * A task linked only to a company or a client, without a deal, is internal work and is never shown.
     *
     * @param User|null $user
     * @param int       $level DealAccess::LEVEL_VIEW to see, LEVEL_ACT to comment
     * @return $this
     */
    public function forClient(User $user = null, int $level = \skeeks\cms\deal\DealAccess::LEVEL_VIEW)
    {
        if ($user === null) {
            $user = \Yii::$app->user->identity;
        }

        if (!$user) {
            return $this;
        }

        if (static::hasDealColumn()) {
            $table = self::getPrimaryTableName();
            //"Внутренняя" closes a task to every client, its author included.
            $this->andWhere([$table.".is_internal" => 0]);
            $this->andWhere([
                "or",
                //A task of a deal: only the rights in that deal decide.
                [$table.".cms_deal_id" => CmsDeal::find()->forClient($user, \skeeks\cms\deal\DealAccess::AREA_TASKS, $level)->select(CmsDeal::tableName().'.id')],
                //Tasks without a deal keep the previous rules for now: own tasks and projects the client takes part in.
                [
                    'and',
                    [$table.".cms_deal_id" => null],
                    [
                        'or',
                        [$table.".created_by" => $user->id],
                        [$table.".cms_project_id" => CmsProject2user::find()->select('cms_project_id')->andWhere(['cms_user_id' => $user->id])],
                    ],
                ],
            ]);
            return $this;
        }

        //Before the migration of "Сделка как услуга": the previous rule.
        $this->andWhere([
            "or",
            [self::getPrimaryTableName().".cms_project_id" => CmsProject::find()->forClient($user)->select(CmsProject::tableName().'.id')],
            [self::getPrimaryTableName().".cms_company_id" => CmsCompany::find()->forClient($user)->select(CmsCompany::tableName().'.id')],
            [self::getPrimaryTableName().".cms_user_id" => $user->id],
            [self::getPrimaryTableName().".created_by" => $user->id],
        ]);

        return $this;
    }

    /**
     * Client-created tasks that still need an executor to be assigned.
     *
     * Access is intentionally not broadened here: callers in the backend must
     * keep forManager() in the query and add this scope to it.
     *
     * @return $this
     */
    public function unassignedFromClients()
    {
        $taskTable = self::getPrimaryTableName();

        return $this
            ->andWhere([$taskTable.'.executor_id' => null])
            ->andWhere(['not', [$taskTable.'.cms_user_id' => null]])
            ->andWhere(new Expression($taskTable.'.created_by = '.$taskTable.'.cms_user_id'))
            ->andWhere([$taskTable.'.status' => [
                CmsTask::STATUS_NEW,
                CmsTask::STATUS_ACCEPTED,
                CmsTask::STATUS_IN_WORK,
                CmsTask::STATUS_ON_PAUSE,
                CmsTask::STATUS_ON_CHECK,
            ]]);
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

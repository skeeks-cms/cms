<?php

namespace skeeks\cms\widgets\admin;

use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\CmsManager;
use yii\base\Widget;

/** Triage queue for client-created tasks without an executor. */
class CmsUnassignedClientTasksWidget extends Widget
{
    /** @var CmsUser */
    public $user;

    public $limit = 20;

    public function run()
    {
        if (!$this->user
            || (int)\Yii::$app->user->id !== (int)$this->user->id
            || !\Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)
        ) {
            return '';
        }

        $query = CmsTask::find()
            ->forManager()
            ->unassignedFromClients()
            ->orderBy([CmsTask::tableName().'.created_at' => SORT_ASC]);
        $count = (int)(clone $query)->count();
        if (!$count) {
            return '';
        }

        return $this->render('unassigned-client-tasks', [
            'count' => $count,
            'tasks' => $query->limit($this->limit)->all(),
        ]);
    }
}

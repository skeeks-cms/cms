<?php

namespace skeeks\cms\support;

use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsProject2manager;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUser2manager;
use yii\db\Expression;

/** Resolves an executor for a task created by a client. */
class SupportTaskExecutorResolver
{
    public function resolve(CmsTask $task, int $clientId): ?int
    {
        $projectId = $task->cms_project_id ? (int)$task->cms_project_id : null;
        $companyId = $task->cms_company_id ? (int)$task->cms_company_id : null;

        if ($projectId) {
            $executorId = $this->leastLoadedWorker(CmsProject2manager::find()
                ->select('cms_user_id')->andWhere(['cms_project_id' => $projectId])->column());
            if ($executorId) {
                return $executorId;
            }

            if (!$companyId) {
                $companyId = (int)CmsProject::find()
                    ->select('cms_company_id')->andWhere(['id' => $projectId])->scalar() ?: null;
            }
        }

        if ($companyId) {
            $executorId = $this->leastLoadedWorker(CmsCompany2manager::find()
                ->select('cms_user_id')->andWhere(['cms_company_id' => $companyId])->column());
            if ($executorId) {
                return $executorId;
            }
        }

        return $this->leastLoadedWorker(CmsUser2manager::find()
            ->select('worker_id')->andWhere(['client_id' => $clientId])->column());
    }

    protected function leastLoadedWorker(array $candidateIds): ?int
    {
        $candidateIds = array_values(array_unique(array_filter(array_map('intval', $candidateIds))));
        if (!$candidateIds) {
            return null;
        }

        $workerIds = CmsUser::find()
            ->select(CmsUser::tableName().'.id')
            ->isWorker()
            ->andWhere([
                CmsUser::tableName().'.id' => $candidateIds,
                CmsUser::tableName().'.is_active' => 1,
            ])
            ->column();
        $workerIds = array_map('intval', $workerIds);
        if (!$workerIds) {
            return null;
        }

        $loadRows = CmsTask::find()
            ->select(['executor_id', 'task_count' => new Expression('COUNT(*)')])
            ->andWhere([
                'executor_id' => $workerIds,
                'status' => [
                    CmsTask::STATUS_NEW,
                    CmsTask::STATUS_ACCEPTED,
                    CmsTask::STATUS_IN_WORK,
                    CmsTask::STATUS_ON_PAUSE,
                    CmsTask::STATUS_ON_CHECK,
                ],
            ])
            ->groupBy('executor_id')
            ->asArray()
            ->all();

        $loads = array_fill_keys($workerIds, 0);
        foreach ($loadRows as $row) {
            $loads[(int)$row['executor_id']] = (int)$row['task_count'];
        }
        usort($workerIds, static function (int $left, int $right) use ($loads): int {
            return $loads[$left] <=> $loads[$right] ?: $left <=> $right;
        });

        return $workerIds[0] ?? null;
    }
}

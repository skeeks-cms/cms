<?php

namespace skeeks\cms\services;

use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsTaskSchedule;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUserSchedule;
use yii\base\Exception;

/** Shared by administration and API; status and time records commit together. */
class TaskWorkflow
{
    public function transition(CmsTask $task, string $status, ?int $endAt = null): array
    {
        $actor = \Yii::$app->user;
        if (!$actor->id) { throw new Exception('Authenticated CMS user is required.'); }
        if (!array_key_exists($status, CmsTask::statuses())) { throw new Exception('Unknown task status.'); }
        return CmsTask::getDb()->transaction(function () use ($task, $status, $endAt, $actor) {
            // Serialize starts on different tasks for the same executor, then reload.
            $db = CmsTask::getDb();
            $executorId = (int)$task->executor_id;
            $db->createCommand('SELECT [[id]] FROM '.CmsUser::tableName().' WHERE [[id]]=:id FOR UPDATE', [':id' => $executorId])->queryScalar();
            $db->createCommand('SELECT [[id]] FROM '.CmsTask::tableName().' WHERE [[id]]=:id FOR UPDATE', [':id' => $task->id])->queryScalar();
            if (!$task->refresh() || (int)$task->executor_id !== $executorId) { throw new Exception('Task changed; read it again.'); }
            $old = $task->status;
            $isExecutor = (int)$actor->id === (int)$task->executor_id;
            $isAuthor = (int)$actor->id === (int)$task->created_by;
            $allowed = $isExecutor;
            if ($status === CmsTask::STATUS_READY) { $allowed = $isAuthor; }
            if ($status === CmsTask::STATUS_CANCELED) { $allowed = $isAuthor || $isExecutor; }
            if ($status === CmsTask::STATUS_ON_PAUSE && in_array($old, [CmsTask::STATUS_ON_CHECK, CmsTask::STATUS_READY], true)) { $allowed = $isAuthor; }
            if ($status === CmsTask::STATUS_ON_PAUSE && $old === CmsTask::STATUS_ON_PAUSE) { $allowed = $isAuthor || $isExecutor; }
            if ($status === CmsTask::STATUS_ON_PAUSE && $old === CmsTask::STATUS_CANCELED) { $allowed = $isAuthor || $isExecutor; }
            if (!$allowed) { throw new Exception('Only the responsible task participant may perform this action.'); }
            $open = CmsTaskSchedule::find()->task($task)->notEnd()->all();
            if ($open && (int)$open[0]->cms_user_id !== $executorId) { throw new Exception('Task interval belongs to another user.'); }
            if ($old === $status) {
                if (($status === CmsTask::STATUS_IN_WORK && count($open) !== 1) || ($status !== CmsTask::STATUS_IN_WORK && $open)) { throw new Exception('Task work intervals are inconsistent.'); }
                if ($status === CmsTask::STATUS_IN_WORK && !$actor->identity->isWorkingNow) { throw new Exception('Employee working time is not running.'); }
                return ['changed' => false, 'work_time_started' => false, 'schedule' => $open ? $open[0] : null];
            }
            if ($status === CmsTask::STATUS_NEW || !array_key_exists($old, CmsTask::statuses())) { throw new Exception('Invalid task status transition.'); }
            if (($old === CmsTask::STATUS_IN_WORK && count($open) !== 1) || ($old !== CmsTask::STATUS_IN_WORK && $open)) { throw new Exception('Task work intervals are inconsistent.'); }
            if ($open && (int)$open[0]->cms_user_id !== $executorId) { throw new Exception('Task interval belongs to another user.'); }
            $started = false;
            $now = time();
            if ($status === CmsTask::STATUS_IN_WORK) {
                if (CmsTaskSchedule::find()->andWhere(['cms_user_id' => $executorId])->notEnd()->exists()) { throw new Exception('Another task interval is already open.'); }
                if (!$actor->identity->isWorkingNow) {
                    $work = new CmsUserSchedule();
                    $work->cms_user_id = $actor->id;
                    $work->start_at = $now;
                    $this->save($work);
                    $started = true;
                }
            }
            $task->status = $status;
            $this->save($task);
            $schedule = $open ? $open[0] : null;
            if ($status === CmsTask::STATUS_IN_WORK) {
                $schedule = new CmsTaskSchedule();
                $schedule->cms_task_id = $task->id;
                $schedule->cms_user_id = $actor->id;
                $schedule->start_at = $now;
                $this->save($schedule);
            } elseif ($old === CmsTask::STATUS_IN_WORK) {
                $schedule->end_at = $endAt ?? $now;
                if ($schedule->end_at > $now) { throw new Exception('Task end time cannot be in the future.'); }
                $this->save($schedule);
            }
            return ['changed' => true, 'work_time_started' => $started, 'schedule' => $schedule];
        });
    }

    /** Narrow administrative recovery; never guesses or fabricates work time. */
    public function repairStatus(CmsTask $task): void
    {
        if (!\Yii::$app->user->can(\skeeks\cms\rbac\CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            throw new Exception('Only an administrator may repair task status.');
        }
        CmsTask::getDb()->transaction(function () use ($task) {
            CmsTask::getDb()->createCommand('SELECT [[id]] FROM '.CmsTask::tableName().' WHERE [[id]]=:id FOR UPDATE', [':id' => $task->id])->queryScalar();
            if (!$task->refresh()) { throw new Exception('Task not found.'); }
            if (array_key_exists($task->status, CmsTask::statuses())) { return; }
            if (CmsTaskSchedule::find()->task($task)->notEnd()->exists()) { throw new Exception('Close or investigate the existing interval before repair.'); }
            $logs = \skeeks\cms\models\CmsLog::find()->andWhere(['model_code' => $task->skeeksModelCode, 'model_id' => $task->id])->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);
            $status = $task->status;
            foreach ($logs->each(100) as $log) {
                $change = $log->data['status'] ?? null;
                if (!$change) { continue; }
                if (($change['value'] ?? null) !== $status || !isset($change['old_value'])) { throw new Exception('Status history is incomplete; manual investigation required.'); }
                $status = $change['old_value'];
                if (array_key_exists($status, CmsTask::statuses())) { break; }
            }
            if (!in_array($status, [CmsTask::STATUS_ACCEPTED, CmsTask::STATUS_ON_PAUSE], true)) { throw new Exception('History does not establish a safe inactive status.'); }
            $task->status = $status;
            // Deliberately bypass transition validation only for verified corrupt history.
            // Keep normal ActiveRecord events, audit trail and priority recalculation.
            if (!$task->save(false, ['status'])) { throw new Exception('Status repair failed.'); }
            $task->refresh();
        });
    }

    protected function save($model): void
    {
        if (!$model->save()) { throw new Exception(implode('; ', $model->getFirstErrors())); }
    }
}

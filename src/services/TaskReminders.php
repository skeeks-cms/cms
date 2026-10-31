<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\services;

use skeeks\cms\models\CmsTask;

/**
 * Sends the reminders of tasks whose time came (owner's decision 31.10.2026): an important notification
 * `task.reminder` to the executor and, when asked, to the author; the notification system delivers it to the
 * channels each person chose (CRM, MAX, Telegram, mail). Every reminder goes once: the row is claimed by a
 * conditional update before notifying, so parallel runs never send it twice.
 */
class TaskReminders
{
    /** A reminder older than this is not sent any more (the site was off): it is only marked. */
    public const STALE_SECONDS = 86400;

    /**
     * @return int reminders sent
     */
    public function sendDue(?int $now = null): int
    {
        if (!TaskPlanner::hasReminder()) {
            return 0;
        }
        $now = $now ?? time();

        CmsTask::updateAll(['reminded_at' => $now], ['and',
            ['reminded_at' => null],
            ['<', 'remind_at', $now - static::STALE_SECONDS],
        ]);

        $tasks = CmsTask::find()
            ->andWhere(['reminded_at' => null, 'status' => CmsTask::activeStatuses()])
            ->andWhere(['<=', 'remind_at', $now])
            ->orderBy(['remind_at' => SORT_ASC])
            ->limit(500)
            ->all();

        $sent = 0;
        foreach ($tasks as $task) {
            $claimed = CmsTask::updateAll(['reminded_at' => $now], [
                'id'          => (int)$task->id,
                'reminded_at' => null,
                'remind_at'   => (int)$task->remind_at,
            ]);
            if (!$claimed) {
                continue;
            }
            try {
                $this->notify($task);
                $sent++;
            } catch (\Throwable $e) {
                \Yii::error('Task reminder '.(int)$task->id.' failed: '.$e->getMessage(), self::class);
            }
        }

        return $sent;
    }

    public function notify(CmsTask $task): void
    {
        $userIds = [(int)$task->executor_id];
        if ($task->remind_author && $task->created_by) {
            $userIds[] = (int)$task->created_by;
        }
        $f = \Yii::$app->formatter;
        $start = (int)$task->plan_start_at;
        $text = 'Напоминание: '.($start ? 'задача на '.$f->asTime($start, 'php:H:i').($f->asDate($start, 'php:Y-m-d') !== $f->asDate(time(), 'php:Y-m-d') ? ' '.$f->asDate($start, 'php:j F') : '') : 'задача');

        \Yii::$app->notifications->notify(array_values(array_unique(array_filter($userIds))), 'task.reminder', [
            'name'     => $text,
            'model'    => $task,
            'eventKey' => 'task.reminder:'.(int)$task->id.':'.(int)$task->remind_at,
        ]);
    }
}

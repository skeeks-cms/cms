<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\services;

use skeeks\cms\models\CmsTask;

/**
 * Sends the reminders of tasks whose time came (owner's decisions 31.10.2026): an important notification to the
 * executor and, when asked, to the author; the notification system delivers it to the channels each person chose
 * (CRM, MAX, Telegram, mail). A reminder a person set for themself (`remind_by`) is the kind `task.reminder_self`
 * and comes during their quiet hours too; one set by someone else (`task.reminder`) waits for them to end.
 * A reminder the check missed (the site or its queue was down) is not skipped: it goes with «опоздало на N мин».
 * Every reminder goes exactly once: the row is claimed by a conditional update before notifying, so a repeated
 * or a parallel run never sends it twice.
 */
class TaskReminders
{
    /** A reminder later than this is marked «опоздало». */
    public const LATE_SECONDS = 120;

    /**
     * @return int reminders sent
     */
    public function sendDue(?int $now = null): int
    {
        if (!TaskPlanner::hasReminder()) {
            return 0;
        }
        $now = $now ?? time();

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
                $this->notify($task, $now);
                $sent++;
            } catch (\Throwable $e) {
                \Yii::error('Task reminder '.(int)$task->id.' failed: '.$e->getMessage(), self::class);
            }
        }

        return $sent;
    }

    public function notify(CmsTask $task, ?int $now = null): void
    {
        $now = $now ?? time();
        $userIds = [(int)$task->executor_id];
        if ($task->remind_author && $task->created_by) {
            $userIds[] = (int)$task->created_by;
        }
        $f = \Yii::$app->formatter;
        $start = (int)$task->plan_start_at;
        $text = 'Напоминание: '.($start ? 'задача на '.$f->asTime($start, 'php:H:i').($f->asDate($start, 'php:Y-m-d') !== $f->asDate($now, 'php:Y-m-d') ? ' '.$f->asDate($start, 'php:j F') : '') : 'задача');
        $late = $now - (int)$task->remind_at;
        if ($late > static::LATE_SECONDS) {
            $text .= ' (опоздало на '.static::lateText($late).')';
        }
        $setBy = $task->hasAttribute('remind_by') ? (int)$task->remind_by : 0;

        foreach (array_values(array_unique(array_filter($userIds))) as $userId) {
            \Yii::$app->notifications->notify($userId, $userId === $setBy ? 'task.reminder_self' : 'task.reminder', [
                'name'     => $text,
                'model'    => $task,
                'eventKey' => 'task.reminder:'.(int)$task->id.':'.(int)$task->remind_at,
            ]);
        }
    }

    /** «7 мин», «2 ч 5 мин», «3 дн». */
    public static function lateText(int $seconds): string
    {
        $minutes = (int)ceil($seconds / 60);
        if ($minutes < 60) {
            return $minutes.' мин';
        }
        if ($minutes < 24 * 60) {
            return intdiv($minutes, 60).' ч'.($minutes % 60 ? ' '.($minutes % 60).' мин' : '');
        }

        return intdiv($minutes, 24 * 60).' дн';
    }
}

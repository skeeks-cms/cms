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
 * Every reminder goes exactly once: in one transaction the row is read again with a lock (a task closed or moved
 * after the selection is not reminded), marked sent and the notification stored with its deliveries. A repeated or
 * a parallel run never sends it twice; a failure before the notification is stored rolls the mark back, and the
 * next run sends it (late — with «опоздало»).
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
            try {
                if ($this->sendOne((int)$task->id, $now)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                \Yii::error('Task reminder '.(int)$task->id.' failed, it will be sent by the next run: '.$e->getMessage(), self::class);
            }
        }

        return $sent;
    }

    /**
     * Sends one reminder if it is still due: the task active, not reminded, its time come. The mark and the
     * notification commit together.
     */
    protected function sendOne(int $id, int $now): bool
    {
        $db = CmsTask::getDb();

        return (bool)$db->transaction(function () use ($db, $id, $now) {
            $condition = ['and',
                ['id' => $id, 'reminded_at' => null, 'status' => CmsTask::activeStatuses()],
                ['<=', 'remind_at', $now],
            ];
            // A locking read sees the latest committed row and waits for a change in progress (a closing).
            $command = (new \yii\db\Query())->select('id')->from(CmsTask::tableName())->where($condition)->createCommand($db);
            if ($db->driverName === 'mysql') {
                $command = $db->createCommand($command->getSql().' FOR UPDATE', $command->params);
            }
            if (!$command->queryScalar()) {
                return false;
            }
            $task = CmsTask::findOne($id);
            if (!$task || !CmsTask::updateAll(['reminded_at' => $now], $condition)) {
                return false;
            }
            $this->notify($task, $now);

            return true;
        });
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

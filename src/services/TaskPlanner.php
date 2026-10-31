<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\services;

use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsTaskSchedule;
use skeeks\cms\models\CmsUser;
use yii\db\Connection;
use yii\db\Expression;

/**
 * One place that gives every active task of an executor its start (owner's decision 31.10.2026).
 *
 * - A fixed task (`is_fixed_start`) keeps the time a person chose; it takes that time out of the schedule.
 * - A task in work keeps its actual start and takes the time it still needs from now.
 * - Auto tasks go in their queue order (`executor_sort`) into the nearest free time of the executor's
 *   work schedule, after one another, a task longer than a free window continuing in the next one.
 *
 * Writes go through updateAll(): no history records, no afterSave() and no recursion.
 *
 * Locks: every change of a task takes the layout lock of its executor before it reads the queue
 * (CmsTask::beforeSave(), TaskWorkflow::transition(), plan()). Inside a transaction the lock is held until the
 * transaction ends, and plan() reads the queue with a locking read: the next layout always sees the committed
 * result of the previous one, two tasks created at once never get the same time.
 */
class TaskPlanner
{
    /** Starts are put on a 5 minute grid. */
    public const STEP = 300;

    /** How far ahead free time is looked for. */
    public const HORIZON_DAYS = 730;

    /** Schedule of an executor who has none: weekdays 9–18. */
    public const DEFAULT_SCHEDULE = [['day' => [1, 2, 3, 4, 5], 'startHour' => '09', 'startMinutes' => '00', 'endHour' => '18', 'endMinutes' => '00']];

    /** A move of an auto task people are told about (another day is always told). */
    public const NOTICEABLE_MOVE = 1800;

    /** Seconds a change waits for the layout lock of an executor. */
    public const LOCK_TIMEOUT = 10;

    /** What a person is told when the lock was not given: the change is refused, not saved half-laid-out. */
    public const BUSY_MESSAGE = 'Расписание сотрудника сейчас пересчитывается — повторите через несколько секунд.';

    /** @var int|null "now" of a test */
    public $now;

    /** @var true[] layout locks held until the current transaction ends: lock name => true */
    protected static $held = [];

    /**
     * Lays out the active tasks of one executor.
     *
     * @param int[] $sortTaskIds auto tasks to put first, in this order (a manual reorder)
     */
    public function plan(CmsUser $user, array $sortTaskIds = []): void
    {
        // One layout of an executor at a time: two changes at once must not give two different layouts.
        // A busy lock is an error of the operation (rolled back with it), never a silently skipped layout.
        $name = static::lockName((int)$user->id);
        $wasHeld = isset(static::$held[$name]);
        if (!static::lockExecutors([(int)$user->id])) {
            throw new \yii\base\Exception(static::BUSY_MESSAGE);
        }

        try {
            $placements = $this->layout($user, $this->loadTasks((int)$user->id, true), $sortTaskIds);
            foreach ($placements as $id => $values) {
                CmsTask::updateAll($values, ['id' => $id]);
            }
        } finally {
            // Outside a transaction the lock is released here; inside one — when it ends.
            if (!$wasHeld && !isset(static::$held[$name])) {
                static::release($name);
            }
        }
    }

    /**
     * Takes the layout locks of executors, in the order of their ids (two changes never wait for each other).
     * Inside a transaction a lock is held until it is committed or rolled back; outside one only plan() takes it
     * and releases it itself.
     *
     * @return bool false when a lock was not given within LOCK_TIMEOUT seconds
     */
    public static function lockExecutors(array $userIds): bool
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        sort($ids);
        $db = CmsTask::getDb();
        $transaction = $db->getTransaction();
        foreach ($ids as $id) {
            $name = static::lockName($id);
            if (isset(static::$held[$name])) {
                continue;
            }
            if (!static::acquire($name)) {
                return false;
            }
            if ($transaction && $transaction->isActive) {
                if (!static::$held) {
                    $db->on(Connection::EVENT_COMMIT_TRANSACTION, [static::class, 'releaseHeld']);
                    $db->on(Connection::EVENT_ROLLBACK_TRANSACTION, [static::class, 'releaseHeld']);
                }
                static::$held[$name] = true;
            }
        }

        return true;
    }

    /** Releases the locks held by the transaction that has just ended. */
    public static function releaseHeld($event = null): void
    {
        $db = CmsTask::getDb();
        $db->off(Connection::EVENT_COMMIT_TRANSACTION, [static::class, 'releaseHeld']);
        $db->off(Connection::EVENT_ROLLBACK_TRANSACTION, [static::class, 'releaseHeld']);
        $names = array_keys(static::$held);
        static::$held = [];
        foreach ($names as $name) {
            static::release($name);
        }
    }

    protected static function lockName(int $userId): string
    {
        return 'cms-task-plan-'.$userId;
    }

    /** A database lock of MySQL/MariaDB (the site has no mutex component), else the mutex when there is one. */
    protected static function acquire(string $name): bool
    {
        $db = CmsTask::getDb();
        if ($db->driverName === 'mysql') {
            return (int)$db->createCommand('SELECT GET_LOCK(:name, :timeout)', [':name' => $name, ':timeout' => static::LOCK_TIMEOUT])->queryScalar() === 1;
        }

        return !\Yii::$app->has('mutex') || \Yii::$app->mutex->acquire($name, static::LOCK_TIMEOUT);
    }

    protected static function release(string $name): void
    {
        $db = CmsTask::getDb();
        if ($db->driverName === 'mysql') {
            $db->createCommand('SELECT RELEASE_LOCK(:name)', [':name' => $name])->execute();
        } elseif (\Yii::$app->has('mutex')) {
            \Yii::$app->mutex->release($name);
        }
    }

    /**
     * Lays out the tasks of every executor with active tasks (a periodic run and the move of old tasks).
     *
     * @return int executors laid out
     */
    public function planAll(): int
    {
        $ids = CmsTask::find()->select('executor_id')->distinct()
            ->andWhere(['status' => CmsTask::activeStatuses()])
            ->andWhere(['not', ['executor_id' => null]])
            ->column();

        $count = 0;
        foreach (CmsUser::find()->andWhere(['id' => $ids])->each(50) as $user) {
            $this->plan($user);
            $count++;
        }

        return $count;
    }

    /**
     * Start and end of a new auto task of $duration seconds put at the end of the executor's queue.
     *
     * @return int[] [start, end]
     */
    public function estimate(CmsUser $user, int $duration, ?int $excludeTaskId = null): array
    {
        $tasks = $this->loadTasks((int)$user->id);
        // An auto task being edited keeps its place in the queue, with the new duration.
        if ($excludeTaskId && isset($tasks[$excludeTaskId]) && !$this->isFixed($tasks[$excludeTaskId])
            && $tasks[$excludeTaskId]['status'] !== CmsTask::STATUS_IN_WORK) {
            $tasks[$excludeTaskId]['plan_duration'] = max(0, $duration) + (int)$tasks[$excludeTaskId]['worked'];
            $placement = $this->layout($user, $tasks, [], true)[$excludeTaskId];

            return [(int)$placement['plan_start_at'], (int)$placement['plan_end_at']];
        }
        if ($excludeTaskId) {
            unset($tasks[$excludeTaskId]);
        }
        $tasks[0] = [
            'id'             => 0,
            'status'         => CmsTask::STATUS_ACCEPTED,
            'is_fixed_start' => 0,
            'plan_start_at'  => null,
            'plan_duration'  => max(0, $duration),
            'executor_sort'  => PHP_INT_MAX,
            'worked'         => 0,
            'open_start'     => null,
        ];

        $placement = $this->layout($user, $tasks, [], true)[0];

        return [(int)$placement['plan_start_at'], (int)$placement['plan_end_at']];
    }

    /**
     * The nearest time from $notBefore where the executor has $duration seconds free in one piece
     * (a call or a reply a package asks for: proposals, mailings, link visits). Without such a window
     * within the horizon — the nearest free minute.
     */
    public function nearestStart(?CmsUser $user, int $duration, ?int $notBefore = null, ?int $excludeTaskId = null): int
    {
        $from = $this->alignUp(max($this->now(), (int)$notBefore));
        $tasks = $user ? $this->loadTasks((int)$user->id) : [];
        unset($tasks[(int)$excludeTaskId]);
        $busy = $this->busyIntervals($tasks, $from);
        $first = null;
        foreach ($this->freeIntervals($this->schedule($user), $from, $busy) as [$a, $b]) {
            $a = $this->alignUp($a);
            if ($a >= $b) {
                continue;
            }
            if ($first === null) {
                $first = $a;
            }
            if ($b - $a >= $duration) {
                return $a;
            }
        }

        return $first ?? $from;
    }

    /**
     * What a person should know about a fixed time: it is outside the working schedule or overlaps other
     * fixed tasks and tasks in work. Such a time is allowed (owner's decision), only warned about.
     *
     * @return string[]
     */
    public function warnings(CmsTask $task): array
    {
        if (!$task->plan_start_at || !$task->executor_id) {
            return [];
        }
        $start = (int)$task->plan_start_at;
        $end = $start + max((int)$task->plan_duration, static::STEP);
        $user = CmsUser::findOne((int)$task->executor_id);
        $warnings = [];

        $inSchedule = false;
        foreach ($this->freeIntervals($this->schedule($user), $start, []) as [$a, $b]) {
            if ($a > $start) {
                break;
            }
            $inSchedule = $b >= $end;
            break;
        }
        if (!$inSchedule) {
            $warnings[] = 'Время вне рабочего графика исполнителя.';
        }

        $tasks = $this->loadTasks((int)$task->executor_id);
        unset($tasks[(int)$task->id]);
        $names = [];
        foreach ($tasks as $id => $other) {
            if (!$this->isFixed($other) && $other['status'] !== CmsTask::STATUS_IN_WORK) {
                continue;
            }
            $a = (int)$other['plan_start_at'];
            $b = $a + max((int)$other['plan_duration'], static::STEP);
            if ($a < $end && $b > $start) {
                $names[] = $id;
            }
        }
        if ($names) {
            $titles = CmsTask::find()->select('name')->andWhere(['id' => array_slice($names, 0, 3)])->column();
            $warnings[] = 'В это время уже есть: «'.implode('», «', $titles).'»'.(count($names) > 3 ? ' и ещё '.(count($names) - 3) : '').'.';
        }

        return $warnings;
    }

    /**
     * @param bool $forUpdate a locking read (MySQL/MariaDB): the latest committed rows even inside a transaction
     *                        that began earlier, and no change of them until the layout is written
     *
     * @return array id => task row with `worked` (seconds of work done), `open_start` (start of an open interval)
     *               and `first_start` (when the work on it first started)
     */
    protected function loadTasks(int $userId, bool $forUpdate = false): array
    {
        $scheduleTable = CmsTaskSchedule::tableName();
        $taskTable = CmsTask::tableName();

        $select = [
            $taskTable.'.id',
            $taskTable.'.status',
            $taskTable.'.plan_start_at',
            $taskTable.'.plan_duration',
            $taskTable.'.plan_end_at',
            $taskTable.'.executor_sort',
            $taskTable.'.executor_end_at',
            'worked'     => (new \yii\db\Query())->select(new Expression('SUM(end_at - start_at)'))->from($scheduleTable)
                ->where(['cms_task_id' => new Expression($taskTable.'.id')])->andWhere(['not', ['end_at' => null]]),
            'open_start' => (new \yii\db\Query())->select(new Expression('MIN(start_at)'))->from($scheduleTable)
                ->where(['cms_task_id' => new Expression($taskTable.'.id'), 'end_at' => null]),
            'first_start' => (new \yii\db\Query())->select(new Expression('MIN(start_at)'))->from($scheduleTable)
                ->where(['cms_task_id' => new Expression($taskTable.'.id')]),
        ];
        if (static::hasStartMode()) {
            $select[] = $taskTable.'.is_fixed_start';
        }
        if (static::hasReminder()) {
            $select[] = $taskTable.'.remind_offset';
            $select[] = $taskTable.'.remind_at';
        }

        $db = CmsTask::getDb();
        $command = (new \yii\db\Query())->select($select)->from($taskTable)
            ->where(['executor_id' => $userId, 'status' => CmsTask::activeStatuses()])
            ->orderBy([new Expression('CASE WHEN '.$taskTable.'.executor_sort IS NULL THEN 1 ELSE 0 END'), $taskTable.'.executor_sort' => SORT_ASC, $taskTable.'.id' => SORT_DESC])
            ->createCommand($db);
        if ($forUpdate && $db->driverName === 'mysql') {
            $command = $db->createCommand($command->getSql().' FOR UPDATE', $command->params);
        }
        $rows = $command->queryAll();

        $result = [];
        foreach ($rows as $row) {
            if (!array_key_exists('is_fixed_start', $row)) {
                $row['is_fixed_start'] = $row['plan_start_at'] ? 1 : 0;
            }
            $result[(int)$row['id']] = $row;
        }

        return $result;
    }

    /**
     * @return array id => changed attributes
     */
    protected function layout(CmsUser $user, array $tasks, array $sortTaskIds, bool $all = false): array
    {
        $now = $this->now();
        $from = $this->alignUp($now);

        $inWork = [];
        $auto = [];
        foreach ($tasks as $id => $task) {
            if ($task['status'] === CmsTask::STATUS_IN_WORK) {
                $inWork[$id] = $task;
            } elseif (!$this->isFixed($task)) {
                $auto[$id] = $task;
            }
        }

        if ($sortTaskIds) {
            $first = [];
            foreach ($sortTaskIds as $id) {
                $id = (int)$id;
                if (isset($auto[$id])) {
                    $first[$id] = $auto[$id];
                    unset($auto[$id]);
                }
            }
            $auto = $first + $auto;
        }

        $result = [];
        $sort = 0;

        foreach ($tasks as $id => $task) {
            if ($this->isFixed($task) && $task['status'] !== CmsTask::STATUS_IN_WORK) {
                $end = (int)$task['plan_start_at'] + (int)$task['plan_duration'];
                $this->put($result, $task, ['plan_end_at' => $end, 'executor_end_at' => $end], $all);
            }
        }

        // A task in work does not move: it is fixed on when the work first started (owner's decision 3; set by
        // TaskWorkflow when it starts), it needs the rest of its time from now.
        $cursor = $from;
        foreach ($inWork as $id => $task) {
            $start = (int)($task['first_start'] ?: ($task['open_start'] ?: ($task['plan_start_at'] ?: $now)));
            $end = max($now, $start) + $this->remaining($task);
            $cursor = max($cursor, $this->alignUp($end));
            $values = [
                'plan_start_at'   => $start,
                'plan_end_at'     => $end,
                'executor_end_at' => $end,
                'executor_sort'   => ++$sort,
            ];
            if (static::hasStartMode()) {
                $values['is_fixed_start'] = 1;
            }
            $this->put($result, $task, $values, $all);
        }

        $busy = $this->busyIntervals($tasks, $from);
        $free = $this->freeIntervals($this->schedule($user), $cursor, $busy);
        $piece = $free->current();
        $pos = $piece ? $piece[0] : $cursor;

        foreach ($auto as $id => $task) {
            $need = $this->remaining($task);
            $start = null;
            $end = $pos;
            while ($piece) {
                if ($start === null) {
                    $pos = $this->alignUp($pos);
                }
                if ($pos >= $piece[1]) {
                    $free->next();
                    $piece = $free->valid() ? $free->current() : null;
                    $pos = $piece ? max($pos, $piece[0]) : $pos;
                    continue;
                }
                if ($start === null) {
                    $start = $pos;
                }
                $take = min($need, $piece[1] - $pos);
                $pos += $take;
                $need -= $take;
                $end = $pos;
                if ($need <= 0) {
                    break;
                }
            }
            if ($start === null) {
                // No working time within the horizon: right after the previous task.
                $start = $this->alignUp($pos);
                $end = $start + $need;
                $pos = $end;
            }

            $values = [
                'plan_start_at'   => $start,
                'plan_end_at'     => $end,
                'executor_end_at' => $end,
                'executor_sort'   => ++$sort,
            ];
            // A noticeable move (another day or half an hour and more) is shown to people with the former start.
            $old = (int)$task['plan_start_at'];
            if (!$all && static::hasReplanned() && $old && $old !== $start
                && (abs($start - $old) >= static::NOTICEABLE_MOVE || $this->dayOf($start) !== $this->dayOf($old))) {
                $values['replanned_from'] = $old;
                $values['replanned_at'] = $this->now();
            }
            $this->put($result, $task, $values, $all);
        }

        return $result;
    }

    protected function put(array &$result, array $task, array $values, bool $all): void
    {
        // A reminder «N minutes before the start» moves with the start; a moved one is sent again. A started task
        // keeps its reminder: its start is now when the work started, not a time to remind of.
        $moves = isset($values['plan_start_at']) && $task['status'] !== CmsTask::STATUS_IN_WORK;
        if ($moves && isset($task['remind_offset']) && $task['remind_offset'] !== null) {
            $remindAt = (int)$values['plan_start_at'] - (int)$task['remind_offset'];
            if ((int)$task['remind_at'] !== $remindAt) {
                $values['remind_at'] = $remindAt;
                $values['reminded_at'] = null;
            }
        } elseif ($moves && !empty($task['remind_at']) && $task['plan_start_at']
            && (int)$values['plan_start_at'] !== (int)$task['plan_start_at']) {
            // A reminder of its own time moves with the task too, keeping its distance to the start; its author
            // (`remind_by`) stays — this is not a new choice of a person.
            $remindAt = CmsTask::followedReminderAt((int)$task['remind_at'], (int)$values['plan_start_at'] - (int)$task['plan_start_at'], $this->now());
            if ($remindAt !== (int)$task['remind_at']) {
                $values['remind_at'] = $remindAt;
                if ($remindAt > $this->now()) {
                    $values['reminded_at'] = null;
                }
            }
        }
        if (!$all) {
            foreach ($values as $name => $value) {
                if (array_key_exists($name, $task) && $task[$name] !== null && (int)$task[$name] === (int)$value) {
                    unset($values[$name]);
                }
            }
        }
        if ($values) {
            $result[(int)$task['id']] = $values;
        }
    }

    /**
     * Time taken by fixed tasks and tasks in work, from $from on, sorted.
     *
     * @return int[][]
     */
    protected function busyIntervals(array $tasks, int $from): array
    {
        $now = $this->now();
        $busy = [];
        foreach ($tasks as $task) {
            if ($task['status'] === CmsTask::STATUS_IN_WORK) {
                $busy[] = [$now, $now + $this->remaining($task)];
            } elseif ($this->isFixed($task)) {
                $start = (int)$task['plan_start_at'];
                $busy[] = [$start, $start + max(0, (int)$task['plan_duration'] - (int)$task['worked'])];
            }
        }

        $busy = array_values(array_filter($busy, static function ($b) use ($from) {
            return $b[1] > $from && $b[1] > $b[0];
        }));
        usort($busy, static function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        return $busy;
    }

    /**
     * Free pieces of working time from $from on: the schedule periods minus the busy intervals.
     */
    protected function freeIntervals(array $schedule, int $from, array $busy): \Generator
    {
        $tz = new \DateTimeZone(\Yii::$app->formatter->timeZone);
        $day = (new \DateTime('@'.$from))->setTimezone($tz);
        $day->setTime(0, 0);

        for ($i = 0; $i <= static::HORIZON_DAYS; $i++) {
            $periods = CmsScheduleHelper::getSchedulesByWorktimeForDate($schedule, $day->format('Y-m-d'));
            usort($periods, static function ($a, $b) {
                return (int)$a->start_at <=> (int)$b->start_at;
            });
            foreach ($periods as $period) {
                $a = max((int)$period->start_at, $from);
                $b = (int)$period->end_at;
                if ($b <= $a) {
                    continue;
                }
                foreach ($busy as [$x, $y]) {
                    if ($y <= $a || $x >= $b) {
                        continue;
                    }
                    if ($x > $a) {
                        yield [$a, $x];
                    }
                    $a = max($a, $y);
                    if ($a >= $b) {
                        break;
                    }
                }
                if ($a < $b) {
                    yield [$a, $b];
                }
            }
            $day->modify('+1 day');
        }
    }

    protected function schedule(?CmsUser $user): array
    {
        $schedule = $user && is_array($user->work_shedule) ? array_values(array_filter($user->work_shedule, static function ($row) {
            return !empty($row['day']);
        })) : [];

        return $schedule ?: static::DEFAULT_SCHEDULE;
    }

    protected function remaining(array $task): int
    {
        return max(0, (int)$task['plan_duration'] - (int)$task['worked']);
    }

    protected function isFixed(array $task): bool
    {
        return (bool)$task['is_fixed_start'] && $task['plan_start_at'];
    }

    protected function alignUp(int $time): int
    {
        return (int)(ceil($time / static::STEP) * static::STEP);
    }

    protected function now(): int
    {
        return $this->now ?? time();
    }

    protected function dayOf(int $time): string
    {
        return \Yii::$app->formatter->asDate($time, 'php:Y-m-d');
    }

    /**
     * Whether the table has the columns of a noticed move yet (code may run before its migration).
     */
    public static function hasReplanned(): bool
    {
        static $has = null;
        if ($has === null) {
            $schema = CmsTask::getTableSchema();
            $has = $schema && $schema->getColumn('replanned_from') !== null;
        }

        return $has;
    }

    /**
     * Whether the table has the reminder columns yet (code may run before its migration).
     */
    public static function hasReminder(): bool
    {
        static $has = null;
        if ($has === null) {
            $schema = CmsTask::getTableSchema();
            $has = $schema && $schema->getColumn('remind_at') !== null;
        }

        return $has;
    }

    /**
     * Whether the table has the start mode yet (code may run before its migration).
     */
    public static function hasStartMode(): bool
    {
        static $has = null;
        if ($has === null) {
            $schema = CmsTask::getTableSchema();
            $has = $schema && $schema->getColumn('is_fixed_start') !== null;
        }

        return $has;
    }
}

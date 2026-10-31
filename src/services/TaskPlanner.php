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
 */
class TaskPlanner
{
    /** Starts are put on a 5 minute grid. */
    public const STEP = 300;

    /** How far ahead free time is looked for. */
    public const HORIZON_DAYS = 730;

    /** Schedule of an executor who has none: weekdays 9–18. */
    public const DEFAULT_SCHEDULE = [['day' => [1, 2, 3, 4, 5], 'startHour' => '09', 'startMinutes' => '00', 'endHour' => '18', 'endMinutes' => '00']];

    /** @var int|null "now" of a test */
    public $now;

    /**
     * Lays out the active tasks of one executor.
     *
     * @param int[] $sortTaskIds auto tasks to put first, in this order (a manual reorder)
     */
    public function plan(CmsUser $user, array $sortTaskIds = []): void
    {
        $mutex = \Yii::$app->has('mutex') ? \Yii::$app->mutex : null;
        $lock = 'cms-task-plan-'.(int)$user->id;
        if ($mutex && !$mutex->acquire($lock, 10)) {
            \Yii::warning('Task plan of user '.(int)$user->id.' is busy, skipped.', self::class);
            return;
        }

        try {
            $placements = $this->layout($user, $this->loadTasks((int)$user->id), $sortTaskIds);
            foreach ($placements as $id => $values) {
                CmsTask::updateAll($values, ['id' => $id]);
            }
        } finally {
            if ($mutex) {
                $mutex->release($lock);
            }
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
    public function nearestStart(?CmsUser $user, int $duration, ?int $notBefore = null): int
    {
        $from = $this->alignUp(max($this->now(), (int)$notBefore));
        $busy = $user ? $this->busyIntervals($this->loadTasks((int)$user->id), $from) : [];
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
     * @return array id => task row with `worked` (seconds of work done) and `open_start` (start of an open interval)
     */
    protected function loadTasks(int $userId): array
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
        ];
        if (static::hasStartMode()) {
            $select[] = $taskTable.'.is_fixed_start';
        }

        $rows = (new \yii\db\Query())->select($select)->from($taskTable)
            ->where(['executor_id' => $userId, 'status' => CmsTask::activeStatuses()])
            ->orderBy([new Expression('CASE WHEN '.$taskTable.'.executor_sort IS NULL THEN 1 ELSE 0 END'), $taskTable.'.executor_sort' => SORT_ASC, $taskTable.'.id' => SORT_DESC])
            ->all(CmsTask::getDb());

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

        // A task in work does not move: its start is when the work started, it needs the rest of its time from now.
        $cursor = $from;
        foreach ($inWork as $id => $task) {
            $start = $this->isFixed($task) ? (int)$task['plan_start_at'] : (int)($task['open_start'] ?: ($task['plan_start_at'] ?: $now));
            $end = max($now, $start) + $this->remaining($task);
            $cursor = max($cursor, $this->alignUp($end));
            $this->put($result, $task, [
                'plan_start_at'   => $start,
                'plan_end_at'     => $end,
                'executor_end_at' => $end,
                'executor_sort'   => ++$sort,
            ], $all);
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

            $this->put($result, $task, [
                'plan_start_at'   => $start,
                'plan_end_at'     => $end,
                'executor_end_at' => $end,
                'executor_sort'   => ++$sort,
            ], $all);
        }

        return $result;
    }

    protected function put(array &$result, array $task, array $values, bool $all): void
    {
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

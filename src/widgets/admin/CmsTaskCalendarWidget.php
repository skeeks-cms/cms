<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\assets\admin\TaskCalendarAsset;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\services\TaskPlanner;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Calendar of an employee's tasks by their start: a month grid, a week or a day on an hour scale.
 * Every day is drawn, empty and days off included; the current period comes from GET `mode` and `date`.
 */
class CmsTaskCalendarWidget extends Widget
{
    public const MODE_MONTH = 'month';
    public const MODE_WEEK = 'week';
    public const MODE_DAY = 'day';

    /** @var CmsUser */
    public $user;

    /** @var string month|week|day */
    public $mode;

    /** @var string Y-m-d of a day of the shown period */
    public $date;

    /** @var int tasks shown in a month cell before «ещё N» */
    public $monthCellLimit = 4;

    public $options = [];

    /** @var \DateTimeZone */
    public $timeZone;

    public function init()
    {
        parent::init();
        $request = \Yii::$app->request;
        $this->mode = $this->mode ?: (string)$request->get('mode', self::MODE_MONTH);
        if (!in_array($this->mode, [self::MODE_MONTH, self::MODE_WEEK, self::MODE_DAY], true)) {
            $this->mode = self::MODE_MONTH;
        }
        $this->timeZone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
        $date = $this->date ?: (string)$request->get('date', '');
        $this->date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : $this->today();
    }

    public function run()
    {
        TaskCalendarAsset::register($this->view);

        [$from, $to] = $this->range();
        $days = [];
        for ($day = clone $from; $day < $to; $day->modify('+1 day')) {
            $days[] = $day->format('Y-m-d');
        }

        $this->options['id'] = $this->id;
        Html::addCssClass($this->options, ['sx-task-calendar', 'sx-task-calendar--'.$this->mode]);

        return $this->render('task-calendar', [
            'widget' => $this,
            'days'   => $days,
            'tasks'  => $this->tasksByDay($from->getTimestamp(), $to->getTimestamp()),
        ]);
    }

    public function today(): string
    {
        return (new \DateTime('now', $this->timeZone))->format('Y-m-d');
    }

    public function day(string $date): \DateTime
    {
        return new \DateTime($date.' 00:00:00', $this->timeZone);
    }

    /**
     * @return \DateTime[] [first day, day after the last] of the shown period
     */
    public function range(): array
    {
        $date = $this->day($this->date);
        if ($this->mode === self::MODE_DAY) {
            return [$date, (clone $date)->modify('+1 day')];
        }
        if ($this->mode === self::MODE_WEEK) {
            $from = (clone $date)->modify('-'.((int)$date->format('N') - 1).' days');
            return [$from, (clone $from)->modify('+7 days')];
        }
        $first = $this->day($date->format('Y-m-01'));
        $from = (clone $first)->modify('-'.((int)$first->format('N') - 1).' days');
        $last = (clone $first)->modify('last day of this month');
        $to = (clone $last)->modify('+'.(8 - (int)$last->format('N')).' days');

        return [$from, $to];
    }

    /** Date of the previous or next period. */
    public function shift(int $direction): string
    {
        $date = $this->day($this->date);
        $step = ['month' => 'month', 'week' => 'week', 'day' => 'day'][$this->mode];
        if ($this->mode === self::MODE_MONTH) {
            $date = $this->day($date->format('Y-m-01'));
        }

        return $date->modify(($direction > 0 ? '+1 ' : '-1 ').$step)->format('Y-m-d');
    }

    public function url(array $params): string
    {
        return \yii\helpers\Url::current(array_merge(['mode' => $this->mode, 'date' => $this->date], $params));
    }

    public function title(): string
    {
        $f = \Yii::$app->formatter;
        [$from, $to] = $this->range();
        if ($this->mode === self::MODE_DAY) {
            return $f->asDate($from->getTimestamp(), 'php:l, j F Y');
        }
        if ($this->mode === self::MODE_WEEK) {
            $last = (clone $to)->modify('-1 day');
            return $f->asDate($from->getTimestamp(), 'php:j F').' — '.$f->asDate($last->getTimestamp(), 'php:j F Y');
        }

        return mb_convert_case($f->asDate($this->day($this->date)->getTimestamp(), 'LLLL yyyy'), MB_CASE_TITLE);
    }

    /** Working periods of the employee on a day (an employee without a schedule: weekdays 9–18). */
    public function workPeriods(string $date): array
    {
        $schedule = is_array($this->user->work_shedule) ? array_values(array_filter($this->user->work_shedule, static function ($row) {
            return !empty($row['day']);
        })) : [];

        return CmsScheduleHelper::getSchedulesByWorktimeForDate($schedule ?: TaskPlanner::DEFAULT_SCHEDULE, $date);
    }

    /**
     * Tasks of the period by day: active ones by their start, closed ones by their start too (shown muted).
     *
     * @return CmsTask[][]
     */
    protected function tasksByDay(int $from, int $to): array
    {
        $tasks = CmsTask::find()
            ->andWhere(['executor_id' => (int)$this->user->id])
            ->andWhere(['>=', 'plan_start_at', $from])
            ->andWhere(['<', 'plan_start_at', $to])
            ->andWhere(['not', ['status' => CmsTask::STATUS_CANCELED]])
            ->orderBy(['plan_start_at' => SORT_ASC, 'executor_sort' => SORT_ASC])
            ->limit(2000)
            ->all();

        $result = [];
        foreach ($tasks as $task) {
            $result[\Yii::$app->formatter->asDate((int)$task->plan_start_at, 'php:Y-m-d')][] = $task;
        }

        return $result;
    }

    public function isActive(CmsTask $task): bool
    {
        return in_array($task->status, CmsTask::activeStatuses(), true);
    }

    public function isFixed(CmsTask $task): bool
    {
        return !$task->hasAttribute('is_fixed_start') || (bool)$task->is_fixed_start;
    }

    public function isLate(CmsTask $task): bool
    {
        return $this->isActive($task) && $task->status !== CmsTask::STATUS_IN_WORK && (int)$task->plan_start_at < time() - 60;
    }

    /** CSS classes of a task item. */
    public function taskClass(CmsTask $task): string
    {
        $class = ['sx-task-calendar__task'];
        $class[] = $this->isFixed($task) ? 'is-fixed' : 'is-auto';
        if (!$this->isActive($task)) {
            $class[] = 'is-closed';
        } elseif ($task->status === CmsTask::STATUS_IN_WORK) {
            $class[] = 'is-in-work';
        } elseif ($this->isLate($task)) {
            $class[] = 'is-late';
        }

        return implode(' ', $class);
    }

    /** Attributes that open the task card in a window. */
    public function taskLinkOptions(CmsTask $task): array
    {
        $url = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-cms-task/view', 'pk' => $task->id])
            ->enableEmptyLayout()->enableNoActions()->url;
        $f = \Yii::$app->formatter;
        $hint = $f->asTime((int)$task->plan_start_at, 'php:H:i').'–'.$f->asTime((int)$task->plan_end_at ?: (int)$task->plan_start_at, 'php:H:i')
            .' · '.$task->name.' · '.$task->statusAsText.($this->isFixed($task) ? ' · закреплено на время' : ' · по графику');

        return [
            'href'               => \yii\helpers\Url::to(['/cms/admin-cms-task/view', 'pk' => $task->id]),
            'data-pjax'          => '0',
            'data-sx-task-open'  => $url,
            'title'              => $hint,
            'class'              => $this->taskClass($task),
        ];
    }
}

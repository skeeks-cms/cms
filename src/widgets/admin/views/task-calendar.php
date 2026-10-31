<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\admin\CmsTaskCalendarWidget */
/* @var $days string[] */
/* @var $tasks \skeeks\cms\models\CmsTask[][] */

use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\widgets\admin\CmsTaskCalendarWidget;
use yii\helpers\Html;

$f = \Yii::$app->formatter;
$today = $widget->today();
$month = substr($widget->date, 0, 7);
$now = time();
$hours = static function ($seconds) {
    $seconds = (int)$seconds;
    if (!$seconds) {
        return '0';
    }
    $h = $seconds / 3600;
    return ($h == floor($h) ? (int)$h : number_format($h, 1, ',', '')).' ч';
};
$dayLoad = static function (array $dayTasks) {
    $sum = 0;
    foreach ($dayTasks as $task) {
        $sum += (int)$task->plan_duration;
    }
    return $sum;
};

// Hour scale of a week or a day: the working hours and every task fit in.
$hourFrom = 9;
$hourTo = 19;
if ($widget->mode !== CmsTaskCalendarWidget::MODE_MONTH) {
    foreach ($days as $date) {
        foreach ($widget->workPeriods($date) as $period) {
            $hourFrom = min($hourFrom, (int)$f->asTime((int)$period->start_at, 'php:G'));
            $hourTo = max($hourTo, (int)ceil(((int)$period->end_at - $widget->day($date)->getTimestamp()) / 3600));
        }
        foreach ($tasks[$date] ?? [] as $task) {
            $start = (int)$task->plan_start_at - $widget->day($date)->getTimestamp();
            $hourFrom = min($hourFrom, (int)floor($start / 3600));
            $hourTo = max($hourTo, (int)ceil(($start + max(900, (int)$task->plan_duration)) / 3600));
        }
    }
    $hourFrom = max(0, $hourFrom);
    $hourTo = min(24, max($hourTo, $hourFrom + 1));
}
$span = ($hourTo - $hourFrom) * 3600;

/**
 * Lanes of overlapping tasks of one day: every task gets a lane and the number of lanes of its group.
 */
$lanes = static function (array $dayTasks) {
    $result = [];
    $groupEnd = 0;
    $group = [];
    $flush = static function () use (&$group, &$result) {
        $count = 0;
        foreach ($group as $item) {
            $count = max($count, $item[1] + 1);
        }
        foreach ($group as $item) {
            $result[$item[0]] = [$item[1], $count, $group[0][2]];
        }
        $group = [];
    };
    $laneEnds = [];
    foreach ($dayTasks as $task) {
        $start = (int)$task->plan_start_at;
        $end = $start + max(900, (int)$task->plan_duration);
        if ($group && $start >= $groupEnd) {
            $flush();
            $laneEnds = [];
        }
        $lane = 0;
        while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $start) {
            $lane++;
        }
        $laneEnds[$lane] = $end;
        $group[] = [(int)$task->id, $lane, $group ? $group[0][2] : (int)$task->id];
        $groupEnd = max($groupEnd, $end);
    }
    $flush();

    return $result;
};

$btnCreateTask = '';
if ($taskController = \Yii::$app->createController('/cms/admin-cms-task')) {
    $taskController = $taskController[0];
    if ($createAction = \yii\helpers\ArrayHelper::getValue($taskController->actions, 'create')) {
        $createAction->url = \yii\helpers\ArrayHelper::merge($createAction->urlData, [
            'CmsTask' => ['executor_id' => $widget->user->id],
        ]);
        $createAction->name = 'Добавить задачу';
        $btnCreateTask = \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
            'actions'         => [$createAction],
            'isOpenNewWindow' => true,
            'minViewCount'    => 1,
            'itemTag'         => 'button',
            'itemOptions'     => ['class' => 'btn btn-primary'],
        ]);
    }
}
?>
<?= Html::beginTag('div', $widget->options + ['data-sx-task-calendar' => '1', 'data-now' => $now]); ?>

<div class="sx-task-calendar__bar">
    <div class="sx-task-calendar__nav">
        <a class="btn btn-default btn-sm" href="<?= Html::encode($widget->url(['date' => $widget->shift(-1)])); ?>" title="Назад"><i class="fas fa-chevron-left"></i></a>
        <a class="btn btn-default btn-sm" href="<?= Html::encode($widget->url(['date' => $today])); ?>">Сегодня</a>
        <a class="btn btn-default btn-sm" href="<?= Html::encode($widget->url(['date' => $widget->shift(1)])); ?>" title="Вперёд"><i class="fas fa-chevron-right"></i></a>
        <h2 class="sx-task-calendar__title"><?= Html::encode($widget->title()); ?></h2>
    </div>
    <div class="sx-task-calendar__tools">
        <div class="btn-group sx-task-calendar__modes" role="group">
            <? foreach ([CmsTaskCalendarWidget::MODE_DAY => 'День', CmsTaskCalendarWidget::MODE_WEEK => 'Неделя', CmsTaskCalendarWidget::MODE_MONTH => 'Месяц'] as $mode => $label) : ?>
                <a class="btn btn-sm <?= $widget->mode === $mode ? 'btn-primary' : 'btn-default'; ?>" href="<?= Html::encode($widget->url(['mode' => $mode])); ?>"><?= $label; ?></a>
            <? endforeach; ?>
        </div>
        <?= $btnCreateTask; ?>
    </div>
</div>

<? if ($widget->mode === CmsTaskCalendarWidget::MODE_MONTH) : ?>
    <div class="sx-task-calendar__month">
        <? foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $i => $weekday) : ?>
            <div class="sx-task-calendar__weekday <?= $i >= 5 ? 'is-weekend' : ''; ?>"><?= $weekday; ?></div>
        <? endforeach; ?>
        <? foreach ($days as $date) : ?>
            <?
            $dayTasks = $tasks[$date] ?? [];
            $periods = $widget->workPeriods($date);
            $work = CmsScheduleHelper::durationBySchedules($periods);
            $load = $dayLoad(array_filter($dayTasks, [$widget, 'isActive']));
            $class = ['sx-task-calendar__cell'];
            if (substr($date, 0, 7) !== $month) {
                $class[] = 'is-other-month';
            }
            if (!$periods) {
                $class[] = 'is-off';
            }
            if ((int)$widget->day($date)->format('N') >= 6) {
                $class[] = 'is-weekend';
            }
            if ($date === $today) {
                $class[] = 'is-today';
            }
            if ($date < $today) {
                $class[] = 'is-past';
            }
            ?>
            <div class="<?= implode(' ', $class); ?>">
                <div class="sx-task-calendar__cell-head">
                    <a class="sx-task-calendar__date" href="<?= Html::encode($widget->url(['mode' => CmsTaskCalendarWidget::MODE_DAY, 'date' => $date])); ?>"><?= (int)substr($date, 8, 2); ?></a>
                    <? if ($periods || $load) : ?>
                        <span class="sx-task-calendar__load <?= $work && $load > $work ? 'is-over' : ''; ?>" title="Задачи / рабочее время по графику"><?= $hours($load); ?> / <?= $hours($work); ?></span>
                    <? else : ?>
                        <span class="sx-task-calendar__load">выходной</span>
                    <? endif; ?>
                </div>
                <? if ($work) : ?>
                    <div class="sx-task-calendar__meter"><span style="width: <?= min(100, round($load / $work * 100)); ?>%"></span></div>
                <? endif; ?>
                <div class="sx-task-calendar__cell-tasks">
                    <? foreach (array_slice($dayTasks, 0, $widget->monthCellLimit) as $task) : ?>
                        <?= Html::beginTag('a', $widget->taskLinkOptions($task)); ?>
                            <span class="sx-task-calendar__task-time"><?= $f->asTime((int)$task->plan_start_at, 'php:H:i'); ?><? if ($widget->isFixed($task)) : ?><i class="fas fa-thumbtack"></i><? endif; ?><?= $task->reminderIconHtml; ?></span>
                            <span class="sx-task-calendar__task-name"><?= Html::encode($task->name); ?></span>
                        <?= Html::endTag('a'); ?>
                    <? endforeach; ?>
                    <? if (count($dayTasks) > $widget->monthCellLimit) : ?>
                        <a class="sx-task-calendar__more" href="<?= Html::encode($widget->url(['mode' => CmsTaskCalendarWidget::MODE_DAY, 'date' => $date])); ?>">ещё <?= count($dayTasks) - $widget->monthCellLimit; ?></a>
                    <? endif; ?>
                </div>
                <? if ($dayTasks) : ?>
                    <a class="sx-task-calendar__count" href="<?= Html::encode($widget->url(['mode' => CmsTaskCalendarWidget::MODE_DAY, 'date' => $date])); ?>"><?= count($dayTasks); ?></a>
                <? endif; ?>
            </div>
        <? endforeach; ?>
    </div>
<? else : ?>
    <div class="sx-task-calendar__timeline" style="--sx-task-calendar-days: <?= count($days); ?>; --sx-task-calendar-hours: <?= $hourTo - $hourFrom; ?>;">
        <div class="sx-task-calendar__corner"></div>
        <? foreach ($days as $date) : ?>
            <?
            $periods = $widget->workPeriods($date);
            $dayTasks = $tasks[$date] ?? [];
            ?>
            <a class="sx-task-calendar__col-head <?= $date === $today ? 'is-today' : ''; ?> <?= $periods ? '' : 'is-off'; ?>" href="<?= Html::encode($widget->url(['mode' => CmsTaskCalendarWidget::MODE_DAY, 'date' => $date])); ?>">
                <span class="sx-task-calendar__col-weekday"><?= $f->asDate($widget->day($date)->getTimestamp(), 'php:D'); ?></span>
                <span class="sx-task-calendar__col-date"><?= (int)substr($date, 8, 2); ?></span>
                <span class="sx-task-calendar__col-load"><?= $periods ? $hours($dayLoad(array_filter($dayTasks, [$widget, 'isActive']))).' / '.$hours(CmsScheduleHelper::durationBySchedules($periods)) : 'выходной'; ?></span>
            </a>
        <? endforeach; ?>

        <div class="sx-task-calendar__hours">
            <? for ($h = $hourFrom; $h < $hourTo; $h++) : ?>
                <div class="sx-task-calendar__hour"><?= sprintf('%02d:00', $h); ?></div>
            <? endfor; ?>
        </div>
        <? foreach ($days as $date) : ?>
            <?
            $dayStart = $widget->day($date)->getTimestamp() + $hourFrom * 3600;
            $dayTasks = $tasks[$date] ?? [];
            $taskLanes = $lanes($dayTasks);
            ?>
            <div class="sx-task-calendar__col <?= $date === $today ? 'is-today' : ''; ?>" data-day-start="<?= $dayStart; ?>" data-span="<?= $span; ?>">
                <? foreach ($widget->workPeriods($date) as $period) : ?>
                    <?
                    $top = max(0, ((int)$period->start_at - $dayStart) / $span * 100);
                    $height = min(100 - $top, ((int)$period->end_at - max((int)$period->start_at, $dayStart)) / $span * 100);
                    ?>
                    <div class="sx-task-calendar__work" style="top: <?= round($top, 3); ?>%; height: <?= round($height, 3); ?>%;"></div>
                <? endforeach; ?>
                <?
                // More than three tasks at once: two lanes of tasks and «ещё N» opening the day.
                $hidden = [];
                foreach ($dayTasks as $task) {
                    [$lane, $laneCount, $groupId] = $taskLanes[(int)$task->id] ?? [0, 1, 0];
                    if ($laneCount > 3 && $lane >= 2) {
                        $hidden[$groupId][] = $task;
                    }
                }
                ?>
                <? foreach ($dayTasks as $task) : ?>
                    <?
                    [$lane, $laneCount, $groupId] = $taskLanes[(int)$task->id] ?? [0, 1, 0];
                    if ($laneCount > 3) {
                        if ($lane >= 2) {
                            continue;
                        }
                        $laneCount = 3;
                    }
                    $top = ((int)$task->plan_start_at - $dayStart) / $span * 100;
                    $height = max(900, (int)$task->plan_duration) / $span * 100;
                    $options = $widget->taskLinkOptions($task);
                    $options['style'] = sprintf('top: %.3f%%; height: %.3f%%; left: %.3f%%; width: %.3f%%;', $top, $height, $lane / $laneCount * 100, 100 / $laneCount);
                    Html::addCssClass($options, 'sx-task-calendar__block');
                    ?>
                    <?= Html::beginTag('a', $options); ?>
                        <span class="sx-task-calendar__task-time"><?= $f->asTime((int)$task->plan_start_at, 'php:H:i'); ?><? if ($widget->isFixed($task)) : ?><i class="fas fa-thumbtack"></i><? endif; ?><?= $task->reminderIconHtml; ?></span>
                        <span class="sx-task-calendar__task-name"><?= Html::encode($task->name); ?></span>
                    <?= Html::endTag('a'); ?>
                <? endforeach; ?>
                <? foreach ($hidden as $groupTasks) : ?>
                    <?
                    $first = (int)$groupTasks[0]->plan_start_at;
                    $top = ($first - $dayStart) / $span * 100;
                    ?>
                    <a class="sx-task-calendar__task sx-task-calendar__block sx-task-calendar__block-more" style="top: <?= round($top, 3); ?>%; height: <?= round(900 / $span * 100, 3); ?>%; left: 66.667%; width: 33.333%;"
                       href="<?= Html::encode($widget->url(['mode' => CmsTaskCalendarWidget::MODE_DAY, 'date' => $date])); ?>" title="Ещё задачи в это время">ещё <?= count($groupTasks); ?></a>
                <? endforeach; ?>
                <? if ($date === $today && $now >= $dayStart && $now < $dayStart + $span) : ?>
                    <div class="sx-task-calendar__now" style="top: <?= round(($now - $dayStart) / $span * 100, 3); ?>%;"></div>
                <? endif; ?>
            </div>
        <? endforeach; ?>
    </div>
<? endif; ?>

<?= Html::endTag('div'); ?>

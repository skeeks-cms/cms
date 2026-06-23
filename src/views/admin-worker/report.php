<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;
$start = null;
$end = null;
$period = \Yii::$app->request->get("period");
$timeZone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
$formatDate = function ($timestamp) use ($timeZone) {
    return (new \DateTime("@".$timestamp))->setTimezone($timeZone)->format("Y-m-d");
};
$getDayStart = function ($date) use ($timeZone) {
    return (new \DateTime($date." 00:00:00", $timeZone))->getTimestamp();
};
$getDayEnd = function ($date) use ($timeZone) {
    return (new \DateTime($date." 23:59:59", $timeZone))->getTimestamp();
};
$getReportDays = function ($periodStart, $periodEnd) use ($timeZone) {
    $days = [];
    $current = (new \DateTime("@".$periodStart))->setTimezone($timeZone)->setTime(0, 0, 0);
    $last = (new \DateTime("@".$periodEnd))->setTimezone($timeZone)->setTime(0, 0, 0);

    while ($current <= $last) {
        $days[] = $current->format("Y-m-d");
        $current->modify("+1 day");
    }

    return $days;
};
$durationAsShortText = function ($duration) {
    return preg_replace('/,0\s+ч\.$/u', ' ч.', \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($duration));
};
$formatTasksCount = function ($count) {
    $mod100 = $count % 100;
    $mod10 = $count % 10;
    if ($mod100 >= 11 && $mod100 <= 14) {
        return $count.' задач';
    }
    if ($mod10 == 1) {
        return $count.' задача';
    }
    if ($mod10 >= 2 && $mod10 <= 4) {
        return $count.' задачи';
    }

    return $count.' задач';
};
$renderReportTasksTable = function ($tasks, $user) {
    ob_start();
    ?>
    <table class="table sx-table sx-calendar-day sx-worker-report-tasks-table">
        <tbody>
        <?php foreach($tasks as $task) : ?>
            <?php
            $isCan = true;
            if (\Yii::$app->user->id != $user->id) {
                $isCan = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $task]);
            }

            $trClass = "sx-task-tr".($isCan ? "" : " sx-task-hidden");
            if ($task->status == \skeeks\crm\models\CrmTask::STATUS_IN_WORK) {
                $trClass .= " g-bg-in-work";
            }
            ?>
            <?php echo \yii\helpers\Html::beginTag('tr', [
                'class' => $trClass,
                'data'  => [
                    'id'            => $task->id,
                    'executor_sort' => $task->executor_sort,
                ],
            ]); ?>
                <td class="sx-task-td">
                    <?php echo \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $task]); ?>
                </td>
                <td class="sx-worker-report-task-status">
                    <?php echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $task, 'isShort' => true]); ?>
                </td>
            <?php echo \yii\helpers\Html::endTag('tr'); ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php

    return ob_get_clean();
};

if ($period) {

    $data = preg_split('/\s+-\s+/', $period);
    $startDate = trim(\yii\helpers\ArrayHelper::getValue($data, 0));
    $endDate = trim(\yii\helpers\ArrayHelper::getValue($data, 1));
    if (!$endDate) {
        $endDate = $startDate;
    }
    $parseDate = function ($date, $time) use ($timeZone) {
        foreach (['d/m/Y', 'd.m.Y', 'Y-m-d'] as $format) {
            $dateTime = \DateTime::createFromFormat($format.' H:i:s', $date.' '.$time, $timeZone);
            if ($dateTime instanceof \DateTime && $dateTime->format($format) == $date) {
                return $dateTime->getTimestamp();
            }
        }

        return strtotime($date.' '.$time);
    };

    $start = $parseDate($startDate, "00:00:00");
    $end = $parseDate($endDate, "23:59:59");
    if ($start && $end && $start > $end) {
        $tmpStartDate = $startDate;
        $startDate = $endDate;
        $endDate = $tmpStartDate;

        $start = $parseDate($startDate, "00:00:00");
        $end = $parseDate($endDate, "23:59:59");
    }
}

?>


    <div class="sx-block">
        <? $form = \yii\bootstrap\ActiveForm::begin([
            'method' => "get",
        ]); ?>
        <div class="btn-group">
            <?php echo \skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget::widget([
                'name'    => 'period',
                'value'   => $period,
                'options' => [
                    'placeholder' => 'Период отчета',
                    'class'       => 'form-control',
                ],
            ]); ?>
            <button type="submit" class="btn btn-primary">Применить</button>
        </div>
        <?php $form::end(); ?>
    </div>


<?php if ($start) : ?>
    <?php


    //Сколько всего человек отработал за период
    $userTotalTimeFact = 0;
    $getScheduleEndForReport = function (\skeeks\cms\models\CmsUserSchedule $schedule) use ($model, $formatDate, $getDayEnd) {
        if ($schedule->end_at) {
            return (int) $schedule->end_at;
        }

        $scheduleDate = $formatDate((int) $schedule->start_at);
        if ($scheduleDate == $formatDate(time())) {
            return time();
        }

        $planSchedules = \skeeks\cms\helpers\CmsScheduleHelper::getSchedulesByWorktimeForDate((array) $model->work_shedule, $scheduleDate);
        if ($planSchedules) {
            $plannedEnd = null;
            foreach ($planSchedules as $planSchedule) {
                if ($planSchedule->end_at >= $schedule->start_at) {
                    $plannedEnd = max((int) $plannedEnd, (int) $planSchedule->end_at);
                }
            }

            if ($plannedEnd) {
                return max((int) $schedule->start_at, $plannedEnd);
            }
        }

        return $getDayEnd($scheduleDate);
    };

    $taskSchedulesCache = [];
    $getTaskSchedulesInPeriod = function ($periodStart, $periodEnd) use ($model, &$taskSchedulesCache) {
        $cacheKey = $periodStart."-".$periodEnd;
        if (array_key_exists($cacheKey, $taskSchedulesCache)) {
            return $taskSchedulesCache[$cacheKey];
        }

        $taskSchedules = \skeeks\cms\models\CmsTaskSchedule::find()
            ->andWhere(['cms_user_id' => $model->id])
            ->andWhere(['<=', 'start_at', $periodEnd])
            ->andWhere([
                'or',
                ['end_at' => null],
                ['>=', 'end_at', $periodStart],
            ])
            ->orderBy(['start_at' => SORT_ASC])
            ->all();

        $taskSchedulesCache[$cacheKey] = [];
        foreach ($taskSchedules as $taskSchedule) {
            $taskEndAt = $taskSchedule->end_at ? (int) $taskSchedule->end_at : time();
            if ((int) $taskSchedule->start_at <= $periodEnd && $taskEndAt >= $periodStart) {
                $taskSchedulesCache[$cacheKey][] = $taskSchedule;
            }
        }

        return $taskSchedulesCache[$cacheKey];
    };

    $getTaskActivityBounds = function ($periodStart, $periodEnd) use ($getTaskSchedulesInPeriod) {
        $taskSchedules = $getTaskSchedulesInPeriod($periodStart, $periodEnd);

        if (!$taskSchedules) {
            return null;
        }

        $firstTaskAt = null;
        $lastTaskAt = null;
        foreach ($taskSchedules as $taskSchedule) {
            $taskStartAt = max((int) $taskSchedule->start_at, (int) $periodStart);
            $taskEndAt = min($taskSchedule->end_at ? (int) $taskSchedule->end_at : time(), (int) $periodEnd);

            if ($taskEndAt < $taskStartAt) {
                continue;
            }

            $firstTaskAt = $firstTaskAt === null ? $taskStartAt : min($firstTaskAt, $taskStartAt);
            $lastTaskAt = $lastTaskAt === null ? $taskEndAt : max($lastTaskAt, $taskEndAt);
        }

        if ($firstTaskAt === null || $lastTaskAt === null) {
            return null;
        }

        return [$firstTaskAt, $lastTaskAt];
    };

    $getScheduleDurationInPeriod = function (\skeeks\cms\models\CmsUserSchedule $schedule, $periodStart, $periodEnd) use ($getScheduleEndForReport, $getTaskActivityBounds) {
        $taskActivityBounds = $getTaskActivityBounds($periodStart, $periodEnd);
        if (!$taskActivityBounds) {
            return 0;
        }

        $scheduleStart = (int) $schedule->start_at;
        $scheduleEnd = $getScheduleEndForReport($schedule);
        $factStart = max($scheduleStart, (int) $periodStart, $taskActivityBounds[0]);
        $factEnd = min($scheduleEnd, (int) $periodEnd, $taskActivityBounds[1]);

        return max(0, $factEnd - $factStart);
    };

    $days = $getReportDays($start, $end);
    $planDurations = 0;
    $taskTotalTimeFact = 0;
    $daysData = [];

    foreach ($days as $day) {
        $dayStart = $getDayStart($day);
        $dayEnd = $getDayEnd($day);

        $userTimesOnDay = \skeeks\cms\models\CmsUserSchedule::find()
            ->user($model)
            ->andWhere(['<=', 'start_at', $dayEnd])
            ->andWhere([
                'or',
                ['end_at' => null],
                ['>=', 'end_at', $dayStart],
            ])
            ->orderBy(['start_at' => SORT_ASC])
            ->all()
        ;

        $planSchedules = \skeeks\cms\helpers\CmsScheduleHelper::getSchedulesByWorktimeForDate($model->work_shedule, $day);
        $planDurations = $planDurations + \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($planSchedules);

        $dayFactTime = 0;
        if ($userTimesOnDay) {
            foreach ($userTimesOnDay as $userTimeOnDay) {
                $dayFactTime = $dayFactTime + $getScheduleDurationInPeriod($userTimeOnDay, $dayStart, $dayEnd);
            }
        }

        $taskSchedulesOnDay = $getTaskSchedulesInPeriod($dayStart, $dayEnd);
        foreach ($taskSchedulesOnDay as $taskScheduleOnDay) {
            $taskScheduleStartAt = max((int) $taskScheduleOnDay->start_at, (int) $dayStart);
            $taskScheduleEndAt = min($taskScheduleOnDay->end_at ? (int) $taskScheduleOnDay->end_at : time(), (int) $dayEnd);
            $taskTotalTimeFact = $taskTotalTimeFact + max(0, $taskScheduleEndAt - $taskScheduleStartAt);
        }

        $taskIds = array_unique(array_filter(\yii\helpers\ArrayHelper::getColumn($taskSchedulesOnDay, 'cms_task_id')));
        $tasksOnDay = [];
        if ($taskIds) {
            $taskModels = \skeeks\cms\models\CmsTask::find()
                ->andWhere(['id' => $taskIds])
                ->indexBy('id')
                ->all();

            foreach ($taskIds as $taskId) {
                if (isset($taskModels[$taskId])) {
                    $tasksOnDay[] = $taskModels[$taskId];
                }
            }
        }

        $userTotalTimeFact = $userTotalTimeFact + $dayFactTime;
        $daysData[$day] = [
            'start'         => $dayStart,
            'end'           => $dayEnd,
            'userSchedules' => $userTimesOnDay,
            'planSchedules' => $planSchedules,
            'planTime'      => \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($planSchedules),
            'factTime'      => $dayFactTime,
            'tasks'         => $tasksOnDay,
        ];
    }

    ?>

    <div class="sx-block">
        <div class="sx-properties-wrapper sx-columns-1">
            <ul class="sx-properties">
                <li>
                    <span class="sx-properties--name">
                        Необходимо отработат по графику
                    </span>
                    <span class="sx-properties--value">
                        <?php if ($planDurations) : ?>
                            <?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($planDurations); ?>
                        <?php endif; ?>

                    </span>
                </li>

                <li>
                <span class="sx-properties--name">
                    Отработано фактически
                </span>
                    <span class="sx-properties--value">
                    <?php if ($userTotalTimeFact) : ?>
                        <?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($userTotalTimeFact); ?>
                    <?php endif; ?>

                </span>
                </li>

                <li>
                    <span class="sx-properties--name">
                        Отработано в процентах
                    </span>
                    <span class="sx-properties--value">
                        <?php if ($planDurations) : ?>
                            <span data-toggle="tooltip" title="Процент фактически отработанного времени от времени по графику">
                                <?php echo round($userTotalTimeFact / $planDurations * 100); ?>%
                            </span>
                        <?php endif; ?>
                    </span>
                </li>

                <li>
                    <span class="sx-properties--name">
                        Отработано по задачам
                    </span>
                    <span class="sx-properties--value">
                        <?php if ($taskTotalTimeFact) : ?>
                            <?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($taskTotalTimeFact); ?>
                            <?php if ($userTotalTimeFact) : ?>
                                <span class="sx-worker-report-task-percent" data-toggle="tooltip" title="Процент времени по задачам от фактически отработанного времени">
                                    (<?php echo round($taskTotalTimeFact / $userTotalTimeFact * 100); ?>%)
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <?php if ($days) : ?>
        <?php
        $this->registerCss(<<<CSS
.sx-worker-report-calendar {
    display: grid;
    grid-template-columns: repeat(7, minmax(120px, 1fr));
    gap: 12px;
    overflow-x: auto;
}
.sx-worker-report-month-title {
    color: #333;
    font-size: 22px;
    font-weight: 600;
    grid-column: 1 / -1;
    margin: 8px 0 2px;
}
.sx-worker-report-day {
    min-height: 126px;
    padding: 16px 18px;
}
.sx-worker-report-day-empty {
    opacity: .46;
}
.sx-worker-report-day-empty:hover {
    opacity: .82;
}
.sx-worker-report-day .sx-title {
    line-height: 1.25;
}
.sx-worker-report-date-row {
    align-items: baseline;
    display: flex;
    gap: 6px;
    justify-content: space-between;
}
.sx-worker-report-date-row b {
    color: var(--color-gray);
    font-weight: normal;
}
.sx-worker-report-weekday {
    color: var(--color-gray);
    font-size: 12px;
    font-weight: normal;
}
.sx-worker-report-fact-plan {
    color: var(--color-gray);
    font-size: 14px;
    line-height: 1.25;
    margin-top: 9px;
}
.sx-worker-report-fact {
    color: #333;
    font-weight: 600;
}
.sx-worker-report-fact-under-plan {
    color: #dc3545;
}
.sx-worker-report-day-summary {
    color: #333;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.25;
    margin-top: 2px;
}
.sx-worker-report-plan {
    color: var(--color-gray);
    font-weight: normal;
}
.sx-worker-report-task-percent {
    color: var(--color-gray);
}
.sx-worker-report-tasks-link {
    border-bottom: 1px dotted;
    color: inherit;
}
.sx-worker-report-interval {
    color: #555;
    font-size: 12px;
    line-height: 1.25;
    margin: 7px 0 0;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sx-worker-report-day .sx-info {
    margin-top: 18px;
}
.sx-worker-report-interval a {
    color: inherit;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sx-worker-report-interval small {
    font-size: 11px;
}
.sx-worker-report-modal .modal-header {
    align-items: center;
    display: flex;
    gap: 16px;
    justify-content: space-between;
}
.sx-worker-report-modal .modal-header .close {
    margin-left: auto;
    order: 2;
}
.sx-worker-report-modal .modal-title {
    line-height: 1.3;
    margin: 0;
}
.sx-worker-report-modal .modal-body {
    padding: 0;
}
.sx-worker-report-tasks-table {
    margin-bottom: 0;
}
.sx-worker-report-tasks-table tbody tr {
    border-bottom: 1px solid #e5e5e5;
    position: relative;
}
.sx-worker-report-tasks-table tbody tr:last-child {
    border-bottom: 0;
}
.sx-worker-report-tasks-table td {
    vertical-align: middle !important;
}
.sx-worker-report-task-status {
    text-align: center;
    width: 50px;
}
.sx-worker-report-tasks-table .sx-task-hidden:after {
    align-items: center;
    background: white;
    color: silver;
    content: "Не доступна";
    display: flex;
    height: 100%;
    justify-content: left;
    left: 0;
    padding-left: 12px;
    position: absolute;
    top: 0;
    width: 100%;
}
.sx-worker-report-tasks-table .sx-task-td {
    padding-left: 12px !important;
}
@media (max-width: 900px) {
    .sx-worker-report-calendar {
        grid-template-columns: repeat(7, minmax(118px, 1fr));
    }
}
CSS
        );
        ?>
        <div class="sx-worker-report-calendar">
            <?php
            /**
             * @var $userFactSchedule \skeeks\cms\models\CmsUserSchedule
             */
            $currentMonthKey = null;
            ?>
            <?php foreach ($days as $day) : ?>
                <?
                    $dayDateTime = new \DateTime($day, $timeZone);
                    $monthKey = $dayDateTime->format("Y-m");
                    if ($monthKey != $currentMonthKey) {
                        $currentMonthKey = $monthKey;
                        echo '<div class="sx-worker-report-month-title">'.\Yii::$app->formatter->asDate($day, "LLLL y").'</div>';

                        $monthFirstDayWeekNumber = (int) $dayDateTime->format("N");
                        for ($i = 1; $i < $monthFirstDayWeekNumber; $i++) {
                            echo '<div></div>';
                        }
                    }

                    $dayData = \yii\helpers\ArrayHelper::getValue($daysData, $day, []);
                    $dayStart = \yii\helpers\ArrayHelper::getValue($dayData, 'start');
                    $dayEnd = \yii\helpers\ArrayHelper::getValue($dayData, 'end');
                    $userTimesOnDay = \yii\helpers\ArrayHelper::getValue($dayData, 'userSchedules', []);
                    $times = \yii\helpers\ArrayHelper::getValue($dayData, 'planSchedules', []);
                    $planTime = \yii\helpers\ArrayHelper::getValue($dayData, 'planTime', 0);
                    $dayFactTime = \yii\helpers\ArrayHelper::getValue($dayData, 'factTime', 0);
                    $tasksOnDay = \yii\helpers\ArrayHelper::getValue($dayData, 'tasks', []);
                    $tasksCount = count($tasksOnDay);
                    $tasksModalId = "sx-worker-report-tasks-".md5($model->id."-".$day);
                    $tasksCountText = $formatTasksCount($tasksCount);
                    $weekDayNames = [
                        1 => 'пн',
                        2 => 'вт',
                        3 => 'ср',
                        4 => 'чт',
                        5 => 'пт',
                        6 => 'сб',
                        7 => 'вс',
                    ];
                    $weekDayName = \yii\helpers\ArrayHelper::getValue($weekDayNames, (int) (new \DateTime($day, $timeZone))->format("N"));
                    $dayCardClasses = ['sx-block', 'sx-worker-report-day'];
                    if (!$dayFactTime && !$tasksCount) {
                        $dayCardClasses[] = 'sx-worker-report-day-empty';
                    }
                    $factClasses = ['sx-worker-report-fact'];
                    if ($planTime && $dayFactTime && $dayFactTime < $planTime) {
                        $factClasses[] = 'sx-worker-report-fact-under-plan';
                    }
                ?>
                <div>
                    <div class="<?php echo implode(" ", $dayCardClasses); ?>">
                        <div class="sx-title">
                            <div class="sx-worker-report-date-row">
                                <b><?php echo \Yii::$app->formatter->asDate($day, "d MMM"); ?></b>
                                <span class="sx-worker-report-weekday"><?php echo $weekDayName; ?></span>
                            </div>

                            <div class="sx-worker-report-fact-plan">
                                <?php if($dayFactTime) : ?>
                                    <span class="<?php echo implode(" ", $factClasses); ?>"><?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($dayFactTime); ?></span>
                                    <span>/</span>
                                <?php endif; ?>

                                <?php if(!$times) : ?>
                                    <span class="sx-worker-report-plan" data-toggle="tooltip" title="По плану согласно рабочему графику сотрудника">вых.</span>
                                <?php else : ?>
                                    <span class="sx-worker-report-plan" data-toggle="tooltip" title="По плану согласно рабочему графику сотрудника"><?php echo $durationAsShortText($planTime); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if($tasksCount) : ?>
                                <div class="sx-worker-report-day-summary">
                                    <a href="#<?php echo $tasksModalId; ?>" class="sx-worker-report-tasks-link" data-toggle="modal" data-pjax="0" title="Показать затронутые задачи">
                                        <?php echo $tasksCountText; ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="sx-info">
                            <?php if($userTimesOnDay) : ?>
                                <?php



                                /**
                                 * @var $sheduleUserFactOnDay \skeeks\cms\models\CmsUserSchedule
                                 */
                                foreach($userTimesOnDay as $sheduleUserFactOnDay) : ?>


                                    <?php
                                    $taskActivityBounds = $getTaskActivityBounds($dayStart, $dayEnd);
                                    if (!$taskActivityBounds || !$getScheduleDurationInPeriod($sheduleUserFactOnDay, $dayStart, $dayEnd)) {
                                        continue;
                                    }

                                    $scheduleEndForReport = $getScheduleEndForReport($sheduleUserFactOnDay);
                                    $factStartAt = max((int) $sheduleUserFactOnDay->start_at, $dayStart, $taskActivityBounds[0]);
                                    $factEndAt = min((int) $scheduleEndForReport, $dayEnd, $taskActivityBounds[1]);
                                    $isForgottenTimer = !$sheduleUserFactOnDay->end_at && $scheduleEndForReport < time();
                                    $isCurrentTimer = !$sheduleUserFactOnDay->end_at && !$isForgottenTimer;

                                    $factStartTime = \Yii::$app->formatter->asTime((int) $factStartAt, "short");
                                    $factEndTime = $isCurrentTimer && $factEndAt >= time()
                                        ? "сейчас..."
                                        : \Yii::$app->formatter->asTime((int) $factEndAt, "short");
                                    $factDuration = max(0, $factEndAt - $factStartAt);
                                    $intervalTaskSchedules = $getTaskSchedulesInPeriod($factStartAt, $factEndAt);
                                    $intervalTaskIds = array_unique(array_filter(\yii\helpers\ArrayHelper::getColumn($intervalTaskSchedules, 'cms_task_id')));
                                    $intervalTasks = [];
                                    if ($intervalTaskIds) {
                                        $intervalTaskModels = \skeeks\cms\models\CmsTask::find()
                                            ->andWhere(['id' => $intervalTaskIds])
                                            ->indexBy('id')
                                            ->all();

                                        foreach ($intervalTaskIds as $intervalTaskId) {
                                            if (isset($intervalTaskModels[$intervalTaskId])) {
                                                $intervalTasks[] = $intervalTaskModels[$intervalTaskId];
                                            }
                                        }
                                    }
                                    $intervalTasksCount = count($intervalTasks);
                                    $intervalModalId = "sx-worker-report-tasks-".md5($model->id."-".$day."-".$factStartAt."-".$factEndAt);
                                    $intervalText = $factStartTime." — ".$factEndTime;
                                    $intervalTitle = $factStartTime." — ".$factEndTime;
                                    if ($factDuration) {
                                        $intervalDetails = \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($factDuration);
                                        $intervalTitle .= " (".\skeeks\cms\helpers\CmsScheduleHelper::durationAsText($factDuration);
                                        if ($intervalTasksCount) {
                                            $intervalDetails .= ", ".$formatTasksCount($intervalTasksCount);
                                            $intervalTitle .= ", ".$formatTasksCount($intervalTasksCount);
                                        }
                                        $intervalText .= " (".$intervalDetails.")";
                                        $intervalTitle .= ")";
                                    }
                                    ?>


                                    <p class="sx-worker-report-interval" title="<?php echo \yii\helpers\Html::encode($intervalTitle); ?>">
                                        <?php if($intervalTasksCount) : ?>
                                            <a href="#<?php echo $intervalModalId; ?>" class="sx-worker-report-tasks-link" data-toggle="modal" data-pjax="0" title="<?php echo \yii\helpers\Html::encode($intervalTitle); ?>">
                                                <?php echo $intervalText; ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo $intervalText; ?>
                                        <?php endif; ?>
                                        <?php if($isForgottenTimer) : ?>
                                            <small style="color: var(--color-gray);" data-toggle="tooltip" title="Таймер не был выключен, в отчете учтен только день запуска">(таймер не выключен)</small>
                                        <?php endif; ?>
                                    </p>
                                    <?php if($intervalTasksCount) : ?>
                                        <div id="<?php echo $intervalModalId; ?>" class="modal fade sx-worker-report-modal" tabindex="-1" role="dialog">
                                            <div class="modal-dialog modal-lg" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Задачи за <?php echo \Yii::$app->formatter->asDate($day); ?>, <?php echo $factStartTime; ?> — <?php echo $factEndTime; ?></h4>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                    </div>
                                                    <div class="modal-body sx-worker-report-task-list">
                                                        <?php echo $renderReportTasksTable($intervalTasks, $model); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                            <?php endif; ?>
                            
                        </div>
                    </div>
                    <?php if($tasksCount) : ?>
                        <div id="<?php echo $tasksModalId; ?>" class="modal fade sx-worker-report-modal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Задачи за <?php echo \Yii::$app->formatter->asDate($day); ?></h4>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body sx-worker-report-task-list">
                                        <?php echo $renderReportTasksTable($tasksOnDay, $model); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else : ?>
    <div class="sx-block">
        Для построения отчета, укажите период!
    </div>
<?php endif; ?>

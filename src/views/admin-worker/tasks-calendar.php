<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelAction */
/* @var $model \common\models\User */

use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsTaskSchedule;
use skeeks\cms\widgets\admin\CmsTaskStatusWidget;
use skeeks\cms\widgets\admin\CmsTaskViewWidget;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$controller = $this->context;
$action = $controller->action;
if (!isset($model) || !$model) {
    $model = $action->model;
}
$timeZone = new \DateTimeZone(\Yii::$app->formatter->timeZone);

$durationAsShortText = function ($duration) {
    return preg_replace('/,0\s+ч\.$/u', ' ч.', CmsScheduleHelper::durationAsText($duration));
};
$tasksTimeHint = function ($duration) use ($durationAsShortText) {
    return 'Плановое время задач: '.$durationAsShortText($duration);
};
$scheduleTimeHint = function ($duration, $planSchedules) use ($durationAsShortText) {
    $hint = 'Работа по графику: '.$durationAsShortText($duration);
    if ($planSchedules) {
        $hint .= '<br /><br />'.CmsScheduleHelper::getAsTextBySchedules($planSchedules);
    }

    return $hint;
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
$getTaskRestTime = function (CmsTask $task) {
    $taskPlanTime = (int) ArrayHelper::getValue($task->raw_row, 'planTotalTime', 0);
    $taskScheduleTime = (int) ArrayHelper::getValue($task->raw_row, 'scheduleTotalTime', 0);
    $taskRestTime = max(0, $taskPlanTime - $taskScheduleTime);
    if (!$taskRestTime) {
        $taskRestTime = (int) $task->plan_duration;
    }

    return $taskRestTime;
};
$renderTasksTable = function ($tasks, $user) {
    ob_start();
    ?>
    <table class="table sx-table sx-worker-calendar-tasks-table">
        <tbody>
        <?php foreach ($tasks as $task) : ?>
            <?php
            $isCan = true;
            if (\Yii::$app->user->id != $user->id) {
                $isCan = \Yii::$app->user->can('cms/admin-task/manage', ['model' => $task]);
            }

            $trClass = $isCan ? '' : 'sx-task-hidden';
            if ($task->status == CmsTask::STATUS_IN_WORK) {
                $trClass .= ' g-bg-in-work';
            }
            ?>
            <?php echo Html::beginTag('tr', ['class' => trim($trClass)]); ?>
                <td class="sx-worker-calendar-task-view">
                    <?php echo CmsTaskViewWidget::widget(['task' => $task]); ?>
                </td>
                <td class="sx-worker-calendar-task-status">
                    <?php echo CmsTaskStatusWidget::widget(['task' => $task, 'isShort' => true]); ?>
                </td>
            <?php echo Html::endTag('tr'); ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php

    return ob_get_clean();
};

$scheduleTotalTime = CmsTaskSchedule::find()->select([
    'SUM((end_at - start_at)) as total_timestamp',
])->where([
    'cms_task_id' => new Expression(CmsTask::tableName().'.id'),
]);

$tasks = CmsTask::find()->select([
    CmsTask::tableName().'.*',
    'executorPriority' => new Expression('IF(executor_sort is not null, executor_sort, 9999999)'),
    'scheduleTotalTime' => $scheduleTotalTime,
    'planTotalTime' => new Expression(CmsTask::tableName().'.plan_duration'),
])->where([
    'executor_id' => $model->id,
])->andWhere([
    'status' => [
        CmsTask::STATUS_NEW,
        CmsTask::STATUS_IN_WORK,
        CmsTask::STATUS_ON_PAUSE,
        CmsTask::STATUS_ACCEPTED,
    ],
])->orderBy([
    'executorPriority' => SORT_ASC,
    'id' => SORT_DESC,
])->all();

$todayDate = \Yii::$app->formatter->asDate(time(), 'php:Y-m-d');
$todayStartAt = (new \DateTime($todayDate.' 00:00:00', $timeZone))->getTimestamp();
$plannedTasksByDate = [];
$tasksWithoutPlan = [];
$overdueTasks = [];
foreach ($tasks as $task) {
    if (!empty($task->plan_start_at)) {
        if ($task->plan_start_at < $todayStartAt) {
            $overdueTasks[] = $task;
            continue;
        }

        $plannedDate = \Yii::$app->formatter->asDate($task->plan_start_at, 'php:Y-m-d');
        if (!isset($plannedTasksByDate[$plannedDate])) {
            $plannedTasksByDate[$plannedDate] = [];
        }
        $plannedTasksByDate[$plannedDate][] = $task;
    } else {
        $tasksWithoutPlan[] = $task;
    }
}

$maxPlannedDate = $plannedTasksByDate ? max(array_keys($plannedTasksByDate)) : null;
$days = [];
$daysData = [];
$currentDateTime = new \DateTime($todayDate.' 00:00:00', $timeZone);

for ($i = 0; $i <= 1000; $i++) {
    $day = $currentDateTime->format('Y-m-d');
    $planSchedules = CmsScheduleHelper::getSchedulesByWorktimeForDate((array) $model->work_shedule, $day);
    $planTime = CmsScheduleHelper::durationBySchedules($planSchedules);
    $dayTasks = [];
    $dayTasksTime = 0;

    if ($day == $todayDate && $overdueTasks) {
        foreach ($overdueTasks as $overdueTask) {
            $dayTasks[] = $overdueTask;
            $dayTasksTime += $getTaskRestTime($overdueTask);
        }
    }

    if (!empty($plannedTasksByDate[$day])) {
        foreach ($plannedTasksByDate[$day] as $plannedTask) {
            $dayTasks[] = $plannedTask;
            $dayTasksTime += $getTaskRestTime($plannedTask);
        }
    }

    $dayCapacity = (int) $planTime;
    if ($dayCapacity > 0 && $tasksWithoutPlan) {
        foreach ($tasksWithoutPlan as $taskKey => $task) {
            $taskRestTime = $getTaskRestTime($task);

            $dayTasks[] = $task;
            $dayTasksTime += $taskRestTime;
            unset($tasksWithoutPlan[$taskKey]);
            $dayCapacity -= $taskRestTime;

            if ($dayCapacity <= 0) {
                break;
            }
        }
    }

    $days[] = $day;
    $daysData[$day] = [
        'planSchedules' => $planSchedules,
        'planTime' => $planTime,
        'tasksTime' => $dayTasksTime,
        'tasks' => $dayTasks,
    ];

    if (!$tasksWithoutPlan && (!$maxPlannedDate || $day >= $maxPlannedDate)) {
        break;
    }

    $currentDateTime->modify('+1 day');
}

$tasksTotalCount = 0;
foreach ($daysData as $dayData) {
    $tasksTotalCount += count(ArrayHelper::getValue($dayData, 'tasks', []));
}

$btnCreateTask = '';
if ($taskController = \Yii::$app->createController('/cms/admin-cms-task')) {
    $taskController = $taskController[0];

    if ($createAction = ArrayHelper::getValue($taskController->actions, 'create')) {
        $r = new \ReflectionClass(CmsTask::class);
        $createAction->url = ArrayHelper::merge($createAction->urlData, [
            $r->getShortName() => [
                'executor_id' => $model->id,
            ],
        ]);
        $createAction->name = 'Добавить задачу';

        $btnCreateTask = \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
            'actions' => [$createAction],
            'isOpenNewWindow' => true,
            'minViewCount' => 1,
            'itemTag' => 'button',
            'itemOptions' => ['class' => 'btn btn-primary'],
        ]);
    }
}

$this->registerCss(<<<CSS
.sx-worker-calendar-toolbar {
    align-items: center;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-bottom: 18px;
}
.sx-worker-calendar-summary .sx-properties {
    margin-bottom: 0;
}
.sx-worker-calendar-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(7, minmax(128px, 1fr));
    overflow-x: auto;
}
.sx-worker-calendar-month-title {
    color: #333;
    font-size: 22px;
    font-weight: 600;
    grid-column: 1 / -1;
    margin: 8px 0 2px;
}
.sx-worker-calendar-day {
    min-height: 126px;
    padding: 16px 18px;
}
.sx-worker-calendar-day-empty {
    opacity: .48;
}
.sx-worker-calendar-day-empty:hover {
    opacity: .82;
}
.sx-worker-calendar-day-off {
    background: #f6f7f8;
}
.sx-worker-calendar-date-row {
    align-items: baseline;
    display: flex;
    gap: 6px;
    justify-content: space-between;
    line-height: 1.25;
}
.sx-worker-calendar-date-row b {
    color: var(--color-gray);
    font-weight: normal;
}
.sx-worker-calendar-weekday {
    color: var(--color-gray);
    font-size: 12px;
}
.sx-worker-calendar-plan {
    color: var(--color-gray);
    font-size: 14px;
    line-height: 1.25;
    margin-top: 12px;
}
.sx-worker-calendar-plan b {
    color: var(--color-gray);
    font-weight: normal;
}
.sx-worker-calendar-plan span {
    color: var(--color-gray);
}
.sx-worker-calendar-day-off .sx-worker-calendar-plan b {
    color: var(--color-gray);
    font-weight: normal;
}
.sx-worker-calendar-tasks-count {
    color: #333;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.25;
    margin-top: 8px;
}
.sx-worker-calendar-tasks-link {
    border-bottom: 1px dotted;
    color: inherit;
}
.sx-worker-calendar-tasks-link span {
    display: block;
}
.sx-worker-calendar-no-tasks {
    color: var(--color-gray);
    font-size: 12px;
    line-height: 1.25;
    margin-top: 8px;
}
.sx-worker-calendar-modal .modal-header {
    align-items: center;
    display: flex;
    gap: 16px;
    justify-content: space-between;
}
.sx-worker-calendar-modal .modal-header .close {
    margin-left: auto;
    order: 2;
}
.sx-worker-calendar-modal .modal-title {
    line-height: 1.3;
    margin: 0;
}
.sx-worker-calendar-modal .modal-body {
    padding: 0;
}
.sx-worker-calendar-tasks-table {
    margin-bottom: 0;
}
.sx-worker-calendar-tasks-table tbody tr {
    border-bottom: 1px solid #e5e5e5;
    position: relative;
}
.sx-worker-calendar-tasks-table tbody tr:last-child {
    border-bottom: 0;
}
.sx-worker-calendar-tasks-table td {
    vertical-align: middle !important;
}
.sx-worker-calendar-task-view {
    padding-left: 12px !important;
}
.sx-worker-calendar-task-status {
    text-align: center;
    width: 50px;
}
.sx-worker-calendar-tasks-table .sx-task-hidden:after {
    align-items: center;
    background: white;
    color: silver;
    content: "Недоступна";
    display: flex;
    height: 100%;
    justify-content: left;
    left: 0;
    padding-left: 12px;
    position: absolute;
    top: 0;
    width: 100%;
}
@media (max-width: 900px) {
    .sx-worker-calendar-toolbar {
        align-items: stretch;
        flex-direction: column;
    }
    .sx-worker-calendar-grid {
        grid-template-columns: repeat(7, minmax(118px, 1fr));
    }
}
CSS
);
?>

<div class="sx-worker-calendar-toolbar">
    <?php echo $btnCreateTask; ?>
</div>

<?php if ($days) : ?>
    <div class="sx-block sx-worker-calendar-summary">
        <div class="sx-properties-wrapper sx-columns-1">
            <ul class="sx-properties">
                <li>
                    <span class="sx-properties--name">Задач в календаре</span>
                    <span class="sx-properties--value"><?php echo $formatTasksCount($tasksTotalCount); ?></span>
                </li>
            </ul>
        </div>
    </div>

    <div class="sx-worker-calendar-grid">
        <?php
        $currentMonthKey = null;
        $weekDayNames = [
            1 => 'пн',
            2 => 'вт',
            3 => 'ср',
            4 => 'чт',
            5 => 'пт',
            6 => 'сб',
            7 => 'вс',
        ];
        ?>
        <?php foreach ($days as $day) : ?>
            <?php
            $dayDateTime = new \DateTime($day, $timeZone);
            $monthKey = $dayDateTime->format('Y-m');
            if ($monthKey != $currentMonthKey) {
                $currentMonthKey = $monthKey;
                echo '<div class="sx-worker-calendar-month-title">'.\Yii::$app->formatter->asDate($day, 'LLLL y').'</div>';

                $monthFirstDayWeekNumber = (int) $dayDateTime->format('N');
                for ($i = 1; $i < $monthFirstDayWeekNumber; $i++) {
                    echo '<div></div>';
                }
            }

            $dayData = ArrayHelper::getValue($daysData, $day, []);
            $planSchedules = ArrayHelper::getValue($dayData, 'planSchedules', []);
            $planTime = (int) ArrayHelper::getValue($dayData, 'planTime', 0);
            $tasksTime = (int) ArrayHelper::getValue($dayData, 'tasksTime', 0);
            $tasksOnDay = ArrayHelper::getValue($dayData, 'tasks', []);
            $tasksCount = count($tasksOnDay);
            $tasksModalId = 'sx-worker-calendar-tasks-'.md5($model->id.'-'.$day);
            $dayCardClasses = ['sx-block', 'sx-worker-calendar-day'];
            if (!$planTime && !$tasksCount) {
                $dayCardClasses[] = 'sx-worker-calendar-day-off';
            }
            if (!$planTime && !$tasksCount) {
                $dayCardClasses[] = 'sx-worker-calendar-day-empty';
            }
            ?>
            <div>
                <div class="<?php echo implode(' ', $dayCardClasses); ?>">
                    <div class="sx-worker-calendar-date-row">
                        <b><?php echo \Yii::$app->formatter->asDate($day, 'd MMM'); ?></b>
                        <span class="sx-worker-calendar-weekday"><?php echo ArrayHelper::getValue($weekDayNames, (int) $dayDateTime->format('N')); ?></span>
                    </div>

                    <div class="sx-worker-calendar-plan">
                        <?php if ($planTime) : ?>
                            <span data-toggle="tooltip" data-html="true" title="<?php echo Html::encode($scheduleTimeHint($planTime, $planSchedules)); ?>">
                                <?php echo $durationAsShortText($planTime); ?> / по графику
                            </span>
                        <?php else : ?>
                            <b>вых.</b>
                            <span>/ не работает</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($tasksCount) : ?>
                        <div class="sx-worker-calendar-tasks-count">
                            <a href="#<?php echo $tasksModalId; ?>" class="sx-worker-calendar-tasks-link" data-toggle="modal" data-pjax="0" title="<?php echo Html::encode($tasksTimeHint($tasksTime)); ?>">
                                <span><?php echo $formatTasksCount($tasksCount); ?></span>
                                <?php if ($tasksTime) : ?><span>на <?php echo $durationAsShortText($tasksTime); ?></span><?php endif; ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <div class="sx-worker-calendar-no-tasks">Задач нет</div>
                    <?php endif; ?>
                </div>

                <?php if ($tasksCount) : ?>
                    <div id="<?php echo $tasksModalId; ?>" class="modal fade sx-worker-calendar-modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">Задачи за <?php echo \Yii::$app->formatter->asDate($day); ?></h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <?php echo $renderTasksTable($tasksOnDay, $model); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="sx-block">Для построения календаря укажите период.</div>
<?php endif; ?>

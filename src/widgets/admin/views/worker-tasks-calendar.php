<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $user \common\models\User */
/* @var $widget \skeeks\crm\widgets\WorkerTasksCalendarWidget */
$widget = $this->context;
$user = $widget->user;
$model = $user;
?>
<?= \yii\helpers\Html::beginTag("div", $widget->options); ?>
<div class="row">
    <div class="col-sm-12">
        <!--<h5 class="g-mt-14">Календарь задач</h5>-->

        <div class="sx-task-calendar__toolbar">
            <div class="sx-task-calendar__toolbar-start">
                <button class="btn btn-primary sx-save-priority-btn"><i class="fa fa-save"></i> Сохранить порядок задач</button>
            </div>
            <div class="sx-task-calendar__toolbar-end">

                    <?
                    $btnCreateTask = '';
                    if ($controller = \Yii::$app->createController('/cms/admin-cms-task')) {
                        $controller = $controller[0];

                        if ($createAction = \yii\helpers\ArrayHelper::getValue($controller->actions, 'create')) {

                            /**
                             * @var $createAction BackendModelCreateAction
                             */
                            $r = new \ReflectionClass(\skeeks\cms\models\CmsTask::class);

                            $createAction->url = \yii\helpers\ArrayHelper::merge($createAction->urlData, [
                                $r->getShortName() => [
                                    'executor_id' => $user->id,
                                ],
                            ]);

                            $createAction->name = "Добавить задачу";

                            $btnCreateTask = \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
                                'actions'         => [$createAction],
                                'isOpenNewWindow' => true,
                                'minViewCount'    => 1,
                                'itemTag'         => 'button',
                                'itemOptions'     => ['class' => 'btn btn-primary'],
                                /*'button'          => [
                                    'class' => 'btn btn-primary',
                                    //'style' => 'font-size: 11px; cursor: pointer;',
                                    'tag'   => 'a',
                                    'label' => 'Зарегистрировать номер',
                                ],*/
                            ]);

                        }
                    }
                    ?>
                <?= $btnCreateTask; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?
                \skeeks\cms\backend\widgets\sortable\assets\BackendSortableAdapterAsset::register($this);
                ?>
                <?
                $json = \yii\helpers\Json::encode([
                    'url' => \Yii::$app->request->url,
                    'id'  => $widget->id,
                ]);

                $this->registerJs(<<<JS
                
(function(sx, $, _)
{
    sx.classes.WorkCalendar = sx.classes.Component.extend({
    
        _onDomReady: function()
        {
            var self = this;
            this.jWrapper = $("#" + this.get('id'));
            this.jSavePriorityButton = $(".sx-save-priority-btn", this.jWrapper);
            
            this.jSavePriorityButton.on('click', function() {
                    
                if ($(this).is('disabled')) {
                    return false;
                }
                
                var newSort = [];
                
                $(".sx-task-tr", self.jWrapper).each(function(i, element)
                {
                    newSort.push($(this).data("id"));
                });
                
                var blocker = sx.block("#" + self.get('id'));
                var id = self.get('id');
                
                var ajax = sx.ajax.preparePostQuery(
                    self.get('url'),
                    {
                        "ids" : newSort,
                        'widget' : id
                    }
                );
                
                //new sx.classes.AjaxHandlerStandartRespose(ajax); //отключение глобального загрузчика
                new sx.classes.AjaxHandlerNotify(ajax, {
                    'error': "Изменения не сохранились",
                    'success': "Изменения сохранены",
                }); //отключение глобального загрузчика
                
                ajax
                /*.onError(function(e, data)
                {
                    sx.notify.error("Подождите сейчас страница будет перезагружена");
                    _.delay(function()
                    {
                        window.location.reload();
                    }, 2000);
                })*/
                .onSuccess(function(e, data)
                {
                    blocker.unblock();
                    
                    _.delay(function()
                    {
                        window.location.reload();
                    }, 200);
                })
                .execute();
            });

            this.Sortable = sx.backend.sortable.create($(".sx-calendar-day tbody", this.jWrapper), {
                group: "cms-worker-task-calendar-" + this.get('id'),
                cursor: "n-resize",
                handle: ".sx-move-btn",
                itemSelector: "> .sx-task-tr",
                forceHelperSize: true,
                forcePlaceholderSize: true,
                opacity: 0.5,
                placeholderClass: "ui-state-highlight",
                providerOptions: {
                    direction: "vertical",
                    onMove: function(event) {
                        if (event.from !== event.to && !$(event.to).children(".sx-task-tr").length) {
                            return false;
                        }
                    }
                },
                onUpdate: function()
                {
                    self.jSavePriorityButton.fadeIn();
                }
            });
        }
    });
    
    new sx.classes.WorkCalendar({$json});
})(sx, sx.$, sx._);
                
                        
JS
                );
                ?>
                <?
                $todayDate = \Yii::$app->formatter->asDate(time(), "php:Y-m-d");
                $todayStartAt = strtotime($todayDate . ' 00:00:00');
                $todayEndAt = strtotime($todayDate . ' 23:59:59');

                $unfinishedSchedules = \skeeks\cms\models\CmsTaskSchedule::find()
                    ->select([
                        'cms_task_id',
                        'last_start_at' => new \yii\db\Expression('MAX(' . \skeeks\cms\models\CmsTaskSchedule::tableName() . '.start_at)'),
                        'last_end_at' => new \yii\db\Expression('MAX(' . \skeeks\cms\models\CmsTaskSchedule::tableName() . '.end_at)'),
                    ])
                    ->andWhere([\skeeks\cms\models\CmsTaskSchedule::tableName() . '.cms_user_id' => $model->id])
                    ->andWhere(['between', \skeeks\cms\models\CmsTaskSchedule::tableName() . '.start_at', $todayStartAt, $todayEndAt])
                    ->andWhere(['>', \skeeks\cms\models\CmsTaskSchedule::tableName() . '.end_at', 0])
                    ->groupBy(\skeeks\cms\models\CmsTaskSchedule::tableName() . '.cms_task_id');

                $unfinishedTasks = \skeeks\cms\models\CmsTask::find()
                    ->select([
                        \skeeks\cms\models\CmsTask::tableName() . '.*',
                        'last_start_at' => 'unfinished_schedules.last_start_at',
                        'last_end_at' => 'unfinished_schedules.last_end_at',
                    ])
                    ->where([\skeeks\cms\models\CmsTask::tableName() . '.executor_id' => $model->id])
                    ->andWhere([\skeeks\cms\models\CmsTask::tableName() . '.status' => \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE])
                    ->innerJoin(['unfinished_schedules' => $unfinishedSchedules], ['unfinished_schedules.cms_task_id' => new \yii\db\Expression(\skeeks\cms\models\CmsTask::tableName() . '.id')])
                    ->orderBy(['last_start_at' => SORT_DESC])
                    ->all();

                $scheduleTotalTime = \skeeks\cms\models\CmsTaskSchedule::find()->select([
                    'SUM((end_at - start_at)) as total_timestamp',
                ])->where([
                    'cms_task_id' => new \yii\db\Expression(\skeeks\cms\models\CmsTask::tableName() . ".id"),
                ]);


                $tasks = \skeeks\cms\models\CmsTask::find()->select([
                    \skeeks\cms\models\CmsTask::tableName().'.*',
                    'executorPriority'  => new \yii\db\Expression("IF(executor_sort is not null, executor_sort, 9999999)"),
                    'scheduleTotalTime' => $scheduleTotalTime,
                    'planTotalTime'     => new \yii\db\Expression(\skeeks\cms\models\CmsTask::tableName().".plan_duration"),
                ])->where([
                    'executor_id' => $model->id,
                ])->andWhere([
                    'status' => [
                        \skeeks\cms\models\CmsTask::STATUS_NEW,
                        \skeeks\cms\models\CmsTask::STATUS_IN_WORK,
                        \skeeks\cms\models\CmsTask::STATUS_ON_PAUSE,
                        \skeeks\cms\models\CmsTask::STATUS_ACCEPTED,
                    ],
                ])->orderBy([
                    'executorPriority' => SORT_ASC,
                    'id'               => SORT_DESC,
                ])
                    ->all();

                $now = time();
                $currentDate = \Yii::$app->formatter->asDate($now, "php:Y-m-d");

                $expiredTasks = [];
                $plannedTasksByDate = [];
                $tasksWithoutPlan = [];

                if ($tasks) {
                    foreach ($tasks as $task) {
                        if (!empty($task->plan_start_at)) {
                            if ($task->plan_start_at < $now) {
                                $expiredTasks[] = $task;
                            } else {
                                $plannedDate = \Yii::$app->formatter->asDate($task->plan_start_at, "php:Y-m-d");
                                if (!isset($plannedTasksByDate[$plannedDate])) {
                                    $plannedTasksByDate[$plannedDate] = [];
                                }
                                $plannedTasksByDate[$plannedDate][] = $task;
                            }
                        } else {
                            $tasksWithoutPlan[] = $task;
                        }
                    }
                }

                $tasks = $tasksWithoutPlan;
                $maxPlannedDate = $plannedTasksByDate ? max(array_keys($plannedTasksByDate)) : null;

                $elseDayTime = 0;


                $workShedule = $user->work_shedule;


                ?>

                <? if ($unfinishedTasks) : ?>
                    <table class="table sx-table sx-unfinished-tasks">
                        <thead>
                        <tr>
                            <th class="text-center" colspan="4">
                                Незавершенные задачи
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <? foreach ($unfinishedTasks as $task) : ?>
                            <?
                            $isCan = true;

                            if (\Yii::$app->user->id != $user->id) {
                                $isCan = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $task]);
                            }
                            ?>
                            <?= \yii\helpers\Html::beginTag('tr', [
                                'class' => ($isCan ? "" : "sx-task-hidden") . ' sx-unfinished-task',
                            ]); ?>
                            <td style="width: 45px;">
                            </td>
                            <td style="width: 45px;">
                                <span title="Последний запуск" data-toggle="tooltip" style="line-height: 35px;">
                                    <?= \Yii::$app->formatter->asTime($task->raw_row['last_start_at'], 'php:H:i'); ?>
                                </span>
                            </td>
                            <td class="sx-task-td">
                                <?= \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $task]); ?>
                            </td>

                            <td style="width: 50px;">
                                <?= \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $task, 'isShort' => true]); ?>
                            </td>
                            <?= \yii\helpers\Html::endTag('tr'); ?>
                        <? endforeach; ?>
                        </tbody>
                    </table>
                <? endif; ?>

                <? if ($expiredTasks) : ?>
                    <table class="table sx-table sx-calendar-day sx-expired-tasks">
                        <thead>
                        <tr>
                            <th class="text-center" colspan="4">
                                Просроченные задачи
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <? foreach ($expiredTasks as $task) : ?>
                            <?
                            $isCan = true;

                            if (\Yii::$app->user->id != $user->id) {
                                $isCan = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $task]);
                            }

                            $tr = [
                                'class' => 'sx-task-tr ' . ($isCan ? "" : "sx-task-hidden") . ' sx-task-expired',

                                'data' => [
                                    'id'            => $task->id,
                                    'executor_sort' => $task->executor_sort,
                                ],
                            ];

                            if ($task->status == \skeeks\crm\models\CrmTask::STATUS_IN_WORK) {
                                $tr['class'] = "sx-task-tr sx-row-in-work sx-task-expired";
                            }
                            ?>
                            <?= \yii\helpers\Html::beginTag('tr', $tr); ?>
                            <td style="width: 45px;">
                                <span title="Перетащите для изменеия порядка" style="line-height: 35px;">
                                    <a href="#" class="btn sx-move-btn sx-task-calendar__move">
                                        <i class="fas fa-arrows-alt-v"></i>
                                    </a>
                                </span>
                            </td>
                            <td style="width: 45px;">
                                <!--<span title="Плановое время выполнения" data-toggle="tooltip" style="line-height: 35px;">
                                    <?php /*= \Yii::$app->formatter->asDatetime($task->plan_start_at); */?>
                                </span>-->
                            </td>
                            <td class="sx-task-td">
                                <?= \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $task]); ?>
                            </td>

                            <td style="width: 50px;">
                                <?= \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $task, 'isShort' => true]); ?>
                            </td>
                            <?= \yii\helpers\Html::endTag('tr'); ?>
                        <? endforeach; ?>
                        </tbody>
                    </table>
                <? endif; ?>

                <? for ($i = 0; $i <= 1000; $i++) : ?>
                    <?

                    $workShedule = $user->work_shedule;

                    //День в цикле
                    $date = date("Y-m-d", strtotime("+{$i} day"));
                    $times = \skeeks\cms\helpers\CmsScheduleHelper::getSchedulesByWorktimeForDate($workShedule, $date);

                    $timesForCalculate = $times;
                    $seconds = \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($times);

                    $timesToday = [];
                    $isToday = false;
                    if ($date == \Yii::$app->formatter->asDate(time(), "php:Y-m-d")) {
                        $timesToday = \skeeks\cms\helpers\CmsScheduleHelper::getFilteredSchedulesByStartTime($times);
                        /*print_r($times);
                        print_r($timesToday);*/
                        $timesForCalculate = $timesToday;
                        $isToday = true;
                        $workedSeconds = \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($times);
                    }
                ?>


                <table class="table sx-table sx-calendar-day <?= !$times  ? "sx-not-work-day" : ""; ?> <?= $date != \Yii::$app->formatter->asDate(time(), "php:Y-m-d") ? "sx-not-today-day" : ""; ?>">
                    <thead>
                    <tr>
                        <th class="text-center" colspan="4"><?= \Yii::$app->formatter->asDate($date, 'full'); ?>
                                <? if ($times) : ?>
                                <a href="<?= \yii\helpers\Url::to(['/cms/admin-user/planschedule', 'pk' => $model->id]); ?>" class="sx-task-calendar__header-link" target="_blank">
                                    <small data-toggle="tooltip" title="Время по графику: <br /><br /><?= \skeeks\cms\helpers\CmsScheduleHelper::getAsTextBySchedules($times); ?>" data-html="true">
                                        (всего по графику: <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($seconds); ?>)
                                    </small>
                                </a>
                                <? if ($timesToday) : ?>
                                    <small data-toggle="tooltip" title="Осталось сегодня исходя из графика: <br /><br /><?= \skeeks\cms\helpers\CmsScheduleHelper::getAsTextBySchedules($timesToday); ?>" data-html="true">
                                        (еще отработает: <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsTextBySchedules($timesToday); ?>)
                                    </small>
                                <? elseif (!$timesToday && $date == \Yii::$app->formatter->asDate(time(), "php:Y-m-d")) : ?>

                                    <? if ($workedSeconds > $seconds) : ?>
                                        <small data-toggle="tooltip" title="Сегодня отработал сверх плана: <br /><br /><?= \skeeks\cms\helpers\CmsScheduleHelper::getAsTextBySchedules($user->crmSchedulesByDate); ?>" data-html="true">
                                            (отработал сверх плана: <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($workedSeconds - $seconds); ?>)
                                        </small>
                                    <? else: ?>
                                        <small data-toggle="tooltip" title="Не доработано: <br /><br /><?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($seconds - $workedSeconds); ?>" data-html="true">
                                            (Не доработано: <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($seconds - $workedSeconds); ?>)
                                        </small>
                                    <? endif; ?>

                                <? endif; ?>


                            <? else : ?>
                                <a href="<?= \yii\helpers\Url::to(['/crm/crm-user/planschedule', 'pk' => $model->id]); ?>" class="sx-task-calendar__header-link" target="_blank">
                                    <small>(Не работает)</small>
                                </a>
                            <? endif; ?>

                        </th>
                    </tr>
                    <? if ($isToday) : ?>
                        
                        <?
                        //Только промежутки закрытые в этот день
                        $subquery = \skeeks\cms\models\CmsTaskSchedule::find()
                            ->select([
                                \skeeks\cms\models\CmsTaskSchedule::tableName() . ".*",
                                "date_formated_end" => new \yii\db\Expression("DATE_FORMAT(FROM_UNIXTIME(" . \skeeks\cms\models\CmsTaskSchedule::tableName() . ".end_at), '%Y-%m-%d')")
                            ])
                            ->orderBy([
                                "date_formated_end" => SORT_DESC,
                                \skeeks\cms\models\CmsTaskSchedule::tableName().".end_at" => SORT_DESC
                            ])
                            ->andHaving(["date_formated_end" => $date])
                        ;

                        $qt = \skeeks\cms\models\CmsTask::find()
                            ->select([
                                \skeeks\cms\models\CmsTask::tableName() . ".*",
                                "schedules_end_at" => "schedules.end_at",
                            ])
                            ->where(['executor_id' => $model->id])
                            ->innerJoin(['schedules' => $subquery], ['schedules.cms_task_id' => new \yii\db\Expression(\skeeks\cms\models\CmsTask::tableName().".id")])
                            ->andWhere([
                                'status' => [
                                    \skeeks\cms\models\CmsTask::STATUS_ON_CHECK,
                                    \skeeks\cms\models\CmsTask::STATUS_READY,
                                    \skeeks\cms\models\CmsTask::STATUS_CANCELED,
                                ],
                            ])
                            ->groupBy([
                                \skeeks\cms\models\CmsTask::tableName().".id"
                            ])
                            ->orderBy([
                                'schedules_end_at' => SORT_ASC
                            ])
                        ;
                        
                        ?>
                        <? if ($tasksToday = $qt->all()) : ?>
                            <? foreach ($tasksToday as $t) : ?>

                                <?
                                $isCan = true;

                                if (\Yii::$app->user->id != $user->id) {
                                    $isCan = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $t]);
                                }
                                ?>
                                <?= \yii\helpers\Html::beginTag('tr', [
                                    'class' => 'sx-task-tr sx-task-completed ' . ($isCan ? "" : "sx-task-hidden"),
                                ]); ?>
                                <td style="width: 45px;">
                                </td>
                                <td style="width: 45px;">
                                </td>
                                <td class="sx-task-td">
                                    <?= \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $t]); ?>
                                    <?/*= \skeeks\crm\widgets\TaskViewWidget::widget(['task' => $t]); */?>
                                </td>
                                <td style="width: 50px;">
                                    <?= \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $t, 'isShort' => true]); ?>
                                    <?/*= \skeeks\crm\widgets\TaskStatusWidget::widget(['task' => $t, 'isShort' => true]); */?>
                                </td>
                                <?= \yii\helpers\Html::endTag('tr'); ?>
                            <? endforeach; ?>
                        <? endif; ?>
                    <? endif; ?>
                    </thead>

                    <? if ($times) : ?>
                        <tbody>

                        <?
                        $dayTime = \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($timesForCalculate);
                        /*echo $dayTime;*/
                        $dayTime = $dayTime + $elseDayTime;
                        /*echo "/" . $dayTime;*/

                        ?>

                        <? if (!empty($plannedTasksByDate[$date])) : ?>
                            <? foreach ($plannedTasksByDate[$date] as $task) : ?>

                                <?
                                $isCan = true;

                                if (\Yii::$app->user->id != $user->id) {
                                    $isCan = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $task]);
                                }

                                $tr = [
                                    'class' => 'sx-task-tr ' . ($isCan ? "" : "sx-task-hidden") . ' sx-task-planned',

                                    'data' => [
                                        'id'            => $task->id,
                                        'executor_sort' => $task->executor_sort,
                                    ],
                                ];

                                if ($task->status == \skeeks\crm\models\CrmTask::STATUS_IN_WORK) {
                                    $tr['class'] = "sx-task-tr sx-row-in-work sx-task-planned";
                                }
                                ?>

                                <?= \yii\helpers\Html::beginTag('tr', $tr); ?>
                                <td style="width: 45px;">
                                    <span title="Перетащите для изменеия порядка" style="line-height: 35px;">
                                        <a href="#" class="btn sx-move-btn sx-task-calendar__move">
                                            <i class="fas fa-arrows-alt-v"></i>
                                        </a>
                                    </span>
                                </td>
                                <td style="width: 45px;">
                                    <span title="Плановое время выполнения" data-toggle="tooltip" style="line-height: 35px;">
                                        <?= \Yii::$app->formatter->asTime($task->plan_start_at,'php:H:i'); ?>
                                    </span>
                                </td>
                                <td class="sx-task-td">
                                    <?= \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $task]); ?>
                                </td>

                                <td style="width: 50px;">
                                    <?= \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $task, 'isShort' => true]); ?>
                                </td>
                                <?= \yii\helpers\Html::endTag('tr'); ?>
                            <? endforeach; ?>
                        <? endif; ?>

                        <? if ($tasks) : ?>
                            <? foreach ($tasks as $key => $task) : ?>

                                <?
                                $isCan = true;

                                if (\Yii::$app->user->id != $user->id) {
                                    $isCan = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $task]);
                                }

                                $time = $task->raw_row['planTotalTime'] - $task->raw_row['scheduleTotalTime'];
                                if ($time < 0) {
                                    $time = 0;
                                }
                                $dayTime = $dayTime - $time;

                                $tr = [
                                    'class' => 'sx-task-tr ' . ($isCan ? "" : "sx-task-hidden"),

                                    'data' => [
                                        'id'                => $task->id,
                                        'executor_sort' => $task->executor_sort,
                                    ],
                                ];

                                if ($task->status == \skeeks\crm\models\CrmTask::STATUS_IN_WORK) {
                                    $tr['class'] = "sx-task-tr sx-row-in-work" . ($isCan ? "" : " sx-task-hidden");
                                }
                                ?>

                                <?= \yii\helpers\Html::beginTag('tr', $tr); ?>
                                <td style="width: 45px;">
                                    <span title="Перетащите для изменеия порядка" style="line-height: 35px;">
                                        <a href="#" class="btn sx-move-btn sx-task-calendar__move">
                                            <i class="fas fa-arrows-alt-v"></i>
                                        </a>
                                    </span>
                                </td>
                                <td style="width: 45px;">
                                    <span title="Сотировка задачи" data-toggle="tooltip" style="line-height: 35px;">
                                    <?= $task->executor_sort; ?>
                                    </span>
                                </td>
                                <td class="sx-task-td">
                                    <?= \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $task]); ?>
                                    <?/*= \skeeks\crm\widgets\TaskViewWidget::widget(['task' => $task]); */?>
                                </td>

                                <td style="width: 50px;">
                                    <?/*= \skeeks\crm\widgets\TaskStatusWidget::widget(['task' => $task, 'isShort' => true]); */?>
                                    <?= \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $task, 'isShort' => true]); ?>
                                </td>
                                <?= \yii\helpers\Html::endTag('tr'); ?>
                                <?
                                unset($tasks[$key]);
                                if ($dayTime <= 0) {
                                    $elseDayTime = $dayTime;
                                    break;
                                }
                                ?>

                            <? endforeach; ?>
                        <? else : ?>
                            <? if (empty($plannedTasksByDate[$date]) && (!$maxPlannedDate || $date >= $maxPlannedDate)) : ?>
                                </tbody>
                                </table>
                                <? break; ?>
                            <? endif; ?>
                        <? endif; ?>
                        </tbody>
                    <? else : ?>
                        <thead>
                        <tr>
                            <td class="text-center sx-task-calendar__empty" colspan="4">
                                Не делает задачи в этот день
                            </td>
                        </tr>
                        </thead>
                    <? endif; ?>


                    </table>

                <? endfor; ?>

            </div>
        </div>

    </div>
</div>

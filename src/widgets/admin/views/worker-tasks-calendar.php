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

        <div class="row g-mb-20" style="margin-bottom: 1rem;">
            <div class="col-sm-12">
                <div class="pull-left">
                    <button class="btn btn-primary sx-save-priority-btn"><i class="fa fa-save"></i> Сохранить порядок задач</button>
                </div>
                <div class="pull-right">

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
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?
                \yii\jui\Sortable::widget();
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
            
            $(".sx-save-priority-btn").on('click', function() {
                    
                if ($(this).is('disabled')) {
                    return false;
                }
                
                var newSort = [];
                
                $(".sx-task-tr").each(function(i, element)
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

            $(".sx-calendar-day tbody").sortable({
                connectWith: ".sx-calendar-day tbody",
                cursor: "n-resize",
                dropOnEmpty: false,
                handle: ".sx-move-btn",
                forceHelperSize: true,
                forcePlaceholderSize: true,
                opacity: 0.5,
                placeholder: "ui-state-highlight",
                
                out: function( event, ui )
                {
                    $(".sx-save-priority-btn").fadeIn();
                }
            });
            
            //$( ".sx-calendar-day tbody" ).sortable( "option", "handle", "button" );
        }
    });
    
    new sx.classes.WorkCalendar({$json});
})(sx, sx.$, sx._);
                
                        
JS
                );
                ?>
                <?
                $this->registerCss(<<<CSS
        @keyframes sx-save-priority-pulse {
          0% {
            box-shadow: 0 0 5px 0px var(--primary-color), 0 0 5px 0px var(--primary-color); 
          }
          100% {
            box-shadow: 0 0 5px 6px rgba(255, 48, 26, 0), 0 0 4px 10px rgba(255, 48, 26, 0); 
          } 
        }

        .sx-save-priority-btn {
            position: fixed;
            display: none;
            left: 50%;
            top: 50%;
            z-index: 99;
            animation: sx-save-priority-pulse 1.5s infinite linear;
        }
        .sx-task-hidden .sx-task-td {
            position: relative;
        }
        
        .sx-task-hidden .sx-task-td:after {
            position: absolute;
            content: "Не доступна";
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* filter: blur(1.5rem); */
            background: white;
            display: flex;
            justify-content: left;
            align-items: center;
            color: silver;
        }
        .table.sx-calendar-day thead th {
            border-bottom: 0;
        }
        .sx-calendar-day th {
            background: var(--primary-color) !important;
            color: white;
            border: none;
        }
        .sx-calendar-day{
            border: 1px solid var(--primary-color);
            background: white;
            overflow: hidden;
            border-radius: var(--border-radius);
            border: 0;
        }
        .sx-not-work-day {
            border-radius: var(--border-radius);
        }

        .sx-not-work-day th {
            background: var(--color-gray) !important;
        }
        .sx-not-today-day {
            opacity: 0.9;
        }
        .sx-not-today-day:hover {
            opacity: 1;
        }
CSS
                );

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

                $elseDayTime = 0;


                $workShedule = $user->work_shedule;


                ?>

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


                <table class="table sx-table sx-calendar-day <?= !$times ? "sx-not-work-day" : ""; ?> <?= $date != \Yii::$app->formatter->asDate(time(), "php:Y-m-d") ? "sx-not-today-day" : ""; ?>">
                    <thead>
                    <tr>
                        <th class="text-center" colspan="4"><?= \Yii::$app->formatter->asDate($date, 'full'); ?>
                                <? if ($times) : ?>
                                <a href="<?= \yii\helpers\Url::to(['/cms/admin-user/planschedule', 'pk' => $model->id]); ?>" target="_blank" style="color: white;">
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
                                <a href="<?= \yii\helpers\Url::to(['/crm/crm-user/planschedule', 'pk' => $model->id]); ?>" target="_blank" style="color: white;">
                                    <small>(Не работает)</small>
                                </a>
                            <? endif; ?>

                        </th>
                    </tr>
                    <? if ($isToday) : ?>
                        <?/*
                        $subquery = \skeeks\crm\models\CrmTaskSchedule::find()
                            ->orderBy([
                                \skeeks\crm\models\CrmTaskSchedule::tableName().".date" => SORT_DESC,
                                \skeeks\crm\models\CrmTaskSchedule::tableName().".end_time" => SORT_DESC
                            ])
                            ;

                        $qt = \skeeks\crm\models\CrmTask::find()->where(['executor_id' => $model->id])
                            ->leftJoin(['crmTaskSchedules' => $subquery], ['crmTaskSchedules.crm_task_id' => new \yii\db\Expression(\skeeks\crm\models\CrmTask::tableName().".id")])
                            ->andWhere([
                                'status' => [
                                    \skeeks\crm\models\CrmTask::STATUS_ON_CHECK,
                                    \skeeks\crm\models\CrmTask::STATUS_READY,
                                    \skeeks\crm\models\CrmTask::STATUS_CANCELED,
                                ],
                            ])
                            ->andWhere(["crmTaskSchedules.date" => $date])
                            ->groupBy([
                                \skeeks\crm\models\CrmTask::tableName().".id"
                            ])
                            ->orderBy([
                                'crmTaskSchedules.end_time' => SORT_ASC
                            ])
                        ;
                        */?>
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

                        /*$qt = \skeeks\cms\models\CmsTask::find()
                            ->select([
                                \skeeks\cms\models\CmsTask::tableName() . ".*",
                                "schedules_end_at" => "schedules.end_at",
                                "date_formated_end" => new \yii\db\Expression("DATE_FORMAT(FROM_UNIXTIME(schedules.end_at), '%Y-%m-%d')")
                            ])
                            ->joinWith("schedules as schedules")
                            ->andWhere(['executor_id' => $model->id])
                            ->andHaving(['date_formated_end' => $date])
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
                        ;*/

                        /*print_R($qt->createCommand()->rawSql);*/
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
                                    'class' => 'sx-task-tr ' . ($isCan ? "" : "sx-task-hidden"),
                                    'style' => '    opacity: 0.5;
    background: #e5fde5;'
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
                                    $tr['class'] = "sx-task-tr g-bg-in-work";
                                }
                                ?>

                                <?= \yii\helpers\Html::beginTag('tr', $tr); ?>
                                <td style="width: 45px;">
                                    <span title="Перетащите для изменеия порядка" style="line-height: 35px;">
                                        <a href="#" class="btn u-btn-white sx-move-btn" style="color: gray; cursor: n-resize;">
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
                            </tbody>
                            </table>
                            <? break; ?>
                        <? endif; ?>
                        </tbody>
                    <? else : ?>
                        <thead>
                        <tr>
                            <td class="text-center" colspan="4" style="color: gray;">
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



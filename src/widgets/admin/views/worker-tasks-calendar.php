<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $user \common\models\User */
/* @var $widget \skeeks\cms\widgets\admin\CmsWorkerTasksCalendarWidget */
$widget = $this->context;
$user = $widget->user;
$model = $user;
?>
<?= \yii\helpers\Html::beginTag("div", $widget->options); ?>
<div class="row">
    <div class="col-sm-12">
        <!--<h5 class="g-mt-14">Календарь задач</h5>-->

        <?= \skeeks\cms\widgets\admin\CmsTaskViewToolbarWidget::widget([
            'context' => $widget->context,
            'floating' => '<button type="button" class="btn btn-primary btn-sm sx-save-priority-btn">'.\skeeks\cms\backend\helpers\BackendIcon::render('check-circle', ['size' => 16]).' Сохранить порядок задач</button>',
        ]); ?>

        <div class="row">
            <div class="col-sm-12">
                <?= \skeeks\cms\widgets\admin\CmsUnassignedClientTasksWidget::widget(['user' => $model]); ?>
                <?
                \skeeks\cms\backend\widgets\sortable\assets\BackendSortableAdapterAsset::register($this);
                ?>
                <?
                $json = \yii\helpers\Json::encode([
                    'url'     => \Yii::$app->request->url,
                    'id'      => $widget->id,
                    'moveUrl' => \yii\helpers\Url::to(['/cms/admin-cms-task/reschedule']),
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

            this._initScrollPosition();

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
                cursor: "grabbing",
                handle: ".sx-move-btn",
                itemSelector: "> .sx-task-tr",
                forceHelperSize: true,
                forcePlaceholderSize: true,
                opacity: 0.5,
                placeholderClass: "ui-state-highlight",
                providerOptions: {
                    direction: "vertical",
                    onMove: function(event) {
                        // An empty list takes a task only when it is a day (a fixed task moves to that day).
                        if (event.from !== event.to && !$(event.to).children(".sx-task-tr").length && !$(event.to).data("day")) {
                            return false;
                        }
                    }
                },
                onUpdate: function(event)
                {
                    // A fixed (or overdue) task dropped on another day: the first free time of that day, still fixed.
                    if (event && event.jItem && event.jItem.hasClass("sx-task-tr--fixed")) {
                        var day = event.jContainer ? event.jContainer.data("day") : null;
                        if (!day || event.from === event.container) {
                            window.location.reload();
                            return;
                        }
                        self.moveFixed(event.jItem.data("id"), day);
                        return;
                    }
                    self.jSavePriorityButton.fadeIn();
                }
            });
        },

        moveFixed: function(id, day)
        {
            var data = {day: day};
            data[yii.getCsrfParam()] = yii.getCsrfToken();
            var blocker = sx.block("#" + this.get('id'));
            $.ajax({
                url: this.get('moveUrl') + (this.get('moveUrl').indexOf('?') === -1 ? '?' : '&') + 'pk=' + id,
                method: 'POST',
                data: data,
                dataType: 'json',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).done(function (response) {
                if (!response || !response.success) {
                    sx.notify.error(response && response.message ? response.message : 'Не удалось перенести задачу.');
                } else {
                    (response.warnings || []).forEach(function (text) { sx.notify.info(text); });
                    sx.notify.success('Задача перенесена на ' + response.task.start_text + '.');
                }
            }).fail(function () {
                sx.notify.error('Не удалось перенести задачу.');
            }).always(function () {
                blocker.unblock();
                setTimeout(function () { window.location.reload(); }, 500);
            });
        },

        /**
         * При перезагрузке (закрытие окна задачи, сохранение порядка, F5) возвращаем прежнюю позицию,
         * при обычном открытии страница показывается сверху (просроченные свёрнуты до трёх).
         * Штатное восстановление скролла браузером на длинном списке срабатывает ненадежно.
         */
        _initScrollPosition: function()
        {
            var self = this;
            var storageKey = "sx-task-calendar-scroll:" + window.location.pathname + window.location.search;

            var navigationType = "navigate";
            try {
                var navigation = window.performance.getEntriesByType("navigation")[0];
                if (navigation) {
                    navigationType = navigation.type;
                } else if (window.performance.navigation) {
                    navigationType = {1: "reload", 2: "back_forward"}[window.performance.navigation.type] || "navigate";
                }
            } catch (e) {}

            var savedTop = null;
            try {
                savedTop = window.sessionStorage.getItem(storageKey);
                window.sessionStorage.removeItem(storageKey);
            } catch (e) {}

            if ("scrollRestoration" in window.history) {
                window.history.scrollRestoration = "manual";
            }

            $(window).on("pagehide beforeunload", function() {
                try {
                    window.sessionStorage.setItem(storageKey, String(Math.round(window.pageYOffset)));
                } catch (e) {}
            });

            if (savedTop !== null && navigationType !== "navigate") {
                window.scrollTo(0, parseInt(savedTop, 10) || 0);
                return;
            }

            // A plain opening shows the page from the top: overdue tasks are folded to three, the day follows them
            // (owner, 31.10.2026: no jump to the current day).
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
                    ->with(\skeeks\cms\widgets\admin\TaskViewContext::ROW_RELATIONS)
                    ->orderBy(['last_start_at' => SORT_DESC])
                    ->all();

                // Every active task has a start (TaskPlanner). The page does not replan: tasks move only on events.

                $tasksQuery = \skeeks\cms\models\CmsTask::find()
                    ->where(['executor_id' => $model->id])
                    ->andWhere(['status' => \skeeks\cms\models\CmsTask::activeStatuses()])
                    ->with(\skeeks\cms\widgets\admin\TaskViewContext::ROW_RELATIONS)
                    ->orderBy(['plan_start_at' => SORT_ASC, 'executor_sort' => SORT_ASC, 'id' => SORT_ASC]);
                $tasks = $widget->context->applySearch($tasksQuery)->all();

                // Two weeks of days; later tasks are counted and open in the calendar (a search shows every day found).
                $isSearch = $widget->context->q !== '';
                $lastShownDate = date('Y-m-d', strtotime('+13 day'));

                $now = time();
                $expiredTasks = [];
                $tasksByDate = [];
                foreach ($tasks as $task) {
                    $isLate = (int)$task->plan_start_at < $now - 60
                        && $task->status !== \skeeks\cms\models\CmsTask::STATUS_IN_WORK
                        && (!$task->hasAttribute('is_fixed_start') || $task->is_fixed_start);
                    if ($isLate || !$task->plan_start_at) {
                        $expiredTasks[] = $task;
                        continue;
                    }
                    $tasksByDate[\Yii::$app->formatter->asDate(max((int)$task->plan_start_at, $now), "php:Y-m-d")][] = $task;
                }
                $lastDate = $tasksByDate ? max(array_keys($tasksByDate)) : null;

                /**
                 * One row of a task: the move handle for auto tasks (their order is the queue), the start time,
                 * a pin for a fixed time, the task and its status.
                 */
                $renderTaskRow = function (\skeeks\cms\models\CmsTask $task, bool $withDate = false, string $extraClass = '') use ($user) {
                    $isCan = \Yii::$app->user->id == $user->id || \Yii::$app->user->can("cms/admin-task/manage", ['model' => $task]);
                    $isFixed = !$task->hasAttribute('is_fixed_start') || (bool)$task->is_fixed_start;
                    $isInWork = $task->status == \skeeks\cms\models\CmsTask::STATUS_IN_WORK;
                    // Every task but the one in work moves: an auto one changes the queue, a fixed one goes to another day.
                    $class = [$isInWork ? 'sx-task-row-fixed' : ($isFixed ? 'sx-task-tr sx-task-tr--fixed' : 'sx-task-tr')];
                    if (!$isCan) {
                        $class[] = 'sx-task-hidden';
                    }
                    if ($isInWork) {
                        $class[] = 'sx-row-in-work';
                    }
                    if ($extraClass !== '') {
                        $class[] = $extraClass;
                    }
                    $start = (int)$task->plan_start_at;
                    $time = $start ? \Yii::$app->formatter->asTime($start, 'php:H:i') : '—';
                    $hint = $isFixed ? 'Закреплено на это время' : 'Рассчитано по графику: время сдвигается вместе с очередью';
                    if ($isInWork) {
                        $hint = 'В работе с этого времени';
                    }
                    ob_start();
                    echo \yii\helpers\Html::beginTag('tr', ['class' => implode(' ', $class), 'data' => ['id' => $task->id, 'executor_sort' => $task->executor_sort]]);
                    ?>
                    <td class="sx-task-calendar__handle-cell">
                        <? if (!$isInWork && $isCan) : ?>
                            <span title="Перетащите, чтобы изменить очередь">
                                <a href="#" class="btn sx-move-btn sx-task-calendar__move"><?= \skeeks\cms\backend\helpers\BackendIcon::render('grip', ['size' => 16]); ?></a>
                            </span>
                        <? endif; ?>
                    </td>
                    <td class="sx-task-calendar__time-cell">
                        <span class="sx-task-calendar__time <?= $isFixed ? 'is-fixed' : 'is-auto'; ?> <?= $withDate ? 'is-dated' : ''; ?>" title="<?= \yii\helpers\Html::encode($hint); ?>" data-toggle="tooltip">
                            <? if ($withDate && $start) : ?>
                                <span class="sx-task-calendar__time-date"><?= \Yii::$app->formatter->asDate($start, 'php:d.m'); ?></span>
                            <? endif; ?>
                            <?= $time; ?>
                            <? if ($isFixed) : ?><span class="sx-task-calendar__pin"><?= \skeeks\cms\models\CmsTask::icon('pin', 11); ?></span><? endif; ?>
                        </span>
                        <span class="sx-task-calendar__duration"><?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText((int)$task->plan_duration); ?></span>
                    </td>
                    <td class="sx-task-td">
                        <?= \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $task]); ?>
                    </td>
                    <td class="sx-task-calendar__status-cell">
                        <?= \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $task, 'isShort' => true]); ?>
                    </td>
                    <?
                    echo \yii\helpers\Html::endTag('tr');

                    return ob_get_clean();
                };

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
                    <?
                    // Overdue tasks are folded to three (a search shows them all): the day's work comes first.
                    $expiredShown = $isSearch ? count($expiredTasks) : 3;
                    $expiredMore = max(0, count($expiredTasks) - $expiredShown);
                    ?>
                    <table class="table sx-table sx-calendar-day sx-expired-tasks <?= $expiredMore ? 'is-folded' : ''; ?>">
                        <thead>
                        <tr>
                            <th class="text-center" colspan="4">Просроченные задачи · <?= count($expiredTasks); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <? foreach (array_values($expiredTasks) as $index => $task) : ?>
                            <?= $renderTaskRow($task, true, $index >= $expiredShown ? 'sx-task-expired-more' : ''); ?>
                        <? endforeach; ?>
                        </tbody>
                        <? if ($expiredMore) : ?>
                            <tfoot>
                            <tr class="sx-expired-tasks__toggle">
                                <td colspan="4">
                                    <button type="button" class="btn btn-default btn-sm"
                                            onclick="var t = $(this).closest('table').toggleClass('is-folded'); $(this).text(t.hasClass('is-folded') ? $(this).data('more') : 'Свернуть'); return false;"
                                            data-more="Показать ещё <?= $expiredMore; ?>">Показать ещё <?= $expiredMore; ?></button>
                                </td>
                            </tr>
                            </tfoot>
                        <? endif; ?>
                    </table>
                <? endif; ?>

                <? for ($i = 0; $i <= 1000; $i++) : ?>
                    <?

                    $workShedule = $user->work_shedule;

                    //День в цикле
                    $date = date("Y-m-d", strtotime("+{$i} day"));
                    // Every day is shown, empty ones too: a week at least, then up to the last task.
                    if ($i > 6 && (!$lastDate || $date > $lastDate)) {
                        break;
                    }
                    // A search shows only the days with tasks found.
                    if ($isSearch && empty($tasksByDate[$date])) {
                        if (!$lastDate || $date > $lastDate) {
                            if (!$tasksByDate && !$expiredTasks) {
                                echo '<div class="sx-task-calendar__later text-center text-muted">Ничего не найдено</div>';
                            }
                            break;
                        }
                        continue;
                    }
                    if (!$isSearch && $date > $lastShownDate) {
                        $laterCount = 0;
                        foreach ($tasksByDate as $taskDate => $dateTasks) {
                            if ($taskDate >= $date) {
                                $laterCount += count($dateTasks);
                            }
                        }
                        if ($laterCount) {
                            echo '<div class="sx-task-calendar__later text-center text-muted">Дальше ещё задач: '.$laterCount
                                .' — <a href="'.\yii\helpers\Html::encode(\yii\helpers\Url::to(['/cms/admin-cms-task/tasks-calendar', 'executor_id' => $model->id, 'date' => $date])).'" data-pjax="0">открыть в календаре</a></div>';
                        }
                        break;
                    }
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


                <table class="table sx-table sx-calendar-day <?= !$times  ? "sx-not-work-day" : ""; ?> <?= $isToday ? "sx-today-day" : "sx-not-today-day"; ?>">
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
                                        <small data-toggle="tooltip" title="Сегодня отработал сверх плана: <br /><br /><?= \skeeks\cms\helpers\CmsScheduleHelper::getAsTextBySchedules(\skeeks\cms\models\CmsUserSchedule::find()->user($user)->today()->all()); ?>" data-html="true">
                                            (отработал сверх плана: <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($workedSeconds - $seconds); ?>)
                                        </small>
                                    <? else: ?>
                                        <small data-toggle="tooltip" title="Не доработано: <br /><br /><?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($seconds - $workedSeconds); ?>" data-html="true">
                                            (Не доработано: <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($seconds - $workedSeconds); ?>)
                                        </small>
                                    <? endif; ?>

                                <? endif; ?>


                            <? else : ?>
                                <a href="<?= \yii\helpers\Url::to(['/cms/admin-user/update', 'pk' => $model->id]); ?>" class="sx-task-calendar__header-link" target="_blank">
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

                        $qt = \skeeks\cms\models\CmsTask::find()->with(\skeeks\cms\widgets\admin\TaskViewContext::ROW_RELATIONS)
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

                    <tbody data-day="<?= $date; ?>">
                    <? if (!empty($tasksByDate[$date])) : ?>
                        <? foreach ($tasksByDate[$date] as $task) : ?>
                            <?= $renderTaskRow($task); ?>
                        <? endforeach; ?>
                    <? else : ?>
                        <tr class="sx-task-calendar__empty-row">
                            <td class="text-center sx-task-calendar__empty" colspan="4"><?= $times ? 'Задач нет' : 'Не делает задачи в этот день'; ?></td>
                        </tr>
                    <? endif; ?>
                    </tbody>
                    </table>
                <? endfor; ?>

            </div>
        </div>

    </div>
</div>

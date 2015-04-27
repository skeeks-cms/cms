<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
/* @var $this yii\web\View */

$allCkecks = [];
?>
<h2>Полное тестирование системы</h2>
<p>
    Полная проверка системы помогает найти причины проблем в работе сайта и избежать появление ошибок в дальнейшем. Справка по каждому тесту поможет устранить причину ошибки.
</p>
<div class="sx-box sx-p-10 sx-bg-primary">
    <p>
        <a href="#" class="btn btn-primary sx-controll-start"><i class="glyphicon glyphicon-play"></i> Начать тестирование</a>
        <a href="#" class="btn btn-default sx-controll-stop"><i class="glyphicon glyphicon-stop"></i> Остановить</a>
    </p>

    <p>
        <div class="sx-progress-tasks" id="sx-progress-tasks" style="display: none;">
            <span style="vertical-align:middle;"><h3>Тестирование системы (Выполнено <span class="sx-executing-ptc"></span>%)</h3></span>
            <span style="vertical-align:middle;">Тест: <span class="sx-executing-task-name"></span></span>
            <div>
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>
    </p>

    <? foreach(\Yii::$app->cms->getModules() as $module) : ?>

        <? if ($checks = $module->loadChecksComponents()) : ?>
            <? $allCkecks = \yii\helpers\ArrayHelper::merge($allCkecks, $checks); ?>
            <hr />
            <h3>Модуль <?= $module->getName(); ?> (проверок: <?= count($checks); ?>)</h3>
            <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
                'dataProvider' => new \yii\data\ArrayDataProvider([
                    'allModels' => $checks
                ]),
                'layout' => "{items}",
                'columns' =>
                [
                    [
                        'attribute' => "name",
                        'label'     => 'Тест'
                    ],

                    [
                        'class'     => \yii\grid\DataColumn::className(),
                        'value'     => function(\skeeks\cms\base\CheckComponent $model)
                        {
                            $resultId = str_replace("\\", '-', $model->className());

                            $result = \yii\helpers\Html::tag("div", "-", [
                                'class' => '',
                                'id'    => $resultId,
                            ]);

                            return $result;
                        },
                        'format' => 'raw'
                    ],


                    [
                        'class'     => \yii\grid\DataColumn::className(),
                        'value'     => function(\skeeks\cms\base\CheckComponent $model)
                        {
                            $infoId = str_replace("\\", '-', $model->className()) ."-info";
                            $info = \yii\helpers\Html::tag("div", $model->description, [
                                'class' => '',
                                'id'    => $infoId,
                            ]);
                            $infoWrapper = \yii\helpers\Html::tag("div", $info, [
                                'style' => 'display: none;',
                            ]);

                            return \yii\helpers\Html::a("<i class='glyphicon glyphicon-exclamation-sign'></i>", "#" . $infoId, [
                                'class' => 'btn btn-default sx-fancybox'
                            ]) . $infoWrapper;
                        },
                        'format' => 'raw'
                    ],
                ]
            ])?>
        <? endif; ?>

    <? endforeach; ?>
</div>

<?
\skeeks\cms\assets\JsTaskManagerAsset::register($this);

$checksAll = [];
foreach ($allCkecks as $key => $check)
{
    $data = (array) $check;
    $data['className']  = $check->className();
    $data['id']         = str_replace("\\", "-", $check->className());
    $checksAll[$key] = $data;
}
$allCkecksJson = \yii\helpers\Json::encode($checksAll);


$this->registerJs(<<<JS
(function(sx, $, _)
{

    sx.classes.CheckerProgressBar = sx.classes.tasks.ProgressBar.extend({

        _init: function()
        {
            var self = this;
            this.applyParentMethod(sx.classes.tasks.ProgressBar, '_init', []);

            this.bind('update', function(e, data)
            {
                $(".sx-executing-task-name", self.getWrapper()).empty().append(data.Task.get('name'));
                $(".sx-executing-ptc", self.getWrapper()).empty().append(self.getExecutedPtc());
            });
        }

    });

    /**
     * Задача которую необходимо выполнить.
     */
    sx.classes.CheckerTask = sx.classes.tasks.AjaxTask.extend({

    });

    sx.classes.Checker = sx.classes.Component.extend({

        _init: function()
        {
            this.initTaskManager();
        },

        initTaskManager: function()
        {
            this.TaskManager = new sx.classes.tasks.Manager({
                'tasks' : [],
                'delayQueque' : 3000
            });

            this.ProgressBar = new sx.classes.CheckerProgressBar(this.TaskManager, "#sx-progress-tasks");
        },

        /**
        *
        * @returns {sx.classes.Checker}
        */
        resetTaskManager: function()
        {
            var tasks = [];

            _.each(this.get('checks'), function(value, key)
            {
                var ajaxQuery = sx.ajax.preparePostQuery("/", {
                    'className' : value.className
                });

                var handler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {});

                var jResultContainer = $("#" + value.id);

                ajaxQuery.bind("beforeSend", function(e, data)
                {
                    jResultContainer.empty().append('Проверяется...');
                });

                ajaxQuery.bind("success", function(e, data)
                {
                    jResultContainer.empty().append('Хорошо');
                });

                ajaxQuery.bind("error", function(e, response)
                {
                    jResultContainer.empty().append('Ошибка выполнения запроса');
                });

                tasks.push(new sx.classes.CheckerTask(ajaxQuery, value));
            });

            this.TaskManager.setTasks(tasks);

            return this;
        },

        _onDomReady: function()
        {
            var self = this;

            this.jControllStart = $(".sx-controll-start");
            this.jControllStop = $(".sx-controll-stop");

            this.jControllStart.on("click", function()
            {
                self.start();
                return false;
            });

            this.jControllStop.on("click", function()
            {
                self.stop();
                return false;
            });

        },


        _onWindowReady: function()
        {},


        start: function()
        {
            this.resetTaskManager();
            this.TaskManager.start();
            return this;
        },

        stop: function()
        {
            this.TaskManager.stop();
            return this;
        },
    });

    sx.Checker = new sx.classes.Checker({
        'checks' : $allCkecksJson
    });

})(sx, sx.$, sx._);
JS
);
?>





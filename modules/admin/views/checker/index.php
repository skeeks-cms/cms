<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
/* @var $this yii\web\View */

$allCkecks = [];

$this->registerCss(<<<CSS
.sx-first-column
{
    width: 30%;
}

.sx-last-column
{
    width: 50px;
}
CSS
);
?>
<h2>Полное тестирование системы</h2>
<p>
    Полная проверка системы помогает найти причины проблем в работе сайта и избежать появление ошибок в дальнейшем. Справка по каждому тесту поможет устранить причину ошибки.
</p>
<div class="sx-box sx-p-10 sx-bg-primary sx-checker">
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
                                'class' => 'sx-result-container',
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
                            $optionsJson = \yii\helpers\Json::encode([
                                'title' => $model->name,
                                'content' => $model->description,
                            ]);

                            return \yii\helpers\Html::a("<i class='glyphicon glyphicon-question-sign'></i>", "#", [
                                'class' => 'btn btn-default',
                                'onclick' => "sx.dialog({$optionsJson}); return false;"
                            ]);
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

$optionsJs = [
    'checks' => $checksAll,
    'backend' => \skeeks\cms\helpers\UrlHelper::construct('admin/checker/check-test')->enableAdmin()->toString(),
];

$optionsJsJson = \yii\helpers\Json::encode($optionsJs);


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

        _initQuery: function()
        {
            var self = this;

            this.get("ajaxQuery").onSuccess(function(e, data)
            {
                if (data.response.success)
                {
                    var checkerData = data.response.data

                    //Тест еще не завершен нужно сделать еще один запрос.
                    if (checkerData.lastValue)
                    {
                        self.get("ajaxQuery").mergeData({
                            'lastValue' : checkerData.lastValue
                        });

                        self.trigger("update", {
                            'task'      : self,
                            'data'      : checkerData
                        });

                        self.get("ajaxQuery").execute();

                    } else
                    {
                        self.trigger("complete", {
                            'task'      : self,
                            'result'    : data
                        });
                    }
                } else
                {
                    self.trigger("complete", {
                        'task'      : self,
                        'result'    : data
                    });
                }
            });

            this.get("ajaxQuery").onError(function(e, data)
            {
                self.trigger("complete", {
                    'task'      : self,
                    'result'    : data
                });
            });

            return this;
        },

        execute: function()
        {
            var self = this;

            this.trigger("beforeExecute", {
                'task' : this
            });

            this.get("ajaxQuery").execute();
        }

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
                'delayQueque' : 200
            });

            this.ProgressBar = new sx.classes.CheckerProgressBar(this.TaskManager, "#sx-progress-tasks");
        },

        /**
        *
        * @returns {sx.classes.Checker}
        */
        resetTaskManager: function()
        {
            var self = this;
            var tasks = [];

            _.each(this.get('checks'), function(value, key)
            {
                var ajaxQuery = sx.ajax.preparePostQuery(self.get('backend'), {
                    'className' : value.className
                });

                var handler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery, {
                    'allowResponseErrorMessage' : false,
                    'allowResponseSuccessMessage' : false,
                    'ajaxExecuteErrorAllowMessage' : false,
                });

                var jResultContainer = $("#" + value.id);

                ajaxQuery.bind("beforeSend", function(e, data)
                {
                    jResultContainer.empty().append('Проверяется...');
                });

                ajaxQuery.bind("success", function(e, data)
                {
                    var response = data.response;
                    if (response.success === true)
                    {
                        if (response.data.result == 'success')
                        {
                            jResultContainer.empty().append('<span class="label label-success"><i class="glyphicon glyphicon-ok"></i> ' + response.data.successText + '</span>');

                            if (_.size(response.data.successMessages) > 0)
                            {
                                jResultContainer.append("<hr>");
                                var jUl = $("<ul>").appendTo(jResultContainer);
                                _.each(response.data.successMessages, function(value, key)
                                {
                                    jUl.append('<li> ' + value + '</li>');
                                });
                            }

                        } else if (response.data.result == 'error')
                        {
                            jResultContainer.empty().append('<span class="label label-danger"><i class="glyphicon glyphicon-sign"></i> ' + response.data.errorText + '</span>');

                            if (response.data.errorMessages)
                            {
                                jResultContainer.append("<hr>");
                                var jUl = $("<ul>").appendTo(jResultContainer);
                                _.each(response.data.errorMessages, function(value, key)
                                {
                                    jUl.append('<li> ' + value + '</li>');
                                });
                            }
                        } else if (response.data.result == 'warning')
                        {
                            jResultContainer.empty().append('<span class="label label-warning"><i class="glyphicon glyphicon-sign"></i> ' + response.data.warningText + '</span>');
                        }
                    } else
                    {
                        jResultContainer.empty().append('<span class="label label-danger"><i class="glyphicon glyphicon-ban-circle"></i> ' + response.data.errorText + '</span>');
                    }
                });

                ajaxQuery.bind("error", function(e, response)
                {
                    jResultContainer.empty().append('<span class="label label-danger"><i class="glyphicon glyphicon-ban-circle"></i> Ошибка выполнения запроса</span>');
                });

                tasks.push(new sx.classes.CheckerTask(ajaxQuery, value));
            });

            this.TaskManager.setTasks(tasks);

            return this;
        },

        _onDomReady: function()
        {
            var self = this;

            $(".sx-checker table").each(function()
            {
                $(this).find("tr td:first").addClass('sx-first-column');
                $(this).find("tr td:last").addClass('sx-last-column');
            });

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
            $(".sx-result-container").empty().append(" - ");
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

    sx.Checker = new sx.classes.Checker($optionsJsJson);

})(sx, sx.$, sx._);
JS
);
?>





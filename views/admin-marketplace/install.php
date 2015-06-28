<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
/* @var $this yii\web\View */
/* @var string $packagistCode */
/* @var $packageModel PackageModel */

use \skeeks\cms\components\marketplace\models\PackageModel;
$self = $this;
?>

<div id="sx-search">

<!--<div class="sx-box sx-p-10 sx-mb-10 sx-bg-primary">
    <p>Вы можете производить установку любых решений, используя SkeekS CMS маркетплейс или минуя его.</p>
    <p>Для установки нового решения, вам необходимо указать его Packagist Code</p>
</div>-->

<? $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
    'method' => 'get',
    'options' =>
    [
        'id'    => 'sx-package-search',
        'class' => 'form-inline'
    ]
]); ?>

    <form class="form-inline">
      <div class="form-group">
        <div class="input-group">
          <div class="input-group-addon">Packagist code</div>
          <?= \yii\helpers\Html::textInput('packagistCode', $packagistCode, [
                'class' => 'form-control',
                'placeholder' => 'skeeks/cms'
            ]); ?>
        </div>
      </div>

        <?= \yii\helpers\Html::button('Найти', [
            'type'  => 'submit',
            'class' => 'btn btn-primary'
        ]); ?>
    </form>

    <? if ($packageModel) : ?>
        <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
            'dataProvider' => (new \yii\data\ArrayDataProvider([
                'allModels' => [$packageModel],
                'pagination' => [
                    'defaultPageSize' => 1
                ]
            ])),
            'layout' => "{items}",
            'columns' =>
            [
                [
                    'class' => \yii\grid\DataColumn::className(),
                    'value' => function(PackageModel $model) use ($self)
                    {
                        return $self->render('_package-column', [
                            'model' => $model
                        ]);
                    },
                    'format' => 'raw'
                ],

                [
                    'class' => \yii\grid\DataColumn::className(),
                    'value' => function(PackageModel $model)
                    {
                        if ($model->isInstalled())
                        {
                            return $model->createCmsExtension()->version;
                        } else
                        {
                            $code = $model->packagistCode;
                            return <<<HTML
<a data-pjax="0"  class="btn btn-default btn-danger" target="" title="" onclick="sx.Installer.start('{$code}'); return false;">
    <i class="glyphicon glyphicon-download-alt"></i> Запустить установку
</a>

HTML;
;
//                            <pre>
//php yii cms/update/install {$code}:*
//</pre>
                        }
                    },
                    'label' => 'Версия',
                    'format' => 'raw'
                ],

            ]
        ])?>
    <? endif; ?>
<? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>
</div>



<div class="sx-box sx-p-10 sx-mb-10" style="display: none;" id="sx-installer">
    <h2>Процесс установки</h2>
    <?=
        \yii\bootstrap\Alert::widget([
            'options' => [
              'class' => 'alert-danger',
          ],
          'body' => \yii\helpers\Html::tag("div", 'Идет процесс установки нового пакета, пожалуйста не закрывайте эту страницу.
                                                    Это может привести к печальным последствиям работы вашего сайта.'),
        ]);
    ?>

    <div class="sx-progress-tasks" id="sx-progress-tasks" style="display: none;">
        <span style="vertical-align:middle;"><h3>Процесс установки расширения (Выполнено <span class="sx-executing-ptc">0</span>%)</h3></span>
        <span style="vertical-align:middle;">Этап: <span class="sx-executing-task-name"></span></span>
        <div>
            <div class="progress progress-striped active">
                <div class="progress-bar progress-bar-success"></div>
            </div>
        </div>
        <hr />
    </div>
    <div id="sx-tmp-result"></div>
    <div id="sx-ssh-console-wrapper" style="display: none;">
        <?=
            \skeeks\cms\widgets\ssh\SshConsoleWidget::widget([
                'enabledTabs' => \skeeks\cms\components\Cms::BOOL_N,
                'consoleHeight' => '400px;'
            ]);
        ?>
    </div>

</div>

<?
    \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-info',
      ]
    ]);
?>
    <p>Установка пакетов обычно длится 1-5 минут.</p>
    <p>В процессе установки будут изменены все ваши модификации ядра.</p>
    <p>Будет создана резервная копия базы данных</p>
    <p>сброшен кэш</p>
    <p>Удалены временные файлы</p>
<? \yii\bootstrap\Alert::end(); ?>


<?
\skeeks\cms\assets\JsTaskManagerAsset::register($this);


$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.InstallerProgressBar = sx.classes.tasks.ProgressBar.extend({

        _init: function()
        {
            var self = this;

            this.applyParentMethod(sx.classes.tasks.ProgressBar, '_init', []);

            this.bind('update', function(e, data)
            {
                $(".sx-executing-task-name", self.getWrapper()).empty().append(data.Task.get('name'));
            });

            this.bind('updateProgressBar', function(e, data)
            {
                $(".sx-executing-ptc", self.getWrapper()).empty().append(self.getExecutedPtc());
            });
        }

    });

    sx.classes.Installer = sx.classes.Component.extend({

        _init: function()
        {
            var self = this;
            this.SearchBlocker = null;

            this.TaskManager = new sx.classes.tasks.Manager({
                'tasks' : [],
                'delayQueque' : 500
            });

            this.ProgressBar = new sx.classes.InstallerProgressBar(this.TaskManager, "#sx-progress-tasks");

            this.TaskManager.bind('start', function()
            {
                $('#sx-installer').show();
                sx.App.Menu.block();

                self.SearchBlocker = new sx.classes.Blocker('#sx-search');
                self.SearchBlocker.block();
            });

            this.TaskManager.bind('stop', function()
            {
                self.SearchBlocker.unblock();
                sx.App.Menu.unblock();

                $('#sx-installer').hide();

                sx.notify.success('Установка пакета завершена');

                _.delay(function()
                {
                    $('#sx-package-search').submit();
                }, 500);
            });

            this.TaskManager.bind('beforeExecuteTask', function(e, data)
            {
                //$('.sx-tmp-result').empty().append(data.task.get('name'));
            });
        },

        start: function(packageName)
        {
            var tasks = [];

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Подготовка к установке пакета',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка системы, окружения',
                'delay':2000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка совместимости пакета',
                'delay':3000
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Запуска ssh консоли',
                'delay':1000,
                'callback':function()
                {
                    var jSshConsole = $('#sx-ssh-console-wrapper');
                    jSshConsole.show();
                }
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/composer/revert-modified-files',
                'name':'Откат модификаций ядра',
                'delay': 1500
            }));

            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Подключение к серверу обновлений',
                'delay':2000
            }));


            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/update/install ' + packageName,
                'name':'Скачивание пакета и его установка',
                'delay': 500
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/dump-list',
                'name':'Создание резервной копии базы данных',
                'delay': 1500
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/utils/generate-modules-config-file',
                'name':'Генерация файла со списком модулей',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/apply-migrations',
                'name':'Установка всех миграций базы данных',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/utils/clear-runtimes',
                'name':'Чистка временных диррикторий',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/db/db-refresh',
                'name':'Сброс кэша стрктуры базы данных',
            }));

            tasks.push(new sx.classes.InstallerTaskConsole({
                'cmd':'php yii cms/rbac/init',
                'name':'Обновление привилегий',
            }));


            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Проверка установленного решения',
                'delay':3000
            }));
            tasks.push(new sx.classes.InstallerTaskClean({
                'name':'Завершение процесса установки',
                'delay':3000
            }));


            this.TaskManager.setTasks(tasks);
            this.TaskManager.start();
        },

    });

    sx.classes.InstallerTask = sx.classes.tasks.AjaxTask.extend({});
    sx.classes.InstallerTaskClean = sx.classes.tasks.Task.extend({
        execute: function()
        {
            var self = this;

            this.trigger("beforeExecute", {
                'task' : this
            });

            var result = {'message': 'Задача выполнена'};

            if (this.get('callback'))
            {
                var callback = this.get('callback');
                callback();
            }

            _.delay(function()
            {
                self.trigger("complete", {
                    'task'      : self,
                    'result'    : result
                });
            }, this.get('delay', 2000));
        }
    });

    sx.classes.InstallerTaskConsole = sx.classes.tasks.Task.extend({
        execute: function()
        {
            var self = this;

            this.trigger("beforeExecute", {
                'task' : this
            });

            sx.SshConsole.unbind('success');
            sx.SshConsole.unbind('error');

            _.delay(function()
            {
                sx.SshConsole.execute(self.get('cmd'));

            }, this.get('delay', 1000));


            sx.SshConsole.bind('success', function()
            {
                _.delay(function()
                {
                    self.trigger("complete", {
                        'task'      : self,
                        'result'    : {}
                    });
                }, self.get('delay', 1000));
            });

            sx.SshConsole.bind('error', function()
            {
                _.delay(function()
                {
                    self.trigger("complete", {
                        'task'      : self,
                        'result'    : {}
                    });
                }, self.get('delay', 1000));
            });

        }
    });

    sx.Installer = new sx.classes.Installer();

})(sx, sx.$, sx._);
JS
);
?>

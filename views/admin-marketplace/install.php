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

$attrDisabled = '';
if (!\Yii::$app->user->can('admin/ssh'))
{
    $attrDisabled = 'disabled="disabled"';
}

?>

<div id="sx-search">

<? $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
    'method' => 'get',
    'options' =>
    [
        'id'    => 'sx-package-search',
        'class' => 'form-inline'
    ]
]); ?>

      <div class="form-group">
        <div class="input-group">
          <div class="input-group-addon">Код решения</div>
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
                            return ' — '
;
                        }
                    },
                    'label' => 'Версия',
                    'format' => 'raw'
                ],

                [
                    'class' => \yii\grid\DataColumn::className(),
                    'value' => function(PackageModel $model) use ($attrDisabled)
                    {
                        if ($model->isInstalled())
                        {
                            $extension = $model->createCmsExtension();
                            $code = $model->packagistCode;


                            if ($extension->canDelete())
                            {
                                if (!$attrDisabled)
                                {
                                    $class = ' btn-danger';
                                }

                                return <<<HTML
                                <a data-pjax="0"  class="btn btn-default {$class}" target="" title="" onclick="sx.Installer.remove('{$code}'); return false;" {$attrDisabled}>
                                    <i class="glyphicon glyphicon-remove"></i> Запустить удаление
                                </a>
HTML;
;
                            }
                        } else
                        {
                            if (!$attrDisabled)
                            {
                                $class = ' btn-success';
                            }

                            $code = $model->packagistCode;
                            return <<<HTML
                                <a data-pjax="0"  class="btn btn-default btn-success" target="" title="" onclick="sx.Installer.install('{$code}'); return false;" {$attrDisabled}>
                                    <i class="glyphicon glyphicon-download-alt"></i> Запустить установку
                                </a>
HTML;
;
                        }
                    },
                    'label' => 'Действия',
                    'format' => 'raw'
                ],

            ]
        ])?>
    <? endif; ?>
<? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>
</div>


<?= \skeeks\cms\widgets\installer\InstallerWidget::widget(); ?>
<?
$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.InstallerRemover = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            var self = this;
            self.SearchBlocker = null;
            sx.Installer.TaskManager.bind('start', function()
            {
                self.SearchBlocker = new sx.classes.Blocker('#sx-search');
                self.SearchBlocker.block();
            });

            sx.Installer.TaskManager.bind('stop', function()
            {
                self.SearchBlocker.unblock();
                _.delay(function()
                {
                    $('#sx-package-search').submit();
                }, 500);
            });
        },

        _onWindowReady: function()
        {}
    });

    new sx.classes.InstallerRemover();

})(sx, sx.$, sx._);
JS
)
?>

<?
    \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-info',
      ]
    ]);
?>
    <p>Установка/Удаление пакетов обычно длится 1-5 минут.</p>
    <p>В процессе установки будут изменены все ваши модификации ядра.</p>
    <p>Будет создана резервная копия базы данных</p>
    <p>Сброшен кэш</p>
    <p>Удалены временные файлы</p>

<? \yii\bootstrap\Alert::end(); ?>



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

$attrDisabled = [];
if (!\Yii::$app->user->can('admin/ssh'))
{
    $attrDisabled = ['disabled' => 'disabled'];
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
        <?= \yii\helpers\Html::button('<i class="glyphicon glyphicon-retweet"></i> Запустить обновление',
        \yii\helpers\ArrayHelper::merge([
            'type'  => 'submit',
            'class' => 'btn btn-lg ' . ($attrDisabled ?: 'btn-primary'),
            'onclick' => new \yii\web\JsExpression(<<<JS
            sx.Installer.update(); return false;
JS
)

        ], $attrDisabled)

    ); ?>
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
    <p>Обновление платформы обычно длится 1-5 минут.</p>
    <p>В процессе обновления будут изменены все ваши модификации ядра.</p>
    <p>Будет создана резервная копия базы данных</p>
    <p>Сброшен кэш</p>
    <p>Удалены временные файлы</p>
    <p>Обновлены все подключеные пакеты</p>

<? \yii\bootstrap\Alert::end(); ?>



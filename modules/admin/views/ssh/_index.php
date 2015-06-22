<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\modules\admin\models\forms\SshConsoleForm */
use skeeks\cms\modules\admin\widgets\ActiveForm;
use \yii\helpers\Html;

use Yii;
?>

<div class="sx-widget-ssh-console">
    <? $form = ActiveForm::begin() ?>
        <?= $form->field($model, 'command')->textarea([
            'placeholder' => 'php yii help'
        ]); ?>
        <div class="form-group sx-commands">
            <button type="button" class="btn btn-default btn-xs" data-sx-widget="tooltip" title="Выполнить комманду" data-original-title="Выполнить комманду">php yii</button>
            <button type="button" class="btn btn-default btn-xs" data-sx-widget="tooltip" title="Выполнить комманду" data-original-title="Выполнить комманду">php yii help cms/update</button>
            <button type="button" class="btn btn-default btn-xs" data-sx-widget="tooltip" title="Выполнить комманду" data-original-title="Выполнить комманду">php yii cms/update</button>
            <button type="button" class="btn btn-default btn-xs" data-sx-widget="tooltip" title="Выполнить комманду" data-original-title="Выполнить комманду">php yii cms/update/all --interactive=0</button>
        </div>
        <?= Html::tag('div',
            Html::submitButton("Выполнить команду", ['class' => 'btn btn-primary']),
            ['class' => 'form-group']
        ); ?>

        <div class="sx-result-container">
            <pre id="sx-result">
<?= $result; ?>
            </pre>
        </div>

        <? $this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.Console = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            jQuery('.sx-commands button').on('click', function()
            {
                $(".sx-widget-ssh-console textarea").empty().append($(this).text());
            });
        },

        _onWindowReady: function()
        {}
    });
    new sx.classes.Console();
})(sx, sx.$, sx._);
JS
);
        ?>
    <? ActiveForm::end() ?>

    <hr />
    root dir: <?= ROOT_DIR; ?>
</div>


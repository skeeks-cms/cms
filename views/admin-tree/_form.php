<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Tree */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'files')->widget(\skeeks\cms\widgets\formInputs\StorageImages::className())->label(false); ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>



<?= Html::checkbox("isLink", $model->isLink(), [
    'value'     => '1',
    'label'     => 'Этот раздел является ссылкой',
    'class'     => 'smartCheck',
    'id'        => 'isLink',
]); ?>

<div data-listen="isLink" data-show="0" class="sx-hide">
<?= $form->field($model, 'type')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \Yii::$app->registeredModels->getDescriptor($model)->getTypes()->getComponents(),
                 "id",
                 "name"
             ),
    ])->label('Тип раздела')->hint('От выбранного типа раздела может зависеть, то, как она будет отображаться.');
?>



<?= $form->field($model, 'tree_ids')->widget(
    \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
    [
        'mode' => \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_MULTI
    ])->label('Дополнительные разделы сайта')->hint('Дополнительные разделы сайта, где бы хотелось видеть этот раздел.');
?>

<?= $form->field($model, 'tree_menu_ids')->label('Меню')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \skeeks\cms\models\TreeMenu::find()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true
    ])->hint('Вы можете выбрать один или несколько меню, в которых будет показываться этот раздел');
?>
<!--
--><?/*= $form->field($model, 'priority')->label("Приоритет")->hint("Вы можете оставить это поле пустым, оно будет влиять на порядок в некоторых случаях")->textInput([
    'maxlength' => 255
]) */?>
</div>

<div data-listen="isLink" data-show="1" class="sx-hide">
<?= $form->field($model, 'redirect', [

])->textInput(['maxlength' => 500])->label('Редиррект') ?>
</div>

<?= $form->buttonsCreateOrUpdate($model); ?>

<? $this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.createNamespace('classes', sx);

        sx.classes.SmartCheck = sx.classes.Component.extend({

            _init: function()
            {},

            _onDomReady: function()
            {
                var self = this;

                this.JsmartCheck = $('.smartCheck');

                self.updateInstance($(this.JsmartCheck));

                this.JsmartCheck.on("change", function()
                {
                    self.updateInstance($(this));
                });
            },

            updateInstance: function(JsmartCheck)
            {
                if (!JsmartCheck instanceof jQuery)
                {
                    throw new Error('1');
                }

                var id  = JsmartCheck.attr('id');
                var val = Number(JsmartCheck.is(":checked"));

                if (!id)
                {
                    return false;
                }

                if (val == 0)
                {
                    $('#tree-redirect').val('');
                }

                $('[data-listen="' + id + '"]').hide();
                $('[data-listen="' + id + '"][data-show="' + val + '"]').show();

            },
        });

        new sx.classes.SmartCheck();
    })(sx, sx.$, sx._);
JS
);
?>
<?php ActiveForm::end(); ?>
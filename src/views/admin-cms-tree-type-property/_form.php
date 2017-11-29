<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model Tree */
?>


<?php $form = ActiveForm::begin([
    'id' => 'sx-dynamic-form',
    'enableAjaxValidation' => false,
]); ?>

<?php $this->registerJs(<<<JS

(function(sx, $, _)
{
    sx.classes.DynamicForm = sx.classes.Component.extend({

        _onDomReady: function()
        {
            var self = this;

            $("[data-form-reload=true]").on('change', function()
            {
                self.update();
            });
        },

        update: function()
        {
            _.delay(function()
            {
                var jForm = $("#sx-dynamic-form");
                jForm.append($('<input>', {'type': 'hidden', 'name' : 'sx-not-submit', 'value': 'true'}));
                jForm.submit();
            }, 200);
        }
    });

    sx.DynamicForm = new sx.classes.DynamicForm();
})(sx, sx.$, sx._);


JS
); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Basic settings')) ?>

<?= $form->fieldRadioListBoolean($model, 'active') ?>
<?= $form->fieldRadioListBoolean($model, 'is_required') ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'code')->textInput() ?>


<?= $form->field($model, 'component')->listBox(array_merge(['' => ' — '],
    \Yii::$app->cms->relatedHandlersDataForSelect), [
    'size' => 1,
    'data-form-reload' => 'true'
])
    ->label(\Yii::t('skeeks/cms', "Property type"));
?>

<?php if ($handler) : ?>
    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => \Yii::t('skeeks/cms', 'Settings')]); ?>
    <?php if ($handler instanceof \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList) : ?>
        <?php $handler->enumRoute = 'cms/admin-cms-tree-type-property-enum'; ?>
    <?php endif; ?>
    <?= $handler->renderConfigForm($form); ?>
<?php endif; ?>


<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Additionally')) ?>
<?= $form->field($model, 'hint')->textInput() ?>
<?= $form->fieldInputInt($model, 'priority') ?>

<?php if ($content_id = \Yii::$app->request->get('tree_type_id')) : ?>

    <div style="display: none;">
        <?= $form->field($model, 'cmsTreeTypes')->checkboxList(\yii\helpers\ArrayHelper::map(
            \skeeks\cms\models\CmsTreeType::find()->all(), 'id', 'name'
        ), ['value' => $content_id]); ?>
    </div>

    <?php /*= $form->field($model, 'tree_type_id')->hiddenInput(['value' => $content_id])->label(false); */ ?>

<?php else
    : ?>

    <?= $form->field($model, 'cmsTreeTypes')->widget(
        \skeeks\widget\chosen\Chosen::class,
        [
            'multiple' => true,
            'items' => \yii\helpers\ArrayHelper::map(
                \skeeks\cms\models\CmsTreeType::find()->all(), 'id', 'name'
            )
        ]
    );
    ?>

<?php endif; ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsStandart($model); ?>

<?php ActiveForm::end(); ?>





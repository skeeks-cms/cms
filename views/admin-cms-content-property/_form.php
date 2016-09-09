<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $handler \skeeks\cms\relatedProperties\PropertyType */

?>


<?php $form = ActiveForm::begin([
    'id'                                            => 'sx-dynamic-form',
    'enableAjaxValidation'                          => false,
]); ?>

<? $this->registerJs(<<<JS

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

<?= $form->fieldSet(\Yii::t('skeeks/cms','Basic settings')) ?>

    <?= $form->field($model, 'active')->radioList(\Yii::$app->cms->booleanFormat()) ?>
    <?= $form->field($model, 'is_required')->radioList(\Yii::$app->cms->booleanFormat()) ?>


    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'code')->textInput() ?>


    <?= $form->field($model, 'component')->listBox(array_merge(['' => ' — '], \Yii::$app->cms->relatedHandlersDataForSelect), [
            'size' => 1,
            'data-form-reload' => 'true'
        ])
        ->label(\Yii::t('skeeks/cms',"Property type"))
        ;
    ?>

    <? if ($handler) : ?>
        <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => \Yii::t('skeeks/cms', 'Settings')]); ?>
            <?= $handler->renderConfigForm($form); ?>
    <? endif; ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','Additionally')) ?>
    <?= $form->field($model, 'hint')->textInput() ?>
    <?= $form->fieldInputInt($model, 'priority') ?>

    <?= $form->field($model, 'searchable')->radioList(\Yii::$app->cms->booleanFormat()) ?>
    <?= $form->field($model, 'filtrable')->radioList(\Yii::$app->cms->booleanFormat()) ?>
    <?= $form->field($model, 'with_description')->radioList(\Yii::$app->cms->booleanFormat()) ?>

<? if ($content_id = \Yii::$app->request->get('content_id')) : ?>

    <?= $form->field($model, 'content_id')->hiddenInput(['value' => $content_id])->label(false); ?>

<? else: ?>

    <?= $form->field($model, 'content_id')->label(\Yii::t('skeeks/cms','Content'))->widget(
        \skeeks\cms\widgets\formInputs\EditedSelect::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \skeeks\cms\models\CmsContent::find()->all(),
                 "id",
                 "name"
             ),
            'controllerRoute' => 'cms/admin-cms-content',
        ]);
    ?>

<? endif; ?>

<?= $form->fieldSetEnd(); ?>


<? if (!$model->isNewRecord) : ?>
<?= $form->fieldSet(\Yii::t('skeeks/cms','Values for list')) ?>

    <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
        'label'             => \Yii::t('skeeks/cms',"Values for list"),
        'hint'              => \Yii::t('skeeks/cms',"You can snap to the element number of properties, and set the value to them"),
        'parentModel'       => $model,
        'relation'          => [
            'property_id' => 'id'
        ],

        'controllerRoute'   => 'cms/admin-cms-content-property-enum',
        'gridViewOptions'   => [
            'sortable' => true,
            'columns' => [
                [
                    'attribute'     => 'id',
                    'enableSorting' => false
                ],

                [
                    'attribute'     => 'code',
                    'enableSorting' => false
                ],

                [
                    'attribute'     => 'value',
                    'enableSorting' => false
                ],

                [
                    'attribute'     => 'priority',
                    'enableSorting' => false
                ],

                [
                    'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                    'attribute'     => 'def',
                    'enableSorting' => false
                ],
            ],
        ],
    ]); ?>

<?= $form->fieldSetEnd(); ?>
<? endif; ?>

<?= $form->buttonsCreateOrUpdate($model); ?>

<?php ActiveForm::end(); ?>





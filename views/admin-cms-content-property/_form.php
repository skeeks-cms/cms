<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model Tree */

?>

<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet(\Yii::t('app','Basic settings')) ?>

    <?= $form->field($model, 'active')->radioList(\Yii::$app->cms->booleanFormat()) ?>
    <?= $form->field($model, 'is_required')->radioList(\Yii::$app->cms->booleanFormat()) ?>


<? if ($content_id = \Yii::$app->request->get('content_id')) : ?>

    <?= $form->field($model, 'content_id')->hiddenInput(['value' => $content_id])->label(false); ?>

<? else: ?>

    <?= $form->field($model, 'content_id')->label(\Yii::t('app','Content'))->widget(
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

    <?= $form->fieldSelect($model, 'component', [
        \Yii::t('app','Base types')          => \Yii::$app->cms->basePropertyTypes(),
        \Yii::t('app','Custom types') => \Yii::$app->cms->userPropertyTypes(),
    ])
        ->label(\Yii::t('app',"Property type"))
        ;
    ?>
    <?= $form->field($model, 'component_settings')->label(false)->widget(
        \skeeks\cms\widgets\formInputs\componentSettings\ComponentSettingsWidget::className(),
        [
            'componentSelectId' => Html::getInputId($model, "component")
        ]
    ); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'code')->textInput() ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app','Additionally')) ?>
    <?= $form->field($model, 'hint')->textInput() ?>
    <?= $form->fieldInputInt($model, 'priority') ?>

    <?= $form->field($model, 'searchable')->radioList(\Yii::$app->cms->booleanFormat()) ?>
    <?= $form->field($model, 'filtrable')->radioList(\Yii::$app->cms->booleanFormat()) ?>
    <?= $form->field($model, 'with_description')->radioList(\Yii::$app->cms->booleanFormat()) ?>
<?= $form->fieldSetEnd(); ?>


<? if (!$model->isNewRecord) : ?>
<?= $form->fieldSet(\Yii::t('app','Values for list')) ?>

    <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
        'label'             => \Yii::t('app',"Values for list"),
        'hint'              => \Yii::t('app',"You can snap to the element number of properties, and set the value to them"),
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





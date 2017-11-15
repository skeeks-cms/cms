<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
/* @var $console \skeeks\cms\controllers\AdminUserController */
?>


<?php $form = ActiveForm::begin(); ?>
<?php ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'General information')) ?>
<?= $form->field($model, 'name')->textInput(); ?>
<?= $form->field($model, 'code')->textInput(); ?>
<?= $form->fieldInputInt($model, 'priority')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Content')) ?>
<?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
    'label' => \Yii::t('skeeks/cms', "Content"),
    'hint' => "",
    'parentModel' => $model,
    'relation' => [
        'content_type' => 'code'
    ],
    'controllerRoute' => '/cms/admin-cms-content',
    'gridViewOptions' => [
        'sortable' => true,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
            'name',
            'code',
            [
                'class' => \skeeks\cms\grid\BooleanColumn::className(),
                'falseValue' => \skeeks\cms\components\Cms::BOOL_N,
                'trueValue' => \skeeks\cms\components\Cms::BOOL_Y,
                'attribute' => 'active'
            ],
        ],
    ],
]); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
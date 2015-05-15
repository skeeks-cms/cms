<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
/* @var $console \skeeks\cms\controllers\AdminUserController */
?>


<?php $form = ActiveForm::begin(); ?>
<?php  ?>

<?= $form->fieldSet('Общая информация')?>
    <?= $form->field($model, 'name')->textInput(); ?>
    <?= $form->field($model, 'code')->textInput(); ?>
    <?= $form->fieldInputInt($model, 'priority')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('Контент')?>
    <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
        'label'             => "Контент",
        'hint'              => "",
        'parentModel'       => $model,
        'relation'          => [
            'content_type' => 'code'
        ],

        'sort'              => [
            'defaultOrder' =>
            [
                'priority' => SORT_DESC
            ]
        ],

        'controllerRoute'   => 'cms/admin-cms-content',
        'gridViewOptions'   => [
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
<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
/* @var $console \skeeks\cms\controllers\AdminUserController */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet(\Yii::t('app','Main')); ?>


    <?= $form->field($model, 'name')->textInput(); ?>
    <?= $form->field($model, 'code')->textInput(); ?>
    <?= $form->fieldRadioListBoolean($model, 'active'); ?>
    <?= $form->fieldInputInt($model, 'priority'); ?>

    <?= $form->fieldRadioListBoolean($model, 'index_for_search'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app','Captions')); ?>
    <?= $form->field($model, 'name_one')->textInput(); ?>
    <?= $form->field($model, 'name_meny')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<? if (!$model->isNewRecord) : ?>
    <?= $form->fieldSet(\Yii::t('app','Element properties')) ?>
        <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
            'label'             => \Yii::t('app','Element properties'),
            'hint'              => \Yii::t('app','Every content on the site has its own set of properties, its sets here'),
            'parentModel'       => $model,
            'relation'          => [
                'tree_type_id' => 'id'
            ],

            'sort'              => [
                'defaultOrder' =>
                [
                    'priority' => SORT_ASC
                ]
            ],

            'dataProviderCallback' => function($dataProvider)
            {
                /**
                 * @var \yii\data\BaseDataProvider $dataProvider
                */
                $dataProvider->getPagination()->defaultPageSize   = 5000;
            },

            'controllerRoute'   => 'cms/admin-cms-tree-type-property',
            'gridViewOptions'   => [
                'sortable' => true,
                'columns' => [
                    [
                        'attribute'     => 'name',
                        'enableSorting' => false
                    ],

                    [
                        'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                        'attribute'     => 'active',
                        'falseValue'    => \skeeks\cms\components\Cms::BOOL_N,
                        'trueValue'     => \skeeks\cms\components\Cms::BOOL_Y,
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'code',
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'priority',
                        'enableSorting' => false
                    ],
                ],
            ],
        ]); ?>
    <?= $form->fieldSetEnd(); ?>
<? endif; ?>
<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>

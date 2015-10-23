<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet(\Yii::t('app','Main')); ?>

    <? if ($content_type = \Yii::$app->request->get('content_type')) : ?>
        <?= $form->field($model, 'content_type')->hiddenInput(['value' => $content_type])->label(false); ?>
    <? else: ?>
        <div style="display: none;">
            <?= $form->fieldSelect($model, 'content_type', \yii\helpers\ArrayHelper::map(\skeeks\cms\models\CmsContentType::find()->all(), 'code', 'name')); ?>
        </div>
    <? endif; ?>

    <?= $form->field($model, 'name')->textInput(); ?>
    <?= $form->field($model, 'code')->textInput()
        ->hint(\Yii::t('app', 'The name of the template to draw the elements of this type will be the same as the name of the code.')); ?>

    <?= $form->field($model, 'viewFile')->textInput()
        ->hint(\Yii::t('app', 'The path to the template. If not specified, the pattern will be the same code.')); ?>


    <?= $form->fieldRadioListBoolean($model, 'active'); ?>
    <?= $form->fieldInputInt($model, 'priority'); ?>



    <?= $form->fieldRadioListBoolean($model, 'index_for_search'); ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('app', 'Link to section')
    ]); ?>

    <?= $form->fieldSelect($model, 'default_tree_id', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions(), [
        'allowDeselect' => true
    ]); ?>
    <?= $form->fieldRadioListBoolean($model, 'is_allow_change_tree'); ?>


    <?= $form->fieldSelect($model, 'root_tree_id', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions(), [
        'allowDeselect' => true
    ])->hint(\Yii::t('app', 'If it is set to the root partition, the elements can be tied to him and his sub.')); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app','Captions')); ?>
    <?= $form->field($model, 'name_one')->textInput(); ?>
    <?= $form->field($model, 'name_meny')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<? if (!$model->isNewRecord) : ?>
    <?= $form->fieldSet(\Yii::t('app','Properties')) ?>
        <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
            'label'             => \Yii::t('app',"Element properties"),
            'hint'              => \Yii::t('app',"Every content on the site has its own set of properties, its sets here"),
            'parentModel'       => $model,
            'relation'          => [
                'content_id' => 'id'
            ],

            'sort'              => [
                'defaultOrder' =>
                [
                    'priority' => SORT_ASC
                ]
            ],

            'controllerRoute'   => 'cms/admin-cms-content-property',

            'dataProviderCallback' => function($dataProvider)
            {
                /**
                 * @var \yii\data\BaseDataProvider $dataProvider
                */
                $dataProvider->getPagination()->defaultPageSize   = 5000;
            },

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

<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
/* @var $console \skeeks\cms\controllers\AdminUserController */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>


<?= $form->field($model, 'name')->textInput(); ?>
<?= $form->field($model, 'code')
    ->hint(\Yii::t('skeeks/cms',
        'The name of the template to draw the elements of this type will be the same as the name of the code.')); ?>

<?= $form->field($model, 'view_file')->textInput()
    ->hint(\Yii::t('skeeks/cms', 'The path to the template. If not specified, the pattern will be the same code.')); ?>

<?= $form->fieldRadioListBoolean($model, 'active'); ?>
<?= $form->fieldInputInt($model, 'priority'); ?>

<?= $form->fieldRadioListBoolean($model, 'index_for_search'); ?>

<?= $form->fieldSelect($model, 'default_children_tree_type',
    \yii\helpers\ArrayHelper::map(\skeeks\cms\models\CmsTreeType::find()->all(), 'id', 'name'), [
        'allowDeselect' => true
    ])->hint(\Yii::t('skeeks/cms',
    'If this parameter is not specified, the child partition is created of the same type as the current one.')); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Captions')); ?>
<?= $form->field($model, 'name_one')->textInput(); ?>
<?= $form->field($model, 'name_meny')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?php if (!$model->isNewRecord) : ?>
    <?= $form->fieldSet(\Yii::t('skeeks/cms', 'Properties')) ?>

    <?php $pjax = \yii\widgets\Pjax::begin(); ?>
    <?
    $query = \skeeks\cms\models\CmsTreeTypeProperty::find()->orderBy(['priority' => SORT_ASC]);
    $query->joinWith('cmsTreeTypeProperty2types map');
    $query->andWhere(['map.cms_tree_type_id' => $model->id]);
    ?>
    <?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
        'dataProvider' => new \yii\data\ActiveDataProvider([
            'query' => $query
        ]),
        'settingsData' =>
            [
                'namespace' => \Yii::$app->controller->uniqueId . "__" . $model->id
            ],
        //'filterModel'       => $searchModel,
        'autoColumns' => false,
        'pjax' => $pjax,
        'columns' =>
            [
                [
                    'class' => \skeeks\cms\modules\admin\grid\ActionColumn::class,
                    'controller' => \Yii::$app->createController('cms/admin-cms-tree-type-property')[0],
                    'isOpenNewWindow' => true
                ],

                'name',
                'code',
                'priority',
                [
                    'label' => \Yii::t('skeeks/cms', 'Sections'),
                    'value' => function(\skeeks\cms\models\CmsTreeTypeProperty $cmsContentProperty) {
                        $contents = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsTreeTypes, 'id', 'name');
                        return implode(', ', $contents);
                    }
                ],
                [
                    'class' => \skeeks\cms\grid\BooleanColumn::className(),
                    'attribute' => "active"
                ],
            ]
    ]); ?>

    <?php \yii\widgets\Pjax::end(); ?>



    <?= $form->fieldSetEnd(); ?>
<?php endif; ?>
<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>

<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */

 if ($model->isNewRecord)
 {
     if ($tree_id = \Yii::$app->request->get("tree_id"))
     {
         $model->tree_id = $tree_id;
     }

     if ($parent_content_element_id = \Yii::$app->request->get("parent_content_element_id"))
     {
         $model->parent_content_element_id = $parent_content_element_id;
     }
 }
?>

<?php $form = ActiveForm::begin(); ?>

<? if ($model->isNewRecord) : ?>
    <? if ($content_id = \Yii::$app->request->get("content_id")) : ?>
        <? $contentModel = \skeeks\cms\models\CmsContent::findOne($content_id); ?>
        <? $model->content_id = $content_id; ?>
        <?= $form->field($model, 'content_id')->hiddenInput(['value' => $content_id])->label(false); ?>
    <? endif; ?>
<? else : ?>
    <? $contentModel = $model->cmsContent; ?>
<? endif; ?>

<? if ($contentModel && $contentModel->parentContent) : ?>
    <?= Html::activeHiddenInput($contentModel, 'parent_content_is_required'); ?>
<? endif; ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','Main')); ?>


    <?= $form->fieldRadioListBoolean($model, 'active'); ?>
    <div class="row">
        <div class="col-md-3">
            <?= $form->field($model, 'published_at')->widget(\kartik\datecontrol\DateControl::classname(), [
                //'displayFormat' => 'php:d-M-Y H:i:s',
                'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
            ]); ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'published_to')->widget(\kartik\datecontrol\DateControl::classname(), [
                //'displayFormat' => 'php:d-M-Y H:i:s',
                'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
            ]); ?>
        </div>
    </div>
    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => 255])->hint(\Yii::t('skeeks/cms',"This parameter affects the address of the page")); ?>
    <?= $form->fieldInputInt($model, 'priority'); ?>

    <? if ($contentModel->parent_content_id) : ?>

        <?= $form->field($model, 'parent_content_element_id')->widget(
            \skeeks\cms\modules\admin\widgets\formInputs\CmsContentElementInput::className()
        )->label($contentModel->parentContent->name_one) ?>
    <? endif; ?>

    <? if ($model->relatedPropertiesModel->properties) : ?>
        <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
            'content' => \Yii::t('skeeks/cms', 'Additional properties')
        ]); ?>
        <? foreach ($model->relatedPropertiesModel->properties as $property) : ?>
            <?= $property->renderActiveForm($form)?>
        <? endforeach; ?>

    <? else : ?>
        <?/*= \Yii::t('skeeks/cms','Additional properties are not set')*/?>
    <? endif; ?>
<?= $form->fieldSetEnd()?>





<?= $form->fieldSet(\Yii::t('skeeks/cms','Announcement')); ?>
    <?= $form->field($model, 'image_id')->widget(
        \skeeks\cms\widgets\formInputs\StorageImage::className()
    ); ?>

    <?= $form->field($model, 'description_short')->widget(
        \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
        [
            'modelAttributeSaveType' => 'description_short_type',
        ]);
    ?>

<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','In detal')); ?>

    <?= $form->field($model, 'image_full_id')->widget(
        \skeeks\cms\widgets\formInputs\StorageImage::className()
    ); ?>

    <?= $form->field($model, 'description_full')->widget(
        \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
        [
            'modelAttributeSaveType' => 'description_full_type',
        ]);
    ?>

<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','Sections')); ?>
    <? if ($contentModel->root_tree_id) : ?>
        <? $rootTreeModels = \skeeks\cms\models\CmsTree::findAll($contentModel->root_tree_id); ?>
    <? else : ?>
        <? $rootTreeModels = \skeeks\cms\models\CmsTree::findRoots()->joinWith('cmsSiteRelation')->orderBy([\skeeks\cms\models\CmsSite::tableName() . ".priority" => SORT_ASC])->all(); ?>
    <? endif; ?>

    <? if ($contentModel->is_allow_change_tree == \skeeks\cms\components\Cms::BOOL_Y) : ?>
        <? if ($rootTreeModels) : ?>
            <div class="row">
                <div class="col-lg-8 col-md-12 col-sm-12">
                    <?= $form->field($model, 'tree_id')->widget(
                        \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class,
                        [
                            'multiple' => false,
                            'treeWidgetOptions' =>
                            [
                                'models' => $rootTreeModels
                            ]
                        ]
                    ); ?>
                </div>
            </div>
        <? endif; ?>
    <? endif; ?>

    <? if ($rootTreeModels) : ?>
        <div class="row">
            <div class="col-lg-8 col-md-12 col-sm-12">
                <?= $form->field($model, 'treeIds')->widget(
                    \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class,
                    [
                        'multiple' => true,
                        'treeWidgetOptions' =>
                        [
                            'models' => $rootTreeModels
                        ]
                    ]
                ); ?>
            </div>
        </div>
    <? endif; ?>

<?= $form->fieldSetEnd()?>



<?= $form->fieldSet(\Yii::t('skeeks/cms','SEO')); ?>
    <?= $form->field($model, 'meta_title')->textarea(); ?>
    <?= $form->field($model, 'meta_description')->textarea(); ?>
    <?= $form->field($model, 'meta_keywords')->textarea(); ?>
<?= $form->fieldSetEnd() ?>


<?= $form->fieldSet(\Yii::t('skeeks/cms','Images/Files')); ?>

    <?= $form->field($model, 'images')->widget(
        \skeeks\cms\widgets\formInputs\ModelStorageFiles::className()
    ); ?>

    <?= $form->field($model, 'files')->widget(
        \skeeks\cms\widgets\formInputs\ModelStorageFiles::className()
    ); ?>

<?= $form->fieldSetEnd()?>


<? if (!$model->isNewRecord) : ?>
    <?/*= $form->fieldSet(\Yii::t('skeeks/cms','Additionally')); */?><!--
        <?/*= $form->fieldSelect($model, 'content_id', \skeeks\cms\models\CmsContent::getDataForSelect()); */?>
        <?/*= $form->fieldInputInt($model, 'priority'); */?>

    --><?/*= $form->fieldSetEnd() */?>

    <? if ($model->cmsContent->access_check_element == "Y") : ?>
        <?= $form->fieldSet(\Yii::t('skeeks/cms','Access')); ?>
            <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
                'permissionName'                => $model->permissionName,
                'permissionDescription'         => 'Доступ к этому элементу: ' . $model->name,
                'label'                         => 'Доступ к этому элементу',
            ]); ?>
        <?= $form->fieldSetEnd() ?>
    <? endif; ?>
<? endif; ?>

<? if ($model->cmsContent->childrenContents) : ?>

    <?

    /**
     * @var $content \skeeks\cms\models\CmsContent
     */
    ?>
    <? foreach($model->cmsContent->childrenContents as $childContent) : ?>
        <?= $form->fieldSet($childContent->name); ?>

            <? if ($model->isNewRecord) : ?>

                <?= \yii\bootstrap\Alert::widget([
                    'options' =>
                    [
                        'class' => 'alert-warning'
                    ],
                    'body' => \Yii::t('skeeks/cms', 'Management will be available after saving')
                ]); ?>
            <? else:  ?>
                <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
                    'label'             => $childContent->name,
                    'namespace'         => md5($model->className() . $childContent->id),
                    'parentModel'       => $model,
                    'relation'          => [
                        'content_id'                    => $childContent->id,
                        'parent_content_element_id'     => $model->id
                    ],

                    'sort'              => [
                        'defaultOrder' =>
                        [
                            'priority' => 'published_at'
                        ]
                    ],

                    'controllerRoute'   => 'cms/admin-cms-content-element',
                    'gridViewOptions'   => [
                        'columns' => (array) \skeeks\cms\controllers\AdminCmsContentElementController::getColumns($childContent)
                    ],
                ]); ?>

            <? endif;  ?>




        <?= $form->fieldSetEnd() ?>
    <? endforeach; ?>
<? endif; ?>



<?= $form->buttonsStandart($model); ?>
<?php ActiveForm::end(); ?>

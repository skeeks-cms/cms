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

    <?= $this->render('_form-main', [
        'form'              => $form,
        'contentModel'      => $contentModel,
        'model'             => $model,
    ]); ?>

    <?= $this->render('_form-announce', [
        'form'              => $form,
        'contentModel'      => $contentModel,
        'model'             => $model,
    ]); ?>

    <?= $this->render('_form-detail', [
        'form'              => $form,
        'contentModel'      => $contentModel,
        'model'             => $model,
    ]); ?>

    <?= $this->render('_form-sections', [
        'form'              => $form,
        'contentModel'      => $contentModel,
        'model'             => $model,
    ]); ?>

    <?= $this->render('_form-seo', [
        'form'              => $form,
        'contentModel'      => $contentModel,
        'model'             => $model,
    ]); ?>

    <?= $this->render('_form-images', [
        'form'              => $form,
        'contentModel'      => $contentModel,
        'model'             => $model,
    ]); ?>




<? if (!$model->isNewRecord) : ?>
    <? if ($model->cmsContent->access_check_element == "Y") : ?>
        <?= $form->fieldSet(\Yii::t('skeeks/cms','Access')); ?>
            <?= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
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

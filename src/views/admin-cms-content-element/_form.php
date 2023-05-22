<?php

//use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use yii\helpers\Html;

/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */

/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsLang */
$controller = $this->context;
$action = $controller->action;
?>



<?php $pjax = \skeeks\cms\widgets\Pjax::begin(); ?>
<?php $form = $action->beginActiveForm(); ?>

<?php
if ($model->isNewRecord) {
    if ($tree_id = \Yii::$app->request->get("tree_id")) {
        $model->tree_id = $tree_id;
    }

    if ($parent_content_element_id = \Yii::$app->request->get("parent_content_element_id")) {
        $model->parent_content_element_id = $parent_content_element_id;
    }
}
?>

<?php echo $form->errorSummary([$model, $relatedModel]); ?>
<div style="display: none;">

    <?php if ($model->isNewRecord) : ?>
        <?php if ($content_id = \Yii::$app->request->get("content_id")) : ?>
            <?php $contentModel = \skeeks\cms\models\CmsContent::findOne($content_id); ?>
            <?php $model->content_id = $content_id; ?>
            <?= $form->field($model, 'content_id')->hiddenInput(['value' => $content_id])->label(false); ?>
        <?php endif; ?>
    <?php else
        : ?>
        <?php $contentModel = $model->cmsContent;
        ?>
    <?php endif; ?>

    <?php if ($contentModel && $contentModel->parentContent) : ?>
        <?= Html::activeHiddenInput($contentModel, 'is_parent_content_required'); ?>
    <?php endif; ?>
</div>

<?= $this->render('_form-main', [
    'form'         => $form,
    'contentModel' => $contentModel,
    'model'        => $model,
]); ?>

<?= $this->render('_form-images', [
    'form'         => $form,
    'contentModel' => $contentModel,
    'model'        => $model,
]); ?>

<?= $this->render('_form-announce', [
    'form'         => $form,
    'contentModel' => $contentModel,
    'model'        => $model,
]); ?>
<?= $this->render('_form-detail', [
    'form'         => $form,
    'contentModel' => $contentModel,
    'model'        => $model,
]); ?>

<?= $this->render('_form-sections', [
    'form'         => $form,
    'contentModel' => $contentModel,
    'model'        => $model,
]); ?>

<? if ($contentModel->is_have_page) : ?>
    <?= $this->render('_form-seo', [
        'form'         => $form,
        'contentModel' => $contentModel,
        'model'        => $model,
    ]); ?>
<? endif; ?>

<?= $this->render('_form-additionaly', [
    'form'         => $form,
    'contentModel' => $contentModel,
    'model'        => $model,
]); ?>




<?php if (!$model->isNewRecord) : ?>
    <?php if ($model->cmsContent->is_access_check_element) : ?>
        <? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Access')); ?>
        <?= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
            'permissionName'        => $model->permissionName,
            'permissionDescription' => 'Доступ к этому элементу: '.$model->name,
            'label'                 => 'Доступ к этому элементу',
        ]); ?>
        <? $fieldSet::end(); ?>

    <?php endif; ?>
<?php endif; ?>

<?php if ($model->cmsContent->childrenContents) : ?>

    <?
    /**
     * @var $content \skeeks\cms\models\CmsContent
     */
    ?>
    <?php foreach ($model->cmsContent->childrenContents as $childContent) : ?>
        <? $fieldSet = $form->fieldSet($childContent->name); ?>

        <?php if ($model->isNewRecord) : ?>

            <?= \yii\bootstrap\Alert::widget([
                'options' =>
                    [
                        'class' => 'alert-warning',
                    ],
                'body'    => \Yii::t('skeeks/cms', 'Management will be available after saving'),
            ]); ?>
        <?php else
            : ?>
            <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
                'label'       => $childContent->name,
                'namespace'   => md5($model->className().$childContent->id),
                'parentModel' => $model,
                'relation'    => [
                    'content_id'                => $childContent->id,
                    'parent_content_element_id' => $model->id,
                ],

                'sort' => [
                    'defaultOrder' =>
                        [
                            'priority' => 'published_at',
                        ],
                ],

                'controllerRoute' => '/cms/admin-cms-content-element',
                'gridViewOptions' => [
                    'columns' => (array)\skeeks\cms\controllers\AdminCmsContentElementController::getColumns($childContent),
                ],
            ]);
            ?>

        <?php endif; ?>




        <? $fieldSet::end(); ?>

    <?php endforeach; ?>
<?php endif; ?>


<?= $form->buttonsStandart($model, $action->buttons); ?>

<?php echo $form->errorSummary([$model, $relatedModel]); ?>
<?php $form::end(); ?>
<?php $pjax::end(); ?>

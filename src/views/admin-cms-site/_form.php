<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 21.03.2017
 */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsLang */
$controller = $this->context;
$action = $controller->action;
?>


<?php $form = $action->beginActiveForm(); ?>
<?php echo $form->errorSummary($model); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', "Main")); ?>

<?= $form->field($model, 'image_id')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept' => 'image/*',
        'multiple' => false
    ]
); ?>

<?= $form->field($model, 'name'); ?>
<?= $form->field($model, 'code')->textInput(); ?>


<?php if ($model->def === \skeeks\cms\components\Cms::BOOL_Y): ?>
    <?= $form->field($model, 'active')->hiddenInput()->hint(\Yii::t('skeeks/cms',
        'Site selected by default always active')); ?>
    <?= $form->field($model, 'def')->hiddenInput()->hint(\Yii::t('skeeks/cms',
        'This site is the site selected by default. If you want to change it, you need to choose a different site, the default site.')); ?>
<?php else
    : ?>
    <?= $form->fieldRadioListBoolean($model, 'active');
    ?>
    <?= $form->fieldRadioListBoolean($model, 'def'); ?>
<?php endif; ?>




<?= $form->field($model, 'description')->textarea(); ?>
<?= $form->field($model, 'server_name')->textInput(['maxlength' => 255]) ?>
<?= $form->fieldInputInt($model, 'priority'); ?>

<?= $form->fieldSetEnd(); ?>

<?php if (!$model->isNewRecord) : ?>
    <?= $form->fieldSet(\Yii::t('skeeks/cms', "Domains")); ?>

    <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
        'label' => "",
        'hint' => "",
        'parentModel' => $model,
        'relation' => [
            'cms_site_id' => 'id'
        ],

        'controllerRoute' => '/cms/admin-cms-site-domain',
        'gridViewOptions' => [
            'columns' => [
                //['class' => 'yii\grid\SerialColumn'],
                'domain',
            ],
        ],
    ]); ?>

    <?= $form->fieldSetEnd(); ?>
<?php endif; ?>
<?= $form->buttonsStandart($model) ?>

<?php echo $form->errorSummary($model); ?>
<?php $action->endActiveForm(); ?>
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
$action     = $controller->action;
?>
<?php $form = $action->beginActiveForm(); ?>

    <?= $form->field($model, 'image_id')->widget(
        \skeeks\cms\widgets\AjaxFileUploadWidget::class,
        [
            'accept' => 'image/*',
            'multiple' => false
        ]
    ); ?>
    <?= $form->field($model, 'code')->textInput(); ?>
    <?= $form->fieldRadioListBoolean($model, 'active')->hint(\Yii::t('skeeks/cms','On the site must be included at least one language')); ?>
    <?= $form->field($model, 'name')->textarea(); ?>
    <?= $form->field($model, 'description')->textarea(); ?>
    <?= $form->fieldInputInt($model, 'priority'); ?>

    <?= $form->buttonsStandart($model) ?>

<?php $action->endActiveForm(); ?>
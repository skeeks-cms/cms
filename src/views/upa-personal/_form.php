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
    <?= $form->errorSummary($model); ?>
        <?= $form->field($model, 'image_id')->widget(
            \skeeks\cms\widgets\AjaxFileUploadWidget::class,
            [
                'accept' => 'image/*',
                'multiple' => false
            ]
        ); ?>
        <?= $form->field($model, 'username'); ?>
        <?= $form->field($model, 'first_name')->textInput(); ?>
        <?= $form->field($model, 'last_name')->textInput(); ?>
        <?= $form->field($model, 'patronymic')->textInput(); ?>
        <?= $form->field($model, 'email')->textInput(); ?>
        <?
        \skeeks\cms\admin\assets\JqueryMaskInputAsset::register($this);
        $id = \yii\helpers\Html::getInputId($model, 'phone');
        $this->registerJs(<<<JS
$("#{$id}").mask("+7 999 999-99-99");
JS
        );
        ?>

        <?= $form->field($model, 'phone')->textInput([
            'placeholder' => '+7 903 722-28-73'
        ]); ?>

    <?= $form->buttonsCreateOrUpdate($model); ?>
    <?= $form->errorSummary($model); ?>
<?php $action->endActiveForm(); ?>

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
        <?= $form->field($model, 'username')->textInput(['maxlength' => 12])
                ->hint('Уникальное имя пользователя. Используется для авторизации, для формирования ссылки на личный кабинет.'); ?>
        <?= $form->field($model, 'first_name')->textInput(); ?>
        <?= $form->field($model, 'last_name')->textInput(); ?>
        <?= $form->field($model, 'patronymic')->textInput(); ?>
        <?= $form->field($model, 'email')->textInput(); ?>

    <?= $form->buttonsCreateOrUpdate($model); ?>
    <?= $form->errorSummary($model); ?>
<?php $action->endActiveForm(); ?>

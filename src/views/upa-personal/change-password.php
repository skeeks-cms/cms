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
        <?= $form->field($model, 'new_password')->passwordInput() ?>
        <?= $form->field($model, 'new_password_confirm')->passwordInput() ?>

    <?= $form->buttonsCreateOrUpdate($model); ?>
    <?= $form->errorSummary($model); ?>
<?php $action->endActiveForm(); ?>

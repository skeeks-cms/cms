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

    <?= $form->fieldSet(\Yii::t('skeeks/cms',"Main")); ?>

        <?= $form->field($model, 'image_id')->widget(
            \skeeks\cms\widgets\AjaxFileUploadWidget::class,
            [
                'accept' => 'image/*',
                'multiple' => false
            ]
        ); ?>

        <?= $form->field($model, 'code')->textInput(); ?>


        <? if ($model->def === \skeeks\cms\components\Cms::BOOL_Y): ?>
            <?= $form->field($model, 'active')->hiddenInput()->hint(\Yii::t('skeeks/cms','Site selected by default always active')); ?>
            <?= $form->field($model, 'def')->hiddenInput()->hint(\Yii::t('skeeks/cms','This site is the site selected by default. If you want to change it, you need to choose a different site, the default site.')); ?>
        <? else : ?>
            <?= $form->fieldRadioListBoolean($model, 'active'); ?>
            <?= $form->fieldRadioListBoolean($model, 'def'); ?>
        <? endif; ?>


        <?= $form->field($model, 'name')->textarea(); ?>


        <?= $form->field($model, 'description')->textarea(); ?>
        <?= $form->field($model, 'server_name')->textInput(['maxlength' => 255]) ?>
        <?= $form->fieldInputInt($model, 'priority'); ?>

    <?= $form->fieldSetEnd(); ?>

    <? if (!$model->isNewRecord) : ?>
        <?= $form->fieldSet(\Yii::t('skeeks/cms',"Domains")); ?>

            <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
                'label'             => "",
                'hint'              => "",
                'parentModel'       => $model,
                'relation'          => [
                    'site_code' => 'code'
                ],

                'controllerRoute'   => 'cms/admin-cms-site-domain',
                'gridViewOptions'   => [
                    'columns' => [
                        //['class' => 'yii\grid\SerialColumn'],
                        'domain',
                    ],
                ],
            ]); ?>

        <?= $form->fieldSetEnd(); ?>
    <? endif; ?>
    <?= $form->buttonsStandart($model) ?>

<?php $action->endActiveForm(); ?>
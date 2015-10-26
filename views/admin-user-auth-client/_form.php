<?php
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

/* @var $this yii\web\View */
/* @var $model \yii\db\ActiveRecord */
?>
<?php $form = ActiveForm::begin(); ?>

    <?= $form->fieldSet(\Yii::t('app',"Main")); ?>
    <? if (\Yii::$app->request->get('user_id')) : ?>
        <?= $form->field($model, 'user_id')->hiddenInput(['value' => \Yii::$app->request->get('user_id')])->label(false) ?>
    <? else: ?>
        <?= $form->fieldSelect($model, 'user_id', \yii\helpers\ArrayHelper::map(
            \skeeks\cms\models\User::find()->active()->all(),
            'id',
            'displayName'
        ), [
            'allowDeselect' => true
        ]) ?>
    <? endif; ?>

    <?= $form->fieldSetEnd(); ?>

    <? if (!$model->isNewRecord) : ?>
        <?= $form->fieldSet(\Yii::t('app',"Data of provider")); ?>
            <?/*= \yii\widgets\DetailView::widget([
                'model'         => $model->provider_data,
                'attributes'    => array_keys((array) $model->provider_data),
            ])*/?>
            <pre>
            <?
                print_r($model->provider_data);
            ?>
            </pre>
        <?= $form->fieldSetEnd(); ?>
    <? endif; ?>


    <?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>

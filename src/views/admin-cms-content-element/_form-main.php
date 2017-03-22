<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
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

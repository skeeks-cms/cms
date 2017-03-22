<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.05.2015
 */
/* @var $this yii\web\View */
?>
<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Template')); ?>
    <?= $form->field($model, 'viewFile')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Filtration')); ?>
    <?= $form->field($model, 'enabledCurrentSite')->listBox(\yii\helpers\ArrayHelper::merge([null => "-"], \Yii::$app->cms->booleanFormat()), ['size' => 1]); ?>
    <?= $form->field($model, 'active')->listBox(\yii\helpers\ArrayHelper::merge([null => "-"], \Yii::$app->cms->booleanFormat()), ['size' => 1]); ?>

    <?= $form->fieldSelectMulti($model, 'tree_type_ids', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\CmsTreeType::find()->all(), 'id', 'name'
    )); ?>

    <?= $form->fieldInputInt($model, 'level'); ?>
    <?= $form->fieldSelectMulti($model, 'site_codes', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\CmsSite::find()->active()->all(),
        'code',
        'name'
    )); ?>
    <?= $form->field($model, 'treePid')->widget(
        \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class
    ); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Sorting')); ?>
    <?= $form->fieldSelect($model, 'orderBy', (new \skeeks\cms\models\Tree())->attributeLabels()); ?>
    <?= $form->fieldSelect($model, 'order', [
        SORT_ASC    => \Yii::t('skeeks/cms', 'ASC (from lowest to highest)'),
        SORT_DESC   => \Yii::t('skeeks/cms', 'DESC (from highest to lowest)'),
    ]); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Additionally')); ?>
    <?= $form->field($model, 'label')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Cache settings')); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledRunCache', \Yii::$app->cms->booleanFormat()); ?>
    <?= $form->fieldInputInt($model, 'runCacheDuration'); ?>
<?= $form->fieldSetEnd(); ?>



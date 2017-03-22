<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.05.2015
 */
/* @var $this yii\web\View */
?>
<?= $form->fieldSet(\Yii::t('skeeks/cms','Showing')); ?>
    <?= $form->field($model, 'viewFile')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','Pagination')); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledPaging', \Yii::$app->cms->booleanFormat()); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledPjaxPagination', \Yii::$app->cms->booleanFormat()); ?>
    <?= $form->fieldInputInt($model, 'pageSize'); ?>
    <?= $form->fieldInputInt($model, 'pageSizeLimitMin'); ?>
    <?= $form->fieldInputInt($model, 'pageSizeLimitMax'); ?>
    <?= $form->field($model, 'pageParamName')->textInput(); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','Filtering')); ?>
    <?= $form->fieldSelect($model, 'active', \Yii::$app->cms->booleanFormat(), [
        'allowDeselect' => true
    ]); ?>

    <?= $form->fieldSelect($model, 'enabledActiveTime', \Yii::$app->cms->booleanFormat())->hint(\Yii::t('skeeks/cms',"Will be considered time of beginning and end of the publication")); ?>

    <?= $form->fieldSelectMulti($model, 'createdBy', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\User::find()->active()->all(),
        'id',
        'name'
    )); ?>

    <?= $form->fieldSelectMulti($model, 'content_ids', \skeeks\cms\models\CmsContent::getDataForSelect()); ?>

    <?= $form->fieldRadioListBoolean($model, 'enabledCurrentTree', \Yii::$app->cms->booleanFormat()); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledCurrentTreeChild', \Yii::$app->cms->booleanFormat()); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledCurrentTreeChildAll', \Yii::$app->cms->booleanFormat()); ?>

    <?= $form->field($model, 'tree_ids')->widget(
        \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
        [
            'mode' => \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_MULTI,
            'attributeMulti' => 'tree_ids'
        ]
    ); ?>

    <?= $form->fieldRadioListBoolean($model, 'enabledSearchParams', \Yii::$app->cms->booleanFormat()); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','Sorting and quantity')); ?>
    <?= $form->fieldInputInt($model, 'limit'); ?>
    <?= $form->fieldSelect($model, 'orderBy', (new \skeeks\cms\models\CmsContentElement())->attributeLabels()); ?>
    <?= $form->fieldSelect($model, 'order', [
        SORT_ASC    => "ASC (".\Yii::t('skeeks/cms','from smaller to larger').")",
        SORT_DESC   => "DESC (".\Yii::t('skeeks/cms','from highest to lowest').")",
    ]); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Additionally')); ?>
    <?= $form->field($model, 'label')->textInput(); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Cache settings')); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledRunCache', \Yii::$app->cms->booleanFormat()); ?>
    <?= $form->fieldInputInt($model, 'runCacheDuration'); ?>
<?= $form->fieldSetEnd(); ?>




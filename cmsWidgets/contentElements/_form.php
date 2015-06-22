<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.05.2015
 */
/* @var $this yii\web\View */
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
?>
<?php $form = ActiveForm::begin(); ?>
    <?= $form->fieldSet('Отображение'); ?>
        <?= $form->field($model, 'viewFile')->textInput(); ?>
    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet('Постраничная навигация'); ?>
        <?= $form->fieldRadioListBoolean($model, 'enabledPaging', \Yii::$app->cms->booleanFormat()); ?>
        <?= $form->fieldRadioListBoolean($model, 'enabledPjaxPagination', \Yii::$app->cms->booleanFormat()); ?>
        <?= $form->fieldInputInt($model, 'pageSize'); ?>
        <?= $form->field($model, 'pageParamName')->textInput(); ?>

    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet('Фильтрация'); ?>
        <?= $form->fieldSelect($model, 'active', \Yii::$app->cms->booleanFormat(), [
            'allowDeselect' => true
        ]); ?>

        <?= $form->fieldSelect($model, 'enabledActiveTime', \Yii::$app->cms->booleanFormat())->hint("Будет учитываться время начала и окончанию публикации");; ?>

        <?= $form->fieldSelectMulti($model, 'createdBy', \yii\helpers\ArrayHelper::map(
            \skeeks\cms\models\User::find()->active()->all(),
            'id',
            'name'
        )); ?>

        <?= $form->fieldSelectMulti($model, 'content_ids', \yii\helpers\ArrayHelper::map(
            \skeeks\cms\models\CmsContent::find()->active()->all(),
            'id',
            'name'
        )); ?>

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

    <?= $form->fieldSet('Сортировка и количество'); ?>
        <?= $form->fieldInputInt($model, 'limit'); ?>
        <?= $form->fieldSelect($model, 'orderBy', (new \skeeks\cms\models\CmsContentElement())->attributeLabels()); ?>
        <?= $form->fieldSelect($model, 'order', [
            SORT_ASC    => "ASC (от меньшего к большему)",
            SORT_DESC   => "DESC (от большего к меньшему)",
        ]); ?>
    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet('Дополнительно'); ?>
        <?= $form->field($model, 'label')->textInput(); ?>

    <?= $form->fieldSetEnd(); ?>



<?= $form->buttonsStandart($model) ?>
<?php ActiveForm::end(); ?>
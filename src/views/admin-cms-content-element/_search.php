<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.05.2016
 */
?>
<? $form = \skeeks\cms\modules\admin\widgets\filters\AdminFiltersForm::begin([
    'action' => '/' . \Yii::$app->request->pathInfo,
    'namespace' => \Yii::$app->controller->uniqueId . "_" . $content_id
]); ?>

    <?= \yii\helpers\Html::hiddenInput('content_id', $content_id) ?>

    <?= $form->field($searchModel, 'id'); ?>

    <?= $form->field($searchModel, 'q')->textInput([
        'placeholder' => \Yii::t('skeeks/cms', 'Search name and description')
    ])->setVisible(); ?>

    <?= $form->field($searchModel, 'name')->textInput([
        'placeholder' => \Yii::t('skeeks/cms', 'Search by name')
    ]) ?>

    <?= $form->field($searchModel, 'active')->listBox(\yii\helpers\ArrayHelper::merge([
        '' => ' - '
    ], \Yii::$app->cms->booleanFormat()), [
        'size' => 1
    ]); ?>

    <?= $form->field($searchModel, 'section')->listBox(\yii\helpers\ArrayHelper::merge([
        '' => ' - '
    ], \skeeks\cms\helpers\TreeOptions::getAllMultiOptions()),
    [
        'unselect' => ' - ',
        'size' => 1
    ]); ?>


    <?= $form->field($searchModel, 'has_image')->checkbox(\Yii::$app->formatter->booleanFormat, false); ?>
    <?= $form->field($searchModel, 'has_full_image')->checkbox(\Yii::$app->formatter->booleanFormat, false); ?>


    <?= $form->field($searchModel, 'created_by')->widget(\skeeks\cms\modules\admin\widgets\formInputs\SelectModelDialogUserInput::className()); ?>
    <?= $form->field($searchModel, 'updated_by')->widget(\skeeks\cms\modules\admin\widgets\formInputs\SelectModelDialogUserInput::className()); ?>


    <?= $form->field($searchModel, 'created_at_from')->widget(
        \kartik\datetime\DateTimePicker::className()
    ); ?>
    <?= $form->field($searchModel, 'created_at_to')->widget(
        \kartik\datetime\DateTimePicker::className()
    ); ?>

    <?= $form->field($searchModel, 'updated_at_from')->widget(
        \kartik\datetime\DateTimePicker::className()
    ); ?>
    <?= $form->field($searchModel, 'updated_at_to')->widget(
        \kartik\datetime\DateTimePicker::className()
    ); ?>

    <?= $form->field($searchModel, 'published_at_from')->widget(
        \kartik\datetime\DateTimePicker::className()
    ); ?>
    <?= $form->field($searchModel, 'published_at_to')->widget(
        \kartik\datetime\DateTimePicker::className()
    ); ?>

    <?= $form->field($searchModel, 'code'); ?>


    <?
        $searchRelatedPropertiesModel = new \skeeks\cms\models\searchs\SearchRelatedPropertiesModel();
        $searchRelatedPropertiesModel->initProperties($cmsContent->cmsContentProperties);
        $searchRelatedPropertiesModel->load(\Yii::$app->request->get());
    ?>
    <?= $form->relatedFields($searchRelatedPropertiesModel); ?>

<? $form::end(); ?>

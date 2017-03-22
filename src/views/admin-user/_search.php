<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (�����)
 * @date 26.05.2016
 */

/* @var $this yii\web\View */
/* @var $searchModel common\models\searchs\Game */
/* @var $dataProvider yii\data\ActiveDataProvider */

?>
<? $form = \skeeks\cms\modules\admin\widgets\filters\AdminFiltersForm::begin([
        'action' => '/' . \Yii::$app->request->pathInfo,
    ]); ?>

    <?= $form->field($searchModel, 'q')->setVisible(); ?>

    <?= $form->field($searchModel, 'id'); ?>
    <?= $form->field($searchModel, 'role')->listBox(\yii\helpers\ArrayHelper::merge([
        '' => ' - '
    ], \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'description')), [
        'size' => 1
    ]); ?>

    <?= $form->field($searchModel, 'active')->listBox(\yii\helpers\ArrayHelper::merge([
        '' => ' - '
    ], \Yii::$app->cms->booleanFormat()), [
        'size' => 1
    ]); ?>

    <?= $form->field($searchModel, 'has_image')->checkbox(\Yii::$app->formatter->booleanFormat, false); ?>

    <?= $form->field($searchModel, 'email_is_approved')->listBox(\yii\helpers\ArrayHelper::merge([
        '' => ' - '
    ], \Yii::$app->formatter->booleanFormat), [
        'size' => 1
    ]); ?>

    <?= $form->field($searchModel, 'phone_is_approved')->listBox(\yii\helpers\ArrayHelper::merge([
        '' => ' - '
    ], \Yii::$app->formatter->booleanFormat), [
        'size' => 1
    ]); ?>

    <?= $form->field($searchModel, 'name') ?>
    <?= $form->field($searchModel, 'username') ?>
    <?= $form->field($searchModel, 'email') ?>
    <?= $form->field($searchModel, 'phone') ?>

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

    <?= $form->field($searchModel, 'auth_at_from')->widget(
        \kartik\datetime\DateTimePicker::className()
    ); ?>
    <?= $form->field($searchModel, 'auth_at_to')->widget(
        \kartik\datetime\DateTimePicker::className()
    ); ?>


    <?
        /**
         * @var $searchModel \skeeks\cms\models\CmsUser
         */
        $searchRelatedPropertiesModel = new \skeeks\cms\models\searchs\SearchRelatedPropertiesModel();
        $searchRelatedPropertiesModel->propertyElementClassName = \skeeks\cms\models\CmsUserProperty::className();
        $searchRelatedPropertiesModel->initProperties($searchModel->relatedProperties);
        $searchRelatedPropertiesModel->load(\Yii::$app->request->get());
        $searchRelatedPropertiesModel->search($dataProvider, $searchModel::tableName());
    ?>
    <?= $form->relatedFields($searchRelatedPropertiesModel); ?>

<? $form::end(); ?>

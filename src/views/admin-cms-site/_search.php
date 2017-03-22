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
]); ?>

    <?= $form->field($searchModel, 'name')->setVisible(true)->textInput([
        'placeholder' => \Yii::t('skeeks/cms', 'Search by name')
    ]) ?>

    <?= $form->field($searchModel, 'id') ?>

    <?= $form->field($searchModel, 'code'); ?>

    <?= $form->field($searchModel, 'active')->listBox(\yii\helpers\ArrayHelper::merge([
        '' => ' - '
    ], \Yii::$app->cms->booleanFormat()), [
        'size' => 1
    ]); ?>

<? $form::end(); ?>

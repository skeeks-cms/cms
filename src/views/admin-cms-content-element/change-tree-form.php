<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.10.2015
 */
$model = new \skeeks\cms\models\CmsContentElement();
?>
<?php $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin(); ?>

<?php /*= $form->field($model, 'tree_id')->widget(
        \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class
    ); */ ?>

<?= $form->field($model, 'tree_id')->widget(
    \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
); ?>

<?php /*= $form->fieldSelect($model, 'tree_id', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions());*/ ?>
<?= $form->buttonsStandart($model, ['save']); ?>

<?php \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>


<?php \yii\bootstrap\Alert::begin([
    'options' => [
        'class' => 'alert-warning',
        'style' => 'margin-top: 20px;',
    ],
]) ?>
    <p><?= \Yii::t('skeeks/cms', 'Attention! For checked items will be given a new primary section.') ?></p>
    <p><?= \Yii::t('skeeks/cms',
            'This will alter the page record, and it will cease to be available at the old address.') ?></p>
<?php \yii\bootstrap\Alert::end(); ?>
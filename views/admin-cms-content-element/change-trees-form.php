<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.10.2015
 */
$model = new \skeeks\cms\models\CmsContentElement();
?>
<? $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin(); ?>

    <?= $form->fieldSelectMulti($model, 'treeIds', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions())->label(\Yii::t('app','Additional sections'));?>

    <?= \yii\helpers\Html::checkbox('removeCurrent', false); ?> <label><?=\Yii::t('app','Get rid of the already linked (in this case, the selected records bind only to the selected section)')?></label>
    <?= $form->buttonsStandart($model, ['save']);?>

<? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>


<? \yii\bootstrap\Alert::begin([
    'options' => [
        'class' => 'alert-info',
    ],
])?>
    <p><?=\Yii::t('app','You can specify some additional sections that will show your records.')?></p>
    <p><?=\Yii::t('app','This does not affect the final address of the page, and hence safe.')?></p>
<? \yii\bootstrap\Alert::end(); ?>
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

    <?= $form->fieldSelectMulti($model, 'treeIds', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions())->label('Дополнительные разделы');?>

    <?= \yii\helpers\Html::checkbox('removeCurrent', false); ?> <label>Отвязать от уже привязанных (в этом случае выбранные записи, привяжутся только к выбранным разделам)</label>
    <?= $form->buttonsStandart($model, ['save']);?>

<? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>


<? \yii\bootstrap\Alert::begin([
    'options' => [
        'class' => 'alert-info',
    ],
])?>
    <p>Вы можете указать, несоклько дополнительных разделов в которых будут показаны ваши записи.</p>
    <p>Это не влияет на конечный адрес страницы, а следовательно безоспасно.</p>
<? \yii\bootstrap\Alert::end(); ?>
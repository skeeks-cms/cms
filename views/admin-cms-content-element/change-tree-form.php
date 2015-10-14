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

    <?= $form->fieldSelect($model, 'tree_id', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions());?>
    <?= $form->buttonsStandart($model, ['save']);?>

<? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>


<? \yii\bootstrap\Alert::begin([
    'options' => [
        'class' => 'alert-warning',
    ],
])?>
    <p>Внимание! Для отмеченных элементов будет задан новый основной раздел.</p>
    <p>Это приведет к изменению страницы записи, и она перестанет быть доступной по старому адресу.</p>
<? \yii\bootstrap\Alert::end(); ?>
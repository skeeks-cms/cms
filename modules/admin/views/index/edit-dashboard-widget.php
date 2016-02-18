<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.02.2016
 *
 * @var $this \yii\web\View
 * @var $model \skeeks\cms\models\CmsDashboardWidget
 */
?>

<? $form = \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::begin(); ?>

    <? if ($model->widget) : ?>
        <? $model->widget->renderConfigForm($form); ?>
    <? else : ?>
        Настройки не найдены
    <? endif;  ?>

    <?= $form->buttonsStandart($model->widget); ?>
<? \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::end(); ?>

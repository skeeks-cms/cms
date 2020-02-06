<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
?>
<?php $form = \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::begin([
    'enableAjaxValidation'   => false,
    'enableClientValidation' => false,
]); ?>

<?= $form->errorSummary(\yii\helpers\ArrayHelper::merge(
    [$component], $component->getConfigFormModels()
)); ?>

<? if ($fields = $component->getConfigFormFields()) : ?>
    <? $formContent = (new \skeeks\yii2\form\Builder([
        'models'     => $component->getConfigFormModels(),
        'model'      => $component,
        'activeForm' => $form,
        'fields'     => $fields,
    ]))->render(); ?>
<? else : ?>
    <? $formContent = $component->renderConfigForm($form); ?>
<? endif; ?>

<? if ($formContent) : ?>
    <?= $form->errorSummary(\yii\helpers\ArrayHelper::merge(
        [$component], $component->getConfigFormModels()
    )); ?>
    <?= $formContent; ?>
    <?= $form->buttonsStandart($component); ?>
    <?= $form->errorSummary(\yii\helpers\ArrayHelper::merge(
        [$component], $component->getConfigFormModels()
    )); ?>
<? else : ?>
    Нет редактируемых настроек для данного компонента
<? endif; ?>

<?php \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::end(); ?>
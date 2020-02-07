<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/**
 * @var $component \skeeks\cms\base\Component
 */
?>
<?php $form = $component->beginConfigForm(); ?>

<?= $form->errorSummary(\yii\helpers\ArrayHelper::merge(
    [$component], $component->getConfigFormModels()
)); ?>

<? if ($formContent = $component->renderConfigFormFields($form)) : ?>
    <?= $formContent; ?>
    <?= $form->buttonsStandart($component); ?>
    <?= $form->errorSummary(\yii\helpers\ArrayHelper::merge(
        [$component], $component->getConfigFormModels()
    )); ?>
<? else : ?>
    Нет редактируемых настроек для данного компонента
<? endif; ?>

<?php $form::end(); ?>
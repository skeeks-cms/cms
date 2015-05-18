<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.03.2015
 *
 * @var $formField \skeeks\modules\cms\form\models\FormField
 * @var $model \skeeks\modules\cms\form\models\FormValidateModel
 * @var $modelHasRelatedProperties \skeeks\cms\relatedProperties\models\RelatedElementModel
 */
use skeeks\cms\widgets\ActiveFormRelatedProperties as ActiveForm;
?>
    <?php $form = ActiveForm::begin([
        'modelHasRelatedProperties'                 => $modelHasRelatedProperties,
    ]);
?>
<? if ($properties = $modelHasRelatedProperties->relatedProperties) : ?>
    <? foreach ($properties as $property) : ?>
        <?= $property->renderActiveForm($form, $modelHasRelatedProperties->getRelatedPropertiesModel())?>
    <? endforeach; ?>
<? endif; ?>

<?= \yii\helpers\Html::button('Сохранить', [
    'type'  => 'submit',
    'class' => 'btn btn-primary'
]); ?>

<?php ActiveForm::end(); ?>
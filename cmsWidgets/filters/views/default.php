<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 13.10.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\shop\cmsWidgets\filters\ShopProductFiltersWidget */
?>

<?
$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.FiltersForm = sx.classes.Component.extend({

        _init: function()
        {
            var self = this;

            self.onDomReady(function()
            {
                self._initDomReady();
            });
        },

        _initDomReady: function()
        {
            var self = this;
            this.JqueryForm = $("#sx-filters-form");

            $("input, checkbox, select", this.JqueryForm).on("change", function()
            {
                self.JqueryForm.submit();
            });
        }
    });

    new sx.classes.FiltersForm();
})(sx, sx.$, sx._);
JS
)
?>
<? $form = \skeeks\cms\base\widgets\ActiveForm::begin([
    'options' =>
    [
        'id' => 'sx-filters-form',
        'data-pjax' => '1'
    ],
    'method' => 'get',
    'action' => "/" . \Yii::$app->request->getPathInfo(),
]); ?>

    <? if ($widget->searchModel) : ?>

        <? if (in_array('image', $widget->searchModelAttributes)) : ?>
            <?= $form->fieldSelect($widget->searchModel, "image", [
                '' => \skeeks\cms\shop\Module::t('app', 'Does not matter'),
                'Y' => \skeeks\cms\shop\Module::t('app', 'With photo'),
                'N' => \skeeks\cms\shop\Module::t('app', 'Without photo'),
            ]); ?>
        <? endif; ?>

    <? endif ; ?>

    <? if ($properties = $widget->searchRelatedPropertiesModel->properties) : ?>

        <? foreach ($properties as $property) : ?>
            <? if (in_array($property->code, $widget->realatedProperties)) : ?>

                <? if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT) : ?>

                    <?
                        $propertyType = $property->createPropertyType();
                        $options = \skeeks\cms\models\CmsContentElement::find()->active()->andWhere([
                            'content_id' => $propertyType->content_id
                        ])->all();

                        $options = \yii\helpers\ArrayHelper::map(
                            $options, 'id', 'name'
                        );

                    ?>
                    <?= $form->field($widget->searchRelatedPropertiesModel, $property->code)->checkboxList($options); ?>

                <? elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_LIST) : ?>
                    <?= $form->field($widget->searchRelatedPropertiesModel, $property->code)->checkboxList(\yii\helpers\ArrayHelper::map(
                        $property->enums, 'id', 'value'
                    )); ?>
                <? elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_NUMBER) : ?>
                    <?/*= $form->field($widget->searchRelatedPropertiesModel, $property->code)->textInput(); */?>

                    <div class="col-md-6">
                        <?= $form->field($widget->searchRelatedPropertiesModel, $widget->searchRelatedPropertiesModel->getAttributeNameRangeFrom($property->code) )->textInput([
                            'placeholder' => 'от'
                        ])->label(
                            $property->name . ""
                        ); ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($widget->searchRelatedPropertiesModel, $widget->searchRelatedPropertiesModel->getAttributeNameRangeTo($property->code) )->textInput([
                            'placeholder' => 'до'
                        ])->label("&nbsp;"); ?>
                    </div>

                <? else : ?>

                    <? $propertiesValues = \skeeks\cms\models\CmsContentElementProperty::find()->where([
                        'property_id' => $property->id,
                        'element_id'  => $widget->elementIds
                    ])->all(); ?>

                    <? if ($propertiesValues) : ?>
                        <div class="col-md-12">

                        <?= $form->field($widget->searchRelatedPropertiesModel, $property->code)->dropDownList(
                            \yii\helpers\ArrayHelper::merge(['' => ''], \yii\helpers\ArrayHelper::map(
                                $propertiesValues, 'value', 'value'
                            )))
                        ; ?>

                        </div>
                    <? endif; ?>

                <? endif; ?>

            <? endif; ?>


        <? endforeach; ?>
    <? endif; ?>



    <button class="btn btn-primary"><?= \Yii::t('app', 'Apply');?></button>

<? \skeeks\cms\base\widgets\ActiveForm::end(); ?>

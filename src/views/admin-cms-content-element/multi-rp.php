<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.10.2015
 */
/* @var $this yii\web\View */
/* @var $action \skeeks\cms\modules\admin\actions\modelEditor\AdminMultiDialogModelEditAction */
/* @var $content \skeeks\cms\models\CmsContent */

$model = new \skeeks\cms\models\CmsContentElement();

$jsData = \yii\helpers\Json::encode([
    'id' => $action->id
]);

$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.MultiRP = sx.classes.Component.extend({

        _onDomReady: function()
        {
            var self = this;
            this.jWrapper = $("#" + this.get('id'));
            this.jForm = $('form', this.jWrapper);
            this.jSelect = $('.sx-select', this.jWrapper);

            this.jSelect.on('change', function()
            {
                $(".sx-multi", self.jForm).slideUp();

                if (self.jSelect.val())
                {
                    self.jForm.show();
                } else
                {
                    self.jForm.hide();
                }

                _.each(self.jSelect.val(), function(element)
                {
                    $(".sx-multi-" + element, self.jForm).slideDown();

                });
            });
        }
    });

    new sx.classes.MultiRP({$jsData});
})(sx, sx.$, sx._);
JS
);
?>
<div id="<?= $action->id; ?>">
    <?php if ($action->controller && $action->controller->content) : ?>

        <?php $content = $action->controller->content; ?>
        <?php $element = $content->createElement(); ?>
        <?php $element->loadDefaultValues(); ?>

        <?
            $rpm = $element->relatedPropertiesModel;
        ?>

        <?php if ($element && $rpm) : ?>

            <? $rpm->initAllProperties(); ?>
            <?php $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
                'options' => [
                    'class' => 'sx-form',
                ]
            ]); ?>
            <?= \skeeks\widget\chosen\Chosen::widget([
                'multiple' => true,
                'name' => 'fields',
                'options' => [
                    'class' => 'sx-select'
                ],
                'items' => $rpm->attributeLabels()
            ]); ?>

            <?= \yii\helpers\Html::hiddenInput('content_id', $content->id); ?>


            <?php foreach ($rpm->getProperties() as $property) : ?>
                <div class="sx-multi sx-multi-<?= $property->code; ?>" style="display: none;">
                    <?php if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT) : ?>

                        <?php if ($property->handler->fieldElement == \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeElement::FIELD_ELEMENT_SELECT) : ?>
                            <?
                            echo $form->field($rpm, $property->code)->widget(
                                \skeeks\cms\backend\widgets\SelectModelDialogContentElementWidget::class,
                                [
                                    'content_id' => $property->handler->content_id
                                ]
                            );
                            ?>
                        <?php else
                            : ?>
                            <?
                            echo $form->field($rpm, $property->code)->widget(
                                \skeeks\cms\backend\widgets\SelectModelDialogContentElementWidget::class,
                                [
                                    'content_id' => $property->handler->content_id,
                                    'multiple' => true
                                ]
                            );
                            ?>
                        <?php endif; ?>
                    <?php else
                        : ?>
                        <?= $property->renderActiveForm($form);
                        ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?= $form->buttonsStandart($model, ['save']); ?>
            <?php $form::end(); ?>
        <?php else
            : ?>
            Not found properties
        <?php endif;
        ?>
    <?php endif; ?>
</div>




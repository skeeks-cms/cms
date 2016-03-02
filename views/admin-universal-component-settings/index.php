<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.06.2015
 */
/* @var $this yii\web\View */
/* @var $component \skeeks\cms\relatedProperties\PropertyType */
/* @var $saved bool */
$getData            = \Yii::$app->request->get();
$clientOptions      = $getData;
$clientOptions['saveUrl'] = \skeeks\cms\helpers\UrlHelper::constructCurrent()->setRoute('/cms/admin-universal-component-settings/save')->toString();
$clientOptions      = \yii\helpers\Json::encode($clientOptions);


?>
<? if ($forSave) : ?>
    <?= $forSave; ?>
<? endif; ?>

<? if ($component instanceof \skeeks\cms\base\ConfigFormInterface) : ?>


    <? $form = \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::begin(); ?>
        <? $component->renderConfigForm($form); ?>
        <?= $form->buttonsStandart($component); ?>
    <? \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::end(); ?>

<? else: ?>
    <? if ($component->existsConfigFormFile()) : ?>
        <?= $component->renderConfigForm(); ?>
    <? else: ?>
        <p>Настройки отсутствуют</p>
    <? endif; ?>
<? endif; ?>

<? $this->registerJs(<<<JS

(function(sx, $, _)
{
    sx.classes.ComponentSettingsSaver = sx.classes.Component.extend({

        _init: function()
        {
            if (!window.parent)
            {
                throw new Error("Не найден родительский компонент");
            }
        },

        _onDomReady: function()
        {
            var self = this;

            $(document).on('pjax:complete', function() {
               self.save();
            });
        },

        save: function()
        {
            var self = this;
            var ajax = sx.ajax.preparePostQuery(this.get('saveUrl'));
            var ajaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajax);
            ajaxHandler.bind('success', function(e, response)
            {
                self.getCallbackComponent().save(response.data.forSave);
            });
            ajax.setData($("form").serialize());
            ajax.execute();
        },

        _onWindowReady: function()
        {},

        /**
        * Найти компонент который породил это окно, он же будет использоваться в качестве дальнейшего обработчика
        * @returns {sx.classes.Component}
        */
        getCallbackComponent: function()
        {
            var self = this;

            return _.find(window.parent.sx.components, function(Component)
            {
                if (Component instanceof window.parent.sx.classes.Component)
                {
                    if (Component.get('id') == self.get('callbackComonentId'))
                    {
                        return Component;
                    }
                }
            });
        }
    });

    new sx.classes.ComponentSettingsSaver({$clientOptions});

})(sx, sx.$, sx._);


JS
);?>
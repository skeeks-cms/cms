<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 11.07.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\modules\admin\widgets\formInputs\CmsContentElementInput */
?>
<div class="row" id="<?= $widget->id; ?>">
    <div class="col-lg-12">
        <div class="row">
            <div class="sx-one-input col-lg-12">
                <? if ($widget->model) : ?>
                    <?= \yii\helpers\Html::activeHiddenInput($widget->model, $widget->attribute); ?>
                <? else: ?>
                    <?= \yii\helpers\Html::hiddenInput($widget->id, $widget->attribute); ?>
                <? endif; ?>

                <span class="sx-view-cms-content">
                    <? if ($widget->modelData) : ?>
                        <a href="#" target="_blank" data-pjax="0">
                            <?= $widget->modelData->displayName; ?>
                        </a>
                    <? endif; ?>
                </span>

                <a class="btn btn-default btn-xs sx-btn-create">
                    <i class="glyphicon glyphicon-pencil"></i>
                </a>

                <? if ($widget->allowDeselect) : ?>
                    <a class="btn btn-default btn-danger btn-xs sx-btn-deselect" <?= !$widget->modelData ? "style='display: none;'": ""?>>
                        <i class="glyphicon glyphicon-remove"></i>
                    </a>
                <? endif; ?>

            </div>
        </div>
    </div>
</div>
<?
$jsonOptions = $widget->getJsonOptions();
$this->registerCss(<<<CSS
.sx-one-image img
{
    width: 100%;
}
CSS
);

$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.SelectCmsElement = sx.classes.Component.extend({

        _init: function()
        {
            var self = this;

            sx.EventManager.bind(this.get('callbackEvent'), function(e, data)
            {
                self.update(data);
            });
        },

        _onDomReady: function()
        {
            var self = this;

            this.jQueryCreateBtn        = $(".sx-btn-create", this.jQuryWrapper());
            this.jQueryInput            = $("input", this.jQuryWrapper());
            this.jQueryContentWrapper   = $(".sx-view-cms-content", this.jQuryWrapper());
            this.jQueryDeselectBtn      = $(".sx-btn-deselect", this.jQuryWrapper());

            this.jQueryCreateBtn.on("click", function()
            {
                self.openModalWindow();
                return this;
            });

            this.jQueryDeselectBtn.on("click", function()
            {
                self.update({});
                return this;
            });
        },

        update: function(model)
        {
            var self = this;

            self.setVal();
            this.jQueryContentWrapper.empty();
            this.jQueryDeselectBtn.hide();

            if (_.size(model) > 0)
            {
                this.jQueryContentWrapper.append(
                    '<a href="' + model.url + '" target="_blank" data-pjax="0">' + model.displayName + '</a>'
                );
                self.setVal(model.id);
                this.jQueryDeselectBtn.show();
            }

            self.trigger('change', model);

            return this;
        },

        /**
        * @param id
        */
        setVal: function(id)
        {
            $("input", this.jQuryWrapper()).val(id).change();
        },

        /**
        *
        * @returns {sx.classes.SelectOneImage}
        */
        openModalWindow: function()
        {
            this.Window = new sx.classes.WindowOriginal(this.get('selectUrl'), 'sx-select-input-' + this.get('id'));
            this.Window.open();

            return this;
        },
        /**
        *
        * @returns {*|HTMLElement}
        */
        jQuryWrapper: function()
        {
            return $('#' + this.get('id'));
        }
    });

    new sx.classes.SelectCmsElement({$jsonOptions});
})(sx, sx.$, sx._);
JS
)
?>
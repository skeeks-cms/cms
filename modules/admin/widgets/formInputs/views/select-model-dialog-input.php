<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 11.07.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\modules\admin\widgets\formInputs\SelectModelDialogInput */
?>
<div class="row" id="<?= $widget->id; ?>">
    <div class="col-lg-12">

        <div class="row sx-one-input">

            <div class="col-lg-3">
                <? if ($widget->model) : ?>
                    <?= \yii\helpers\Html::activeTextInput($widget->model, $widget->attribute, [
                        'class' => 'form-control'
                    ]); ?>
                <? else: ?>
                    <?= \yii\helpers\Html::textInput($widget->id, $widget->attribute, [
                        'class' => 'form-control'
                    ]); ?>
                <? endif; ?>
            </div>

            <div class="col-lg-6">

                <a class="btn btn-default sx-btn-create">
                    <i class="glyphicon glyphicon-th-list" title="Выбрать"></i>
                </a>

                <? if ($widget->allowDeselect) : ?>
                    <a class="btn btn-default btn-danger sx-btn-deselect" <?= !$widget->getModelData() ? "style='display: none;'": ""?> title="Убрать выбранное">
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
    sx.classes.SelectModelDialog = sx.classes.Component.extend({

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
            //this.jQueryContentWrapper.empty();
            this.jQueryDeselectBtn.hide();

            if (_.size(model) > 0)
            {
                /*this.jQueryContentWrapper.append(
                    '<a href="' + model.url + '" target="_blank" data-pjax="0">' + model.name + '</a>'
                );*/
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

    new sx.classes.SelectModelDialog({$jsonOptions});
})(sx, sx.$, sx._);
JS
)
?>
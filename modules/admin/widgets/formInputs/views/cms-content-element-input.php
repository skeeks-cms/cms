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
                    <?= \yii\helpers\Html::activeHiddenInput($widget->id, $widget->attribute); ?>
                <? endif; ?>
                <? if ($widget->cmsContentElement) : ?>
                    <span class="sx-view-cms-content">
                        <a href="<?= $widget->cmsContentElement->url; ?>" target="_blank" data-pjax="0"><?= $widget->cmsContentElement->name; ?></a>
                    </span>
                <? endif; ?>
                <a class="btn btn-default btn-xs sx-btn-create-file-manager">
                    <i class="glyphicon glyphicon-pencil"></i>
                </a>
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
                self.setFile(data.file);
            });
        },

        _onDomReady: function()
        {
            var self = this;

            this.jQueryCreateBtn        = $(".sx-btn-create-file-manager", this.jQuryWrapper());
            this.jQueryInput            = $("input", this.jQuryWrapper());
            this.jQueryImage            = $(".sx-one-image img", this.jQuryWrapper());
            this.jQueryImageA           = $(".sx-one-image a", this.jQuryWrapper());

            this.jQueryCreateBtn.on("click", function()
            {
                self.createFileManager();
                return this;
            });

            this.jQueryInput.on("keyup", function()
            {
                self.update();
                return this;
            });

            this.jQueryInput.on("change", function()
            {
                self.update();
                return this;
            });

            self.update();
        },

        update: function()
        {
            this.jQueryImage.attr('src', this.jQueryInput.val());
            this.jQueryImageA.attr('href', this.jQueryInput.val());
            return this;
        },

        /**
        *
        * @param file
        */
        setFile: function(file)
        {
            $("input", this.jQuryWrapper()).val(file).change();
        },

        /**
        *
        * @returns {sx.classes.SelectOneImage}
        */
        createFileManager: function()
        {
            this.WindowFileManager = new sx.classes.WindowOriginal(this.get('selectUrl'), 'sx-select-file-manager');
            this.WindowFileManager.open();

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
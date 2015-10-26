<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 11.07.2015
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\modules\admin\widgets\formInputs\OneImage */
?>
<div class="row" id="<?= $widget->id; ?>">
    <div class="col-lg-12">
        <div class="row">
            <div class="sx-one-input col-lg-8 col-md-8 col-sm-8">
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
            <div class="sx-one-btn col-lg-2 col-md-2 col-sm-2">
                <a class="btn btn-default sx-btn-create-file-manager"><i class="glyphicon glyphicon-download-alt"></i> <?=\Yii::t('app','Choose file')?></a>
            </div>

            <div class="sx-one-image col-lg-1 col-md-1 col-sm-1">
                <a href="" class="sx-fancybox" data-pjax="0" target="_blank">
                    <img src="" />
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
    sx.classes.SelectOneImage = sx.classes.Component.extend({

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
            this.WindowFileManager = new sx.classes.WindowOriginal(this.get('selectFileUrl'), 'sx-select-file-manager');
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

    new sx.classes.SelectOneImage({$jsonOptions});
})(sx, sx.$, sx._);
JS
)
?>
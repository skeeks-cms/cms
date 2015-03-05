<?php
/**
 * widget
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 13.11.2014
 * @since 1.0.0
 */
use \skeeks\cms\widgets\formInputs\selectTree\SelectTree;
/**
 * @var \skeeks\cms\widgets\formInputs\selectTree\SelectTree $widget
 */
$idSmartFrame = $id . "-smart-frame";
?>

<div id="<?= $id; ?>">
    <p>
        <? if ($widget->mode == SelectTree::MOD_COMBO) : ?>

        <small>Кружочек — главнй раздел (можно выбрать один раздел, это будет влиять на построение хлебных крошек)</small><br />
        <small>Квадратик — доболнительный раздел (можно отметить несколько дополнительных разделов)</small>
        <? elseif($widget->mode == SelectTree::MOD_MULTI) : ?>
            <small>Квадратик — доболнительный раздел (можно отметить несколько дополнительных разделов)</small>
        <? endif; ?>
    </p>
    <iframe src="<?= $src; ?>" width="100%;" height="200px;" id="sx-test"></iframe>
    <div class="sx-selected">
        <?= $select; ?>
        <?= $singleInput; ?>
    </div>
</div>

<? $this->registerJs(<<<JS

(function(sx, $, _)
{
    sx.createNamespace('classes.app', sx);

    sx.classes.app.TreeSelect = sx.classes.Widget.extend({

        _init: function()
        {
            var self = this;

            this.Iframe = new sx.classes.Iframe('sx-test', {
                'autoHeight'        : true,
                'heightSelector'    : '.sx-panel-content'
            });

            this.Iframe.onSxReady(function()
            {
                if (self.Iframe.sx.Tree)
                {
                    self.Iframe.sx.Tree.bind('select', function(e, data)
                    {
                        self.addTrees(data.selected);
                    });

                    self.Iframe.sx.Tree.bind('selectSingle', function(e, data)
                    {
                        self.JsingleInput.val(data.id);
                    });
                }
            });

        },

        _onDomReady: function()
        {
            var self = this;

            this._containerSelected     = $('.sx-selected ul',              this.getWrapper());
            this._containerControlls    = $('.sx-controlls',                this.getWrapper());
            this._controllElement       = $('.sx-controll-element',         this.getWrapper());
            this._btnCreateWindow       = $('.sx-controll-window-create',   this.getWrapper());
            this.JsingleInput           = $('input.sx-single',              this.getWrapper());

            if (this.get('selected'))
            {
                console.log(this.get('selected'));
                this.addTrees(this.get('selected'));
            }

            this.Iframe.onSxReady(function()
            {
                if (self.Iframe.sx.Tree)
                {
                    self.Iframe.sx.Tree.setSingle(self.JsingleInput.val());
                }
            });
        },


        addTrees: function(data)
        {
            var self = this;

            self._controllElement.empty();

            _.each(data, function(value, key)
            {
                self._controllElement.append('<option value="' + value + '" selected="selected">name</option>')
            })
        },

        _onWindowReady: function()
        {}
    });

    new sx.classes.app.TreeSelect('#{$id}', {$clientOptions});
})(sx, sx.$, sx._);

JS
);?>
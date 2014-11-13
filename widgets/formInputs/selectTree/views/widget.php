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

/**
 * @var \skeeks\cms\widgets\formInputs\selectTree\SelectTree $widget
 */

?>

<div id="<?= $id; ?>">
    <div class="sx-selected">

    </div>
    <div class="sx-controlls">
        <a href="#" class="btn btn-xs btn-default sx-controll-window-create">Выбрать разделы</a>
    </div>
</div>

<? $this->registerJs(<<<JS

(function(sx, $, _)
{
    sx.createNamespace('classes.app', sx);

    sx.classes.app.TreeSelect = sx.classes.Widget.extend({

        _init: function()
        {

        },

        _onDomReady: function()
        {
            var self = this;
            this._containerSelected     = $('.sx-selected',                 this.getWrapper());
            this._containerControlls    = $('.sx-controlls',                this.getWrapper());
            this._btnCreateWindow       = $('.sx-controll-window-create',   this.getWrapper());

            this._windowTree = null;

            this._btnCreateWindow.on('click', function(e, data)
            {
                self.getWindow().open();
                return false;
            });
        },

        /**
        *
        * @returns {null|*}
        */
        getWindow: function()
        {
            if (this._windowTree === null)
            {
                this._windowTree = new sx.classes.Window(this.get('src'), this.get('name')).setCenterOptions();

                this._windowTree.bind('selectedNodes', function(e, data)
                {
                    console.log(data);
                });
            }

            return this._windowTree;
        },

        _onWindowReady: function()
        {}
    });

    new sx.classes.app.TreeSelect('#{$id}', {$clientOptions});
})(sx, sx.$, sx._);

JS
);?>
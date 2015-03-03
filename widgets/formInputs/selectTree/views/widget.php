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
    <iframe src="<?= $src; ?>" width="100%;" height="200px;"></iframe>
    <div class="sx-selected">
        <?= $select; ?>
        <ul>
        </ul>
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
            this._containerSelected     = $('.sx-selected ul',              this.getWrapper());
            this._containerControlls    = $('.sx-controlls',                this.getWrapper());
            this._controllElement       = $('.sx-controll-element',         this.getWrapper());
            this._btnCreateWindow       = $('.sx-controll-window-create',   this.getWrapper());

            this._windowTree = null;

            this._btnCreateWindow.on('click', function(e, data)
            {
                self.getWindow().open();
                return false;
            });

            if (this.get('selected'))
            {
                this.addTrees(this.get('selected'));
            }
        },

        /**
        *
        * @returns {null|*}
        */
        getWindow: function()
        {
            var self = this;

            if (this._windowTree === null)
            {
                this._windowTree = new sx.classes.Window(this.get('src'), this.get('name')).setCenterOptions();

                this._windowTree.bind('selected', function(e, data)
                {
                    self.addTrees(data.selected);
                });
            }

            return this._windowTree;
        },

        addTrees: function(data)
        {
            var self = this;

            self._controllElement.empty();
            self._containerSelected.empty();

            _.each(data, function(value, key)
            {
                self._controllElement.append('<option value="' + value.id + '" selected="selected">name</option>')
                self._containerSelected.append(
                    $('<li>')
                        .append(value.name)
                );
            })
        },

        _onWindowReady: function()
        {}
    });

    new sx.classes.app.TreeSelect('#{$id}', {$clientOptions});
})(sx, sx.$, sx._);

JS
);?>
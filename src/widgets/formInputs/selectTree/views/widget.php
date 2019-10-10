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
?>

    <div id="<?= $id; ?>">
        <p>
            <?php if ($widget->mode == SelectTree::MOD_COMBO) : ?>

                <small><?= \Yii::t('skeeks/cms',
                        'Circle — the main section (you can choose one section, it will affect the construction of bread crumbs)') ?></small>
                <br/>
                <small><?= \Yii::t('skeeks/cms',
                        'The square - an additional section (you can mark several additional sections)') ?></small>
            <?php elseif ($widget->mode == SelectTree::MOD_MULTI) : ?>
                <small><?= \Yii::t('skeeks/cms',
                        'The square - an additional section (you can mark several additional sections)') ?></small>
            <?php endif; ?>
        </p>
        <iframe data-src="<?= $src; ?>" width="100%;" height="200px;" id="<?= $idSmartFrame; ?>"></iframe>
        <div class="sx-selected">
            <?= $select; ?>
            <?= $singleInput; ?>
        </div>
    </div>

<?php
\skeeks\cms\themes\unify\admin\assets\UnifyAdminIframeAsset::register($this);
$this->registerJs(<<<JS

(function(sx, $, _)
{
    sx.createNamespace('classes.app', sx);

    sx.classes.app.TreeSelect = sx.classes.Component.extend({

        _init: function()
        {

            var self = this;

            console.log(this.get('idSmartFrame'));
            
            this.Iframe = new sx.classes.Iframe(this.get('idSmartFrame'), {
                'autoHeight'        : true,
                'heightSelector'    : 'main'
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

        /**
         * @returns {*|HTMLElement}
         */
        getWrapper: function()
        {
            return $('#' + this.get('id'));
        },

        _onDomReady: function()
        {
            var self = this;

            this.JsingleInput           = $('input.sx-single',              this.getWrapper());
            this.JmultiInput           = $('select.sx-multi',              this.getWrapper());

            if (this.get('selected'))
            {
                this.addTrees(this.get('selected'));
            }

            this.Iframe.onSxReady(function()
            {
                if (self.Iframe.sx.Tree)
                {
                    self.Iframe.sx.Tree.setSingle(self.JsingleInput.val());
                    self.Iframe.sx.Tree.setSelect(self.JmultiInput.val());
                }
            });

            _.delay(function()
            {
                $('#' + self.get('idSmartFrame')).attr('src', $('#' + self.get('idSmartFrame')).data('src'));
            }, 200);

        },


        addTrees: function(data)
        {
            var self = this;

            this.getWrapper().find('.sx-controll-element').empty();

            _.each(data, function(value, key)
            {
                self.getWrapper().find('.sx-controll-element').append(
                    $("<option>", {
                        'value' : value,
                        'selected' : 'selected'
                    }).text('text')
                );
            });

        },

    });

    new sx.classes.app.TreeSelect({$clientOptions});
})(sx, sx.$, sx._);

JS
); ?>
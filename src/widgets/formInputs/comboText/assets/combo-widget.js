/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.06.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.combotext', sx);
    sx.createNamespace('classes.combotext.controlls', sx);

    sx.classes.combotext.controlls._Base = sx.classes.Component.extend({

        construct: function (ComboTextInputWidget, opts)
        {
            var self = this;
            opts = opts || {};
            if (!ComboTextInputWidget instanceof sx.classes.combotext.ComboTextInputWidget)
            {
                throw new Error('Некорректный виджет для этого контрола');
            }

            this.widget = ComboTextInputWidget;
            //this.parent.construct(opts);
            this.applyParentMethod(sx.classes.Component, 'construct', [opts]); // TODO: make a workaround for magic parent calling
        },

        _init: function()
        {},

        run: function()
        {

        },

        stop: function()
        {

        },
    });


    sx.classes.combotext.controlls.Codemirror = sx.classes.combotext.controlls._Base.extend({

        _init: function()
        {
            this.Instance = null;
        },

        run: function()
        {
            var self = this;
            this.Instance = CodeMirror.fromTextArea(document.getElementById(this.widget.get('inputId')));
            var options = this.widget.get('codemirror');
            if (options)
            {
                _.each(options, function(val, key)
                {
                    self.Instance.setOption(key, val);
                });
            }

            self.Instance.focus();

            return this;
        },


        /**
        *
        * @returns {sx.classes.combotext.controlls.Codemirror}
        */
        stop: function()
        {
            if (this.Instance)
            {
                this.Instance.save();
                this.Instance.display.wrapper.remove();

                $("#" + this.widget.get('inputId')).show();
            }

            return this;
        },


    });


    /**
     * The visual mode: the light RichTextInputWidget editing the same textarea.
     * A text with scripts, styles or embeds stays locked: it is edited in the HTML mode.
     */
    sx.classes.combotext.controlls.RichText = sx.classes.combotext.controlls._Base.extend({

        root: function()
        {
            return $('[data-sx-combo-richtext]', this.widget.jQueryWrapper);
        },

        run: function()
        {
            var jRoot = this.root();
            $('#' + this.widget.get('inputId')).hide();
            jRoot.prop('hidden', false);
            sx.RichText.load(jRoot);
            return this;
        },

        stop: function()
        {
            this.root().prop('hidden', true);
            $('#' + this.widget.get('inputId')).show();
            return this;
        }
    });

    //Основной объект
    sx.classes.combotext.ComboTextInputWidget = sx.classes.Component.extend({

        _init: function()
        {
            this.ControllRichText   = new sx.classes.combotext.controlls.RichText(this);
            this.ControllCodemirror   = new sx.classes.combotext.controlls.Codemirror(this);
            this.currentControll    = null;
        },

        _onDomReady: function()
        {
            var self = this;
            this.jQueryWrapper = $('#' + this.get('id'));

            $(".sx-select-controll input[type=radio]", this.jQueryWrapper).on('change', function()
            {
                self.goControll($(this).val());
                return false;
            });

            self.goControll($(".sx-select-controll input[type=radio]:checked", this.jQueryWrapper).val());
        },

        goControll: function(code)
        {
            if (this.currentControll)
            {
                this.currentControll.stop();
            }

            this.currentControll = null;

            if (code == 'editor')
            {
                this.currentControll = this.ControllRichText;
                this.ControllRichText.run();

            } else if (code == 'html')
            {
                this.currentControll = this.ControllCodemirror;
                this.ControllCodemirror.run();
            } else
            {
                //sx.notify.error('Еще не реализовано');
            }
        },

    });

})(sx, sx.$, sx._);
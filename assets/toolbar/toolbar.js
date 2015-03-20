/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.03.2015
 */

(function(sx, $, _)
{
    sx.createNamespace('classes.toolbar', sx);

    /**
     * Базовый тулбар контейнер
     */
    sx.classes.toolbar._Base = sx.classes.Widget.extend({

        show: function()
        {
            this.getWrapper().show();
            this.update();
        },

        hide: function()
        {
            this.getWrapper().hide();
            this.update();
        },

        update: function()
        {}
    });

    sx.classes.toolbar.Min = sx.classes.toolbar._Base.extend({});

    sx.classes.toolbar.Full = sx.classes.toolbar._Base.extend({

        update: function()
        {
            if (this.getWrapper().is(":visible"))
            {
                $('html').css('margin-top', this.getWrapper().height());
            } else
            {
                $('html').css('margin-top', 0);
            }
        }

    });


    /**
     *
     */
    sx.classes.toolbar.Dialog = sx.classes.Component.extend({

        construct: function (url, opts)
        {
            var self = this;
            opts = opts || {};
            this.url = url;
            //this.parent.construct(opts);
            this.applyParentMethod(sx.classes.Component, 'construct', [opts]); // TODO: make a workaround for magic parent calling
        },

        _init: function()
        {
            this.window = new sx.classes.Window(this.url);
            this.window.bind('close', function()
            {
                //sx.notify.info('Страница сейчас будет перезагружена');

                _.defer(function()
                {
                    sx.block('body');
                    _.delay(function()
                    {
                        window.location.reload();
                    }, 100);

                });
            });

            this.window.open();
        },

        _onDomReady: function()
        {},

        _onWindowReady: function()
        {}
    });



    sx.classes.SkeeksToolbar = sx.classes.Component.extend({

        _init: function()
        {
            this.getCookieManager().setNamespace('skeeks-toolbar');

            this.Min    = new sx.classes.toolbar.Min('#' + this.get('container-min-id'));
            this.Full   = new sx.classes.toolbar.Full('#' + this.get('container-id'));
        },

        _onDomReady: function()
        {
            var self = this;

            _.defer(function()
            {
                if (self.getCookieManager().get('container') == 'opened')
                {
                    self.open();
                } else
                {
                    self.close();
                }
            });

            $('body').on('click', '.skeeks-cms-toolbar-edit-mode', function()
            {
                new sx.classes.toolbar.Dialog($(this).data('config-url'));
                return false;
            });
        },

        open: function()
        {
            this.Min.hide();
            this.Full.show();

            this.getCookieManager().set('container', 'opened');
        },

        close: function()
        {
            this.Min.show();
            this.Full.hide();

            this.getCookieManager().set('container', 'closed');
        },

        triggerEditMode: function()
        {
            var ajax = sx.ajax.preparePostQuery(this.get('backend-url-triggerEditMode'));

            new sx.classes.AjaxHandlerNotify(ajax);
            new sx.classes.AjaxHandlerBlocker(ajax, {
                'wrapper' : 'body'
            })

            ajax.bind('complete', function(e)
            {
                window.location.reload();
            });

            ajax.execute();
        }
    });

})(sx, sx.$, sx._);
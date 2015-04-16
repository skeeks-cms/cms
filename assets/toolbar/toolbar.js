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



    sx.classes.Infoblock = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            var self = this;

            this.jWrapper = $('#' + this.get('id'));
            this._createBorder();

            this.jWrapper.hover(
                function ()
                {
                    self._adjast();
                    self.jBorder.show();
                },
                function ()
                {
                    self.jBorder.hide();
                }
            );
        },

        _adjast : function()
        {
            var height  = this.jWrapper.height();
            var width   = this.jWrapper.width();
            var top     = this.jWrapper.offset().top;
            var left    = this.jWrapper.offset().left;

            if (height == 0)
            {
                height = 10;
            }

            this.jBorderTop
                .css('top', top)
                .css('left', left)
                .css('width', width)
            ;

            this.jBorderRight
                .css('top', top)
                .css('left', left + width)
                .css('height', height)
            ;

            this.jBorderBottom
                .css('top', top + height)
                .css('left', left)
                .css('width', width)
            ;

            this.jBorderLeft
                .css('top', top)
                .css('left', left)
                .css('height', height)
            ;
        },

        _createBorder: function()
        {
            this.jBorder = $("<div>", {
                'style' : 'display: none; height: 0px; width: 0px;'
            }).appendTo($('body'));

            this.jBorderTop = $('<div>')
                                .css('position', 'absolute')
                                .css('height', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', 'red')
                                .appendTo(this.jBorder);

            this.jBorderRight = $('<div>')
                                .css('position', 'absolute')
                                .css('width', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', 'red')
                                .appendTo(this.jBorder);


            this.jBorderBottom = $('<div>')
                                .css('position', 'absolute')
                                .css('height', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', 'red')
                                .appendTo(this.jBorder);

            this.jBorderLeft = $('<div>')
                                .css('position', 'absolute')
                                .css('width', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', 'red')
                                .appendTo(this.jBorder);

        },

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
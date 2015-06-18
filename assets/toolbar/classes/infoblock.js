/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.04.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.toolbar', sx);

    sx.classes.toolbar.Infoblock = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            var self = this;

            this.jWrapper = $('#' + this.get('id'));
            this._createBorder();

            var height  = this.jWrapper.height();
            if (height == 0)
            {
                this.jWrapper.css('height', '10px');
            }

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

        /**
         * Цвет рамки в режиме редактирования
         * @returns {*}
         */
        getBorderColor: function()
        {
            var settings = sx.Toolbar.getInfoblockSettings();
            if (settings.border)
            {
                if (settings.border.color)
                {
                    return String(settings.border.color);
                }
            }

            return 'red';
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
                                .css('background', this.getBorderColor())
                                .appendTo(this.jBorder);

            this.jBorderRight = $('<div>')
                                .css('position', 'absolute')
                                .css('width', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', this.getBorderColor())
                                .appendTo(this.jBorder);


            this.jBorderBottom = $('<div>')
                                .css('position', 'absolute')
                                .css('height', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', this.getBorderColor())
                                .appendTo(this.jBorder);

            this.jBorderLeft = $('<div>')
                                .css('position', 'absolute')
                                .css('width', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', this.getBorderColor())
                                .appendTo(this.jBorder);

        },

        _onWindowReady: function()
        {}
    });

})(sx, sx.$, sx._);
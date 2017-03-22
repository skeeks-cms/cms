/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.04.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.toolbar', sx);

    sx.classes.toolbar.EditViewBlock = sx.classes.Component.extend({

        _init: function()
        {
            this._isVisible = false;
        },

        _onDomReady: function()
        {
            var self = this;

            //С этой областья юмы работаем
            this.jWrapper = $('#' + this.get('id'));
            //Инициализация невидимого контейнера
            this._createHiddenBorder()._createHiddenControlls();
            this.adjustWrapper();

            this.jWrapper.on("dblclick", function()
            {
                self.goEdit();
                return false;
            });

            this.jWrapper.hover(
                function ()
                {
                    self.adjustHidden().show();
                },
                function ()
                {
                    self._isVisible = false;
                    _.delay(function()
                    {
                        if (self._isVisible === false)
                        {
                            self.hide();
                        }
                    }, 200);
                }
            );

            this.jHiddenControllBtns.hover(
                function ()
                {
                    self.adjustHidden().show();
                },
                function ()
                {
                    self._isVisible = false;
                    _.delay(function()
                    {
                        if (self._isVisible === false)
                        {
                            self.hide();
                        }
                    }, 200);
                }
            );


            $(window).on('scroll', function()
            {
                if (self._isVisible === true)
                {
                    self.adjustHidden().show();
                }
            })
        },


        show: function()
        {
            var self = this;
            this._isVisible = true;
            self.jHiddenBorders.show();
            self.jHiddenControllBtns.show();
        },

        hide: function()
        {
            var self = this;

            self.jHiddenBorders.hide();
            self._isVisible = false;
            self.jHiddenControllBtns.hide();
        },

        /**
         * Действие редактирования блока
         */
        goEdit: function()
        {
            new sx.classes.toolbar.Dialog(this.jWrapper.data('config-url'));
        },

        /**
         * Создание невидимых контейнеров кнопок
         * @returns {sx.classes.toolbar.EditViewBlock}
         * @private
         */
        _createHiddenControlls: function()
        {
            var self = this;

            this.jHiddenControllBtns = $('<div>').addClass('sx-edit-view-block-controlls')
                                        .appendTo($('body'));

            this.jHiddenControllEdit = $('<a>').append("Редактировать").appendTo(this.jHiddenControllBtns).on('click', function()
            {
                self.goEdit();
                return false;
            });

            return this;
        },

        /**
         * Создание невидимых контейнеров, которые накладываются на область
         *
         * @returns {sx.classes.toolbar.EditViewBlock}
         * @private
         */
        _createHiddenBorder: function()
        {

            this.jHiddenBorders = $("<div>", {
                'style' : 'display: none; height: 0px; width: 0px;'
            }).appendTo($('body'));


            this.jBorderTop = $('<div>')
                                .css('position', 'fixed')
                                .css('height', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', this.getBorderColor())
                                .appendTo(this.jHiddenBorders);

            this.jBorderRight = $('<div>')
                                .css('position', 'fixed')
                                .css('width', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', this.getBorderColor())
                                .appendTo(this.jHiddenBorders);


            this.jBorderBottom = $('<div>')
                                .css('position', 'fixed')
                                .css('height', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', this.getBorderColor())
                                .appendTo(this.jHiddenBorders);

            this.jBorderLeft = $('<div>')
                                .css('position', 'fixed')
                                .css('width', '1px')
                                .css('fontSize', '1px')
                                .css('overflow', 'hidden')
                                .css('zIndex', '9990')
                                .css('background', this.getBorderColor())
                                .appendTo(this.jHiddenBorders);

            return this;

        },


        /**
         * Цвет рамки в режиме редактирования, берутся настройки CmsToolbar
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


        /**
         * Настройка блока области
         * @returns {sx.classes.toolbar.EditViewBlock}
         */
        adjustWrapper : function()
        {
            var minHeight   = this.get('minHeight', 12);
            var height      = this.jWrapper.height();
            if (height <= minHeight)
            {
                this.jWrapper.addClass("skeeks-cms-toolbar-edit-view-block-empty");
                this.jWrapper.css('height', minHeight + 'px');
            } else
            {
                this.jWrapper.removeClass("skeeks-cms-toolbar-edit-view-block-empty");
            }

            return this;
        },

        /**
         * Позиционирование и настройка невидимого контейнера
         *
         * @returns {sx.classes.toolbar.EditViewBlock}
         */
        adjustHidden : function()
        {
            this._adjustBorders()._adjustControlls();
            return this;
        },


        /**
         * Настройка кнопок
         * @returns {sx.classes.toolbar.EditViewBlock}
         * @private
         */
        _adjustControlls : function()
        {
            var top     = this.jWrapper.offset().top - $(window).scrollTop();
            var left    = this.jWrapper.offset().left;

            var fromTop = top - 24;
            if (fromTop <= 120)
            {
                fromTop = this.jWrapper.height() + top;
            }

            this.jHiddenControllBtns
                .css('top', fromTop)
                .css('left', left)
            ;

            return this;
        },

        /**
         * Настройка бордеров
         * @returns {sx.classes.toolbar.EditViewBlock}
         * @private
         */
        _adjustBorders: function()
        {
            var height  = this.jWrapper.height();
            var width   = this.jWrapper.width();
            var top     = this.jWrapper.offset().top - $(window).scrollTop();
            var left    = this.jWrapper.offset().left;

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

            return this;
        }
    });

})(sx, sx.$, sx._);
/*!
 * Общение между фреймами.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 04.03.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes', sx);
    /**
     * @type {*|void|Function}
     * @private
     */
    sx.classes._IframeManager  = sx.classes.Component.extend({

        _init: function()
        {
            //Дочерние iframes
            this.childIframes                = [];

            //Текущее окно является фреймом?
            this.isFrame                =   false;
            //Родительский обеъкт фрейм менеджера
            this.parentIframeManager    =   null;

            this.parentFrameElement           =   window.frameElement;

            if (window.parent.window != window.window)
            {
                this.parentFrameElement     = window.frameElement;
                this.isFrame                = true;
            }

            if (window.parent.window.sx.IframeManager)
            {
                this.parentIframeManager = window.parent.window.sx.IframeManager;
            }

            //Если есть родительский объект фрейм менеджера, и это окно является фреймом, нужно найти объект родительского фрейма, породившее этот фрейм ))
            this.parentIframe = null;
            if (this.parentIframeManager && this.isFrame)
            {
                if (window.frameElement.getAttribute('id'))
                {
                    this.parentIframe = this.parentIframeManager.findIframeById(window.frameElement.getAttribute('id'));
                }
            }


            if (this.parentIframe)
            {
                //Сообщим родительскому окну что мы построены
                this.parentIframe.trigger('initChildIframe', this);
            }
        },

        _onDomReady: function()
        {
            var self = this;

            if (this.parentIframe)
            {
                //Сообщим родительскому окну что мы построены
                this.parentIframe.trigger('domReady', this);

                /**
                 * Если родительский фрейм настроен так что будет слушать высоту текущего окна
                 * Вешаем таймер и постоянно сообщаем новую высоту
                 */
                if (this.parentIframe.isAutoHeight())
                {
                    this.listenHeight();
                }
            }
        },



        /**
         * Регистраация дочернего фрейма
         *
         * @param Iframe
         * @returns {sx.classes._IframeManager}
         */
        registerIframe: function(Iframe)
        {
            if (!(Iframe instanceof sx.classes.Iframe))
            {
                throw new Error("object must be instance of 'sx.classes._Iframe'");
            }

            this.childIframes.push(Iframe);

            return this;
        },
        /**
         * Найти дочерний фрейм по ID
         *
         * @param id
         * @returns {sx.classes._Iframe}
         */
        findIframeById: function(id)
        {
            if (typeof id != "string")
            {
                throw new Error("id must be string");
            }

            return _.find(this.childIframes, function(Iframe)
            {
                return Iframe.get("id") == id;
            });
        },


        /**
         * Слушаем изминения высоты окна, и сообщаем об этом.
         */
        listenHeight : function()
        {
            var self = this;
            this._actualHeight = 0;

            setInterval(function()
            {
                self._listenHeight();
            }, this.parentIframe.get("heightTimer", 500));
        },

        /**
         * @private
         */
        _listenHeight: function()
        {
            var actualHeight    = 0;

            if (this.parentIframe.get("heightSelector"))
            {
                actualHeight    = $( this.parentIframe.get("heightSelector") ).height();
            } else
            {
                actualHeight    = $(window).height();
            }
            //var actualHeight    = $(".sx-panel-content").height();


            //Если высота изменилась
            if (Number(this._actualHeight) != Number(actualHeight))
            {
                this._actualHeight = actualHeight;
                this.trigger("changeHeight", actualHeight);
            }
        },

        /**
         * @returns {Window}
         */
        getWindow: function()
        {
            return window;
        },

        /**
         * @returns {*}
         */
        getSx: function()
        {
            return sx;
        }
    });

    sx.classes.IframeManager    = sx.classes._IframeManager.extend({});
    sx.IframeManager            = new sx.classes.IframeManager();


    /**
     * new sx.classes._Iframe('id', {
     *      'autoHeight' : true,                    //автоматически менять высоту фрейма
     *      'heightSelector' : '.sx-panel-test',    //высоты какого контейнера слушать
     *      'heightTimer' : 500                     //Частота обновления таймера,
     *      'minHeight' : 800                       //Минимальная высот фрейма в пикселях
     * })
     * @type {*|void|Function}
     * @private
     */
    sx.classes._Iframe = sx.classes.Component.extend({
        /**
         * Установка необходимых данных
         * @param form
         * @param opts
         */
        construct: function(id, opts)
        {
            opts = opts || {};

            if (typeof id != "string")
            {
                throw new Error("id must be string");
            }

            opts['id'] = id;

            this.applyParentMethod(sx.classes.Component, 'construct', [opts]);
        },


        _init: function()
        {
            var self                        = this;
            //Построен дочерний фрейм?
            this.childIframeManager         = false;
            this.ready                      = false;
            this.sx                         = null;

            sx.IframeManager.registerIframe(this);

            this.bind("domReady", function(e, childIframeManager)
            {
                self.ready = true;
                self.trigger('ready', this);
            });

            this.bind("initChildIframe", function(e, childIframeManager)
            {
                self.childIframeManager = childIframeManager;
                self.sx                 = childIframeManager.getSx();

                self.childIframeManager.bind('changeHeight', function(e, newHeight)
                {
                    self.setHeight(newHeight);
                });
            });

        },

        /**
         * @param callback
         * @returns {*}
         */
        onReady: function(callback)
        {
            if (this.ready == 1)
            {
                callback("", this);
            } else
            {
                this.bind("ready", callback);
            }
            return this;
        },

        /**
         * @param callback
         * @returns {sx.classes._Iframe}
         */
        onSxReady: function(callback)
        {
            var self = this;
            this.onReady(function()
            {
                self.sx.onReady(callback);
            });

            return this;
        },


        /**
         *
         * @private
         */
        _onDomReady: function()
        {
            //Включение или отключение скроллинга
            this.JqueryIframe().attr("scrolling", this.get("scrolling", "no"));
        },

        /**
         *
         * @returns {*|HTMLElement}
         * @constructor
         */
        JqueryIframe: function()
        {
            return $("#" + this.get('id'));
        },


        /**
         * Автоматически езменять высоту, по размеру дочернего контейнера?
         *
         * @returns {boolean|*|Boolean}
         */
        isAutoHeight: function()
        {
            return Boolean(this.get('autoHeight'));
        },

        /**
         * Установка новой высоты фрейма
         *
         * @param newHeight
         * @returns {sx.classes._Iframe}
         */
        setHeight: function(newHeight)
        {
            var self = this;

            newHeight = Number(newHeight);

            if (Number(this.get('minHeight', 200)) > newHeight)
            {
                newHeight = Number(this.get('minHeight', 200));
            }

            this.onDomReady(function()
            {
                self.JqueryIframe().attr("height", self.get("height", newHeight));
            });

            return this;
        }
    });

    sx.classes.Iframe    = sx.classes._Iframe.extend({});

})(sx, sx.$, sx._);
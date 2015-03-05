/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.02.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes', sx);

    /**
     * TODO: нужно порефакторить
     * Основной класс для управления админкой - объект админки
     * @type {extend|*|Function|extend|void|extend}
     */
    sx.classes.Admin = sx.classes.Component.extend({

        _init: function()
        {
            this._navigation            = new sx.classes.MainNav(this.get("navigation"));
            this.Menu                   = new sx.classes.AdminMenu(this.get("menu"));
            this.ajaxLoader             = new sx.classes.AjaxLoader();

            this.onWindowReadyBlocker   = null;


            this.readyWindowTrigger = false;
            var self = this;
            //Если window не будет готово через 200 мс, покажем загрузку.
            _.delay(function()
            {
                if (self.readyWindowTrigger === false)
                {
                    self.onDomReady(function()
                    {
                        self.onWindowReadyBlocker   = sx.block('.sx-unblock-onWindowReady');
                    });
                }

            }, 200);
        },

        _onWindowReady: function()
        {
            var self = this;
            this.readyWindowTrigger = true;

            _.defer(function()
            {
                $(".sx-show-onWindowReady").slideDown(300, function()
                {});
            });

            _.delay(function()
            {
                if (self.onWindowReadyBlocker)
                {
                    self.onWindowReadyBlocker.unblock();
                }

            }, 200);

        },

        _onDomReady: function()
        {
            var self = this;

            this._initBootstrap();

            //Отключение пустых ссылок
            if (this.get("disableCetainLink", true) === true)
            {
                $('a[href^=#]').click(function (e)
                {
                    e.preventDefault()
                })
            }

            this._initWindowCloseButtons();


            $(".sx-sidebar .scrollbar-macosx").scrollbar();

            _.delay(function()
            {
                $(".sx-panel").fadeIn();
            }, 100);
        },

        /**
         * Читаем bootstrap документацию если нужно
         * @private
         */
        _initBootstrap: function()
        {
            //------------- Bootstrap tooltips -------------//
            $("[data-sx-widget=tooltip]").tooltip ({});
            $("[data-sx-widget=tooltip-r]").tooltip ({placement: 'right', container: 'body'});
            $("[data-sx-widget=tooltip-b]").tooltip ({placement: 'bottom', container: 'body'});
            $("[data-sx-widget=tooltip-l]").tooltip ({placement: 'left', container: 'body'});
            //--------------- Popovers ------------------//
            //using data-placement trigger
            $("[data-sx-widget=popover]")
                .popover()
                .click(function(e)
                {
                    e.preventDefault()
                });
        },

        /**
         * Кнопки с классом sx-admin-windowCloseButton, будут показываться только в том случае, если есть окно window.opener
         * Например в редакторе сущностей
         * @returns {sx.classes.admin.Admin}
         * @private
         */
        _initWindowCloseButtons: function()
        {
            if (window.opener)
            {
                $(".sx-admin-windowCloseButton").show();
            }

            $(".sx-admin-windowCloseButton").on("click", "body", function()
            {
                return false;
            });

            return this;
        }
    });

    /**
     * Запускаем глобальный класс админки
     * @type {Admin}
     */
    sx.app = new sx.classes.Admin({
        //Отключение ссылок с href="#"
        disableCetainLink: false,
        globalAjaxLoader: true,
        menu: {}
    });

})(sx, sx.$, sx._);

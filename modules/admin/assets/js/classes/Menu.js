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
     * TODO: переделать хранение состояние, не очень хорошо хранить в cookie. Меню прыгает. Для начала лучше чем ничего...
     * Левое меню админки
     * @type {void|*|Function}
     */
    sx.classes.AdminMenu = sx.classes.Component.extend({
        _init: function()
        {
            this.getCookieManager().setNamespace('admin-menu');
        },

        _onDomReady: function()
        {
            var self = this;

            if (this.get("toggles"))
            {
                _.each(this.get("toggles"), function(toggle, key)
                {
                    self.registerToggle(toggle);
                });
            }

            if (this.getSavedInstance() == "closed")
            {
                this.close();
            }

            /*$('.sidebar-menu').on('click', function()
            {
                sx.notify.info('Скоро можно будет открывать и закрывать этот блок');
            });*/
        },

        /**
         * @returns {sx.classes.AdminMenu}
         */
        registerToggle: function(selector)
        {
            var self = this;

            this.onDomReady(function()
            {
                $(selector).on('click', function()
                {
                    self.toggleTrigger();
                });
            });

            return this;
        },

        /**
         * @returns {sx.classes.AdminMenu}
         */
        toggleTrigger: function()
        {
            this.trigger('toggle', this);

            if (this.isOpened())
            {
                this.close();
            } else
            {
                this.open();
            }

            return this;
        },

        /**
         * Меню открыто?
         * @returns {boolean}
         */
        isOpened: function()
        {
            return Boolean( !$("body").hasClass(this.get("hidden-class", "sidebar-hidden")) );
        },

        /**
         * Закрыть меню
         * @returns {sx.classes.AdminMenu}
         */
        close: function()
        {
            var self = this;

            this.onDomReady(function()
            {
                self.trigger('close', this);
                $("body").addClass(self.get("hidden-class", "sidebar-hidden"));
                self.saveInstance();
            });

            return this;
        },

        /**
         * Открыть
         * @returns {sx.classes.AdminMenu}
         */
        open: function()
        {
            var self = this;

            this.onDomReady(function()
            {
                self.trigger('open', this);
                $("body").removeClass(self.get("hidden-class", "sidebar-hidden"));
                self.saveInstance();
            });

            return this;
        },

        /**
         * Сохранение состояния меню. Заюзаем пока cookie
         * @returns {sx.classes.AdminMenu}
         */
        saveInstance: function()
        {
            if (this.isOpened())
            {
                this.getCookieManager().set('instance', 'opened');
            } else
            {
                this.getCookieManager().set('instance', 'closed');
            }

            return this;
        },

        /**
         * Получить сохраненное состояние
         * @returns {string}
         */
        getSavedInstance: function()
        {
            return String( this.getCookieManager().get('instance')) ;
        },

        /**
         * @returns {*|void|*|Function}
         */
        getBlocker: function()
        {
            if (!this.Blocker)
            {
                this.Blocker = new sx.classes.Blocker('.sx-sidebar');
            }

            return this.Blocker;
        },

        /**
         * @returns {sx.classes.AdminMenu}
         */
        block: function()
        {
            this.getBlocker().block();
            return this;
        },

        /**
         * @returns {sx.classes.AdminMenu}
         */
        unblock: function()
        {
            this.getBlocker().unblock();
            return this;
        }
    });

})(sx, sx.$, sx._);
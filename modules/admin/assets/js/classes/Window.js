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
     * Настройка блокировщика для админки по умолчанию. Глобальное перекрытие
     * @type {void|*|Function}
     */
    sx.classes.Window  = sx.classes._Window.extend({

        /**
         * @returns {Window}
         */
        open: function()
        {
            var self = this;
            this.trigger('beforeOpen');
            //строка параметров, собираем из массива
            var paramsSting = "";
            if (this.getOpts())
            {
                _.each(this.getOpts(), function(value, key)
                {
                    if (paramsSting)
                    {
                        paramsSting = paramsSting + ',';
                    }
                    paramsSting = paramsSting + String(key) + "=" + String(value);
                });
            }

            this.onDomReady(function()
            {
                $("<a>", {
                    'style' : 'display: none;',
                    'href' : self._src,
                    'data-fancybox-type' : 'iframe',
                }).appendTo('body').fancybox({
                    'iframe' : {
                        scrolling : 'auto',
                        preload   : true
                    },
                    'height' : 'html',
                    'type' : 'iframe',
                    'fitToView' : true,
                    'autoSize' : true,
                    'autoHeight' : true,
                    'afterClose' : function()
                    {
                        self.trigger('close');
                    },
                    'width'		: '80%',
                }).click();
            });

            /*this._openedWindow = window.open(this._src, this._name, paramsSting);
            if (!this._openedWindow)
            {
                this.trigger('error', {
                    'message': 'Браузер блокирует окно, необходимо его разрешить'
                });

                return this;
            }

            this.trigger('afterOpen');

            var timer = setInterval(function()
            {
                if(self._openedWindow.closed)
                {
                    clearInterval(timer);
                    self.trigger('close');
                }
            }, 1000);*/

            return this;
        },
    });

})(sx, sx.$, sx._);
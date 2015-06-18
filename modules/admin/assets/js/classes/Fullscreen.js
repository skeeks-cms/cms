/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.06.2015
 */
(function(sx, $, _)
{

    sx.createNamespace('classes', sx);
    /**
     * Настройка блокировщика для админки по умолчанию. Глобальное перекрытие
     * @type {void|*|Function}
     */
    sx.classes.Fullscreen  = sx.classes.BlockerJqueyUi.extend({

        _init: function()
        {
            this.run();
        },

        run: function()
        {
            var self = this;

            this.onDomReady(function()
            {
                self._run();
                return this;
            });

            return this;
        },

        _run: function()
        {
            // If browser is IE we need to pass the fullsreen plugin the 'html' selector
            // rather than the 'body' selector. Fixes a fullscreen overflow bug
            var selector = $('html');

            var ua = window.navigator.userAgent;
            var old_ie = ua.indexOf('MSIE ');
            var new_ie = ua.indexOf('Trident/');
            if ((old_ie > -1) || (new_ie > -1))
            {
                selector = $('body');
            }

            // Fullscreen Functionality
            var screenCheck = $.fullscreen.isNativelySupported();

              // Check for fullscreen browser support
            if (screenCheck)
            {
                if ($.fullscreen.isFullScreen())
                {
                    $.fullscreen.exit();
                }
                else
                {
                    selector.fullscreen({
                        overflow: 'auto'
                    });
                }
            } else
            {
                sx.notify.error('Your browser does not support fullscreen mode.');
            }
        }
    });

})(sx, sx.$, sx._);
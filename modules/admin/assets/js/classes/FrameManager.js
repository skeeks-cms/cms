/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 03.03.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes', sx);
    /**
     * Настройка блокировщика для админки по умолчанию. Глобальное перекрытие
     * @type {void|*|Function}
     */
    sx.classes._FrameManager  = sx.classes.Component.extend({

        _init: function()
        {},

        getCurrentHeight : function()
        {
            myHeight = 0;

            if( typeof( window.innerWidth ) == 'number' ) {
            myHeight = window.innerHeight;
            } else if( document.documentElement && document.documentElement.clientHeight ) {
            myHeight = document.documentElement.clientHeight;
            } else if( document.body && document.body.clientHeight ) {
            myHeight = document.body.clientHeight;
            }

            return myHeight;
        },

        publishHeight : function()
        {
            if (this.FrameId == '') return;
            // если нет jQuery - воспользуемся решениями для  определения размеров из яндекса
            if(typeof jQuery === "undefined") {
                var actualHeight = (document.body.scrollHeight > document.body.offsetHeight)?document.body.scrollHeight:document.body.offsetHeight;
                var currentHeight = this.getCurrentHeight();
            } else {
                var actualHeight = $("body").height();
                var currentHeight = $(window).height();
            }

            if(Math.abs(actualHeight - currentHeight) > 20)
            {
                pm({
                  target: window.parent,
                  type: this.FrameId,
                  data: {height:actualHeight, id:this.FrameId}
                });
            }
        }
    });

    sx.classes.FrameManager = sx.classes._FrameManager.extend({});


    sx.classes.Frame = sx.classes.Component.extend({

    });

    sx.FrameManager = new sx.classes.FrameManager();

})(sx, sx.$, sx._);
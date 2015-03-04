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
        {
            this.isFrame                =   false;
            this.parentFrameManager     =   null;
            this.frameElement           =   window.frameElement;
            this.window                 =   window;

            if (window.parent.window != window.window)
            {
                this.frameElement = window.frameElement;
                this.isFrame = true;
            }

            if (window.parent.window.sx.FrameManager)
            {
                this.parentFrameManager = window.parent.window.sx.FrameManager;
            }

        },

        _onDomReady: function()
        {
            var self = this;

            if (this.parentFrameManager && this.isFrame)
            {
                setInterval(function()
                {
                    self.pushHeight();
                }, 1000);
            }
        },


        _getCurrentHeight : function()
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

        pushHeight : function()
        {
            if (this.FrameId == '') return;
            // если нет jQuery - воспользуемся решениями для  определения размеров из яндекса
            if(typeof jQuery === "undefined") {
                var actualHeight = (document.body.scrollHeight > document.body.offsetHeight)?document.body.scrollHeight:document.body.offsetHeight;
                var currentHeight = this._getCurrentHeight();
            } else {
                var actualHeight = $(".sx-panel-content").height();
                var currentHeight = $(window).height();
            }


            this.parentFrameManager.updateFrameHeightElement(this, actualHeight);

            if(Math.abs(actualHeight - currentHeight) > 20)
            {

            }
        },

        updateFrameHeightElement: function(frameManager, actualHeight)
        {
            frameManager.frameElement.setAttribute("height", actualHeight + 2);
            frameManager.frameElement.setAttribute("scrolling", "no");
        }

    });

    sx.classes.FrameManager = sx.classes._FrameManager.extend({});


    sx.classes.Frame = sx.classes.Component.extend({

    });

    sx.FrameManager = new sx.classes.FrameManager();

})(sx, sx.$, sx._);
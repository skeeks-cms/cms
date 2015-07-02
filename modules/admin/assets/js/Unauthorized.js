/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.07.2015
 */
(function(sx, $, _, window)
{
    sx.createNamespace('classes', sx);

    sx.classes.AppUnAuthorized = sx.classes.Component.extend({

        _init: function()
        {
            this.blocker    = new sx.classes.Blocker();
        },

        _onDomReady: function()
        {
            var $blockerLoader
            var self = this;
            this.blockerHtml = sx.block('html', {
                message: "<div style='padding: 10px;'><h2><img src='" + this.get('blockerLoader') + "'/> Загрузка...</h2></div>",
                css: {
                    "border-radius": "6px",
                    "border-width": "3px",
                    "border-color": "rgba(32, 168, 216, 0.25)",
                    "box-shadow": "0 11px 51px 9px rgba(0,0,0,.55)"
                }
            });

            this.PanelBlocker = new sx.classes.Blocker('.sx-panel', {
                message: "<div style='padding: 10px;'><h2><img src='" + this.get('blockerLoader') + "'/> Загрузка...</h2></div>",
                css: {
                    "border-radius": "6px",
                    "border-width": "1px",
                    "border-color": "rgba(32, 168, 216, 0.25)",
                    "box-shadow": "0 11px 51px 9px rgba(0,0,0,.55)"
                }
            });

         // Init CanvasBG and pass target starting location

        },

        _onWindowReady: function()
        {
            var self = this;
            $("body").addClass('sx-styled');

            this.blockerHtml.unblock();

            _.delay(function()
            {
                /*console.log(window.top.sx);
                if (!window.top.sx)
                {

                }*/
                $('.navbar, .sx-admin-footer').addClass('op-05').fadeIn('slow');

                CanvasBG.init({
                  Loc: {
                    x: window.innerWidth / 2.1,
                    y: window.innerHeight / 2.2
                  },
                });

            }, 2000);




            _.delay(function()
            {
                $('.sx-windowReady-fadeIn').fadeIn();
            }, 500);


        },

        hideHeader: function()
        {
            $(".navbar").fadeOut();
            return this;
        },

        hideFooter: function()
        {
            $(".sx-admin-footer").fadeOut();
            return this;
        },

        triggerBeforeReddirect: function()
        {
            var self = this;
            this.hideHeader().hideFooter();
            $('.sx-content-block').fadeOut();
            self.blockerHtml.block();
        }
    });

})(sx, sx.$, sx._, window);
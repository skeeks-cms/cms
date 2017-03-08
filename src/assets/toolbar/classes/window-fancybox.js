/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.04.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.toolbar', sx);

    /**
     * Красивые окошки fancybox
     */
    sx.classes.toolbar.Window  = sx.classes._Window.extend({
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
                var options = _.extend({
                    'afterClose' : function()
                    {
                        self.trigger('close');
                    },
                    'height'	: '100%',
                    'autoSize'  : false,
                    'width'		: '100%'
                }, self.toArray());

                $("<a>", {
                    'style' : 'display: none;',
                    'href' : self._src,
                    'data-fancybox-type' : 'iframe',
                }).appendTo('body').fancybox(options).click();
            });

            return this;
        }
    });
})(sx, sx.$, sx._);
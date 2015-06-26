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
    sx.classes.Blocker  = sx.classes.BlockerJqueyUi.extend({

        _init: function()
        {
            this.applyParentMethod(sx.classes.BlockerJqueyUi, '_init', []);

            var self = this;
            sx.onReady(function()
            {
                console.log(sx.config.get("Blocker"));
                var BlockerData = sx.config.get("Blocker");
                console.log(BlockerData);
                self.imageLoader = String(BlockerData.circulareBlue);

                self.defaultOpts({
                    message: "<div style='padding: 5px;'><img src='" + self.imageLoader + "' />Подождите...</div>",
                    css: {
                        border: '1px solid #108acb',
                        padding: '10px;',
                    }
                });
            });

        }
    });

})(sx, sx.$, sx._);
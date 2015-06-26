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
            this.imageLoader = '';

            if (sx.App)
            {
                this.imageLoader = sx.App.get('BlockerImageLoader');
            }

            this.defaultOpts({
                message: "<div style='padding: 5px;'><img src='" + this.imageLoader + "' /> Подождите...</div>",
                css: {
                    border: '1px solid #108acb',
                    padding: '10px;',
                }
            });

            this.applyParentMethod(sx.classes.BlockerJqueyUi, '_init', []);
        },
    });

})(sx, sx.$, sx._);
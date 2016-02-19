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
     *
     */
    sx.classes.T  = sx.classes.Component.extend({

        _init: function()
        {
            this.get('db');
        },

        read: function(message)
        {

        },

        /**
         * @returns {*}
         */
        getDb: function()
        {
            return this.get('db');
        }
    });

})(sx, sx.$, sx._);
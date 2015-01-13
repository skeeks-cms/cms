/*!
 *
 * Skeeks cms application
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.10.2014
 * @since 1.0.0
 */
(function(sx, $, _)
{
    sx.createNamespace('classes', sx);

    sx.classes.HiddenCaptcha = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            var w_str = this._generateRand(15);
            $("[name=" + this.get('name1') + "]").val(w_str);
            $("[name=" + this.get('name2') + "]").val(this._changeString(w_str));
        },

        _onWindowReady: function()
        {},

        /**
         * Р“РµРЅРµСЂРёС‚ СЃР»СѓС‡Р°Р№РЅСѓСЋ СЃС‚СЂРѕРєСѓ
         * @param string_length
         * @return {String}
         * @private
         */
        _generateRand: function(string_length)
        {
            var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcde...";
            var r_str = '';
            for (var i=0; i<string_length; i++)
            {
                var rnum = Math.floor(Math.random() * chars.length);
                r_str += chars.substring(rnum,rnum+1);
            }
            return r_str;
        },


        /**
         * @param str
         * @returns {string}
         * @private
         */
        _changeString: function(str)
        {
            var new_line = "";
            new_line += sx.helpers.String.substr(str, 4, 5);
            new_line += sx.helpers.String.substr(str, 1, 4);
            new_line += sx.helpers.String.substr(str, 1, 2);
            new_line += sx.helpers.String.substr(str, 5, 6);
            return new_line;
        }
    });

})(sx, sx.$, sx._);

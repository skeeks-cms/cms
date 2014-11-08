/*!
 *
 *
 *
 * @date 06.11.2014
 * @copyright skeeks.com
 * @author Semenov Alexander <semenov@skeeks.com>
 */


(function(sx, $, _)
{
    sx.createNamespace('classes.app', sx);

    sx.classes.app.controllerAction = sx.classes.Component.extend({

        _init: function()
        {
            this._window = new sx.classes.Window(this.get('url'), this.get('newWindowName'));
            this._window.setCenterOptions().disableResize().disableLocation();
            console.log(localStorage.getItem('test1'));


            this._window.bind('close', function()
            {
                window.location.reload();

            });


            this._window.bind('error', function(e, data)
            {
                alert(data.message);
            });
        },

        /**
         * @returns {sx.classes.app.controllerAction}
         */
        go: function()
        {
            if (this.get('isOpenNewWindow') && window.name != this.get('newWindowName'))
            {
                this._window.open().focus();
                return this;
            }

            location.href = this.get('url');
        },

        _onDomReady: function()
        {},

        _onWindowReady: function()
        {},

        /**
         * @returns {sx.classes.Window|*}
         */
        getWindow: function()
        {
            return this._window;
        }
    });

})(sx, sx.$, sx._);
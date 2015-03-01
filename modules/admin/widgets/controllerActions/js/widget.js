/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.02.2015
 */
(function(sx, $, _)
{
    sx.createNamespace('classes.app', sx);

    sx.classes.app.controllerAction = sx.classes.Component.extend({

        _init: function()
        {
            var self = this;

            this._window = new sx.classes.Window(this.get('url'), this.get('newWindowName'));
            this._window.setCenterOptions().disableResize().disableLocation();

            this._window.bind('close', function()
            {
                self.updateSuccess();
            });

            this._window.bind('error', function(e, data)
            {
                sx.notify.error(data.message + '. Обратитесь к разарботчикам');
            });
        },

        /**
         * @returns {sx.classes.app.controllerAction}
         */
        go: function()
        {
            var self = this;

            if (this.get("confirm"))
            {
                sx.confirm(this.get("confirm"), {
                    'no' : function()
                    {},
                    'yes' : function()
                    {
                        self._go();
                    }
                }).show();
            } else
            {
                return this._go();
            }
        },

        _go: function()
        {
            var self = this;
            //Надо делать ajax запрос
            if (this.get("request") == 'ajax')
            {
                if (this.get("method", "post") == "post")
                {
                    this.ajax = sx.ajax.preparePostQuery(this.get('url'));
                } else
                {
                    this.ajax = sx.ajax.prepareGetQuery(this.get('url'));
                }

                this.ajax.onSuccess(function(e, data)
                {
                    if (data.response.success)
                    {
                        sx.notify.success(data.response.message);
                        self.updateSuccess();
                    } else
                    {
                        sx.notify.error(data.response.message);
                    }
                });

                this.ajax.onError(function(e, response)
                {
                    sx.notify.error(response.errorThrown + '. Обратитесь к разарботчикам');
                });

                this.ajax.execute();
                return this;
            }

            if (this.get('isOpenNewWindow') && window.name != this.get('newWindowName'))
            {
                this._window.open().focus();
                return this;
            }

            location.href = this.get('url');
        },

        updateSuccess: function()
        {
            if (this.get('pjax-id'))
            {
                $.pjax.reload('#' + this.get('pjax-id'), {});
            } else
            {
                window.location.reload();
            }
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
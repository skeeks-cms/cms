/*!
 *
 *
 *
 * @date 17.10.2014
 * @copyright skeeks.com
 * @author Semenov Alexander <semenov@skeeks.com>
 */

(function(sx, $, _)
{
    sx.createNamespace('classes', sx);

    sx.classes.SelectUploadFile = sx.classes.Component.extend({

        _init: function()
        {

        },

        _onDomReady: function()
        {
            var self = this;

            this._$wrapper              = $("#" + this.get("id"));
            this._$valueElement         = $("input[type='hidden']", this._$wrapper);
            this._$imgElement           = $("img", this._$wrapper);
            this._$selectFileElement    = $(".action-select", this._$wrapper);
            this._$deleteFileElement    = $(".action-delete", this._$wrapper);


            this._$imgElement.on("click", function()
            {
                var params = "menubar=no,location=yes,resizable=yes,scrollbars=yes,status=no,width=800,height=600";
                window.open(self.get("windowUrl"), self.get("id"), params).focus();

                return false;
            });

            this._$selectFileElement.on("click", function()
            {
                var params = "menubar=no,location=yes,resizable=yes,scrollbars=yes,status=no,width=800,height=600";
                window.open(self.get("windowUrl"), self.get("id"), params).focus();
                return false;
            });

            this._$deleteFileElement.on("click", function()
            {
                self._$imgElement.attr("src", "");
                self._$valueElement.val("");
                return false;
            });
        },

        _onWindowReady: function()
        {}
    });
})(sx, sx.$, sx._);
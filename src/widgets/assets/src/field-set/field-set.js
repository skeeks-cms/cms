/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
(function (sx, $, _) {
    sx.classes.ActiveFormFieldSet = sx.classes.Component.extend({

        _init: function () {

        },

        getJForm: function() {
            return $("#" + this.get("form-id"));
        },

        _onDomReady: function () {
            var self = this;

            $(".sx-form-fieldset-title", this.getJForm()).on("click", function() {
                var jFieldSet = $(this).closest(".sx-form-fieldset");
                var jFieldContent = $(".sx-form-fieldset-content", jFieldSet);
                if (jFieldSet.hasClass("sx-field-set-hidden")) {
                    jFieldSet.removeClass("sx-field-set-hidden");
                    jFieldContent.slideDown(1000);
                    self.updateUrl();
                } else {
                    jFieldSet.addClass("sx-field-set-hidden");
                    jFieldContent.slideUp(1000);
                    self.updateUrl();
                }
            });
        },

        updateUrl: function () {
            var u = new Url;
            var o = [];
            $(".sx-form-fieldset", this.getJForm()).each(function () {
                if (!$(this).hasClass("sx-field-set-hidden")) {
                    o.push($(this).attr('id'));
                }
            });

            if (o.length > 0) {
                var paramName = this.get("form-id");
                u.query[paramName + "[]"] = o;
            }

            this.getJForm().attr("action", u.toString());
            window.history.replaceState("", "", u.toString());
        },

        _onWindowReady: function () {
        }
    });
})(sx, sx.$, sx._);
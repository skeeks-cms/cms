/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
(function (sx, $, _) {
    sx.classes.Adult = sx.classes.Component.extend({

        _init: function () {
        },

        _onDomReady: function () {
            var self = this;

            $("body").on("click", ".sx-adult-block", function () {
                $("#sx-adult-modal").modal('show');
                return false;
            });
            $("body").on("click", ".sx-btn-no", function () {
                $("#sx-adult-modal").modal('hide');
                return false;
            });
            $("body").on("click", ".sx-btn-yes", function () {
                $("#sx-adult-modal").modal('hide');
                $(".sx-adult-block").hide();
                $(".sx-adult").removeClass("sx-adult");
                var ajaxQuery = sx.ajax.preparePostQuery(self.get("backend"), {
                    'is_allow' : true
                });
                ajaxQuery.execute();
                return false;
            });

            $(".sx-adult-block").each(function() {
                if ($(this).width() < 150) {
                    $(this).addClass("sx-mini")
                }
            });
        },

    });
})(sx, sx.$, sx._);

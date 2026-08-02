(function (sx, $) {
    "use strict";

    var phoneSelector = "[data-sx-phone-mask]";

    function initPhoneMasks(context) {
        var $context = $(context || document);
        var $inputs = $context.find(phoneSelector).add($context.filter(phoneSelector));

        if (!$.fn.mask) {
            return;
        }

        $inputs.each(function () {
            var $input = $(this);

            if ($input.attr("data-sx-phone-mask-ready") === "true") {
                return;
            }

            $input.mask($input.attr("data-sx-phone-mask"));
            $input.attr("data-sx-phone-mask-ready", "true");
        });
    }

    function initDocument() {
        initPhoneMasks(document);
    }

    if (document.readyState === "loading") {
        $(initDocument);
    } else {
        initDocument();
    }

    $(document).on("pjax:end", function (event) {
        initPhoneMasks(event.target);
    });
})(sx, sx.$);

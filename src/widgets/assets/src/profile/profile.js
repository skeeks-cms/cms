(function (sx, $) {
    "use strict";

    var controlSelector = ".sx-password-control";
    var inputSelector = "[data-sx-password-input]";

    function setPasswordVisible($control, isVisible) {
        var $input = $control.find(inputSelector);
        var $toggle = $control.find("[data-sx-password-toggle]");
        var $icon = $toggle.find("i");

        $input.attr("type", isVisible ? "text" : "password");
        $toggle
            .attr("aria-pressed", isVisible ? "true" : "false")
            .attr("aria-label", isVisible ? "Скрыть пароль" : "Показать пароль");
        $icon
            .toggleClass("fa-eye", !isVisible)
            .toggleClass("fa-eye-slash", isVisible);
    }

    function randomPassword(length) {
        var alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*()_+-=";
        var values = new Uint32Array(length);

        if (window.crypto && window.crypto.getRandomValues) {
            window.crypto.getRandomValues(values);
        } else {
            for (var index = 0; index < length; index += 1) {
                values[index] = Math.floor(Math.random() * 0x100000000);
            }
        }

        return Array.prototype.map.call(values, function (value) {
            return alphabet.charAt(value % alphabet.length);
        }).join("");
    }

    $(document)
        .on("click", "[data-sx-password-toggle]", function () {
            var $toggle = $(this);
            var $control = $toggle.closest(controlSelector);
            var isVisible = $control.find(inputSelector).attr("type") === "text";

            setPasswordVisible($control, !isVisible);
        })
        .on("click", "[data-sx-password-generate]", function (event) {
            event.preventDefault();

            var $trigger = $(this);
            var $control = $trigger.closest(controlSelector);
            var length = parseInt($trigger.attr("data-sx-password-length"), 10) || 12;
            var $input = $control.find(inputSelector);

            $input
                .val(randomPassword(length))
                .trigger("input")
                .trigger("change");
            setPasswordVisible($control, true);
        });
})(sx, sx.$);

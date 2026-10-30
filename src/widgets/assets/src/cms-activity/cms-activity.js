(function (sx, $) {
    "use strict";

    if (!sx || !$) {
        return;
    }

    new sx.classes.LinkActivation(".sx-comment-wrapper");

    $("body")
        .off("click.sxCommentPinToggle")
        .on("click.sxCommentPinToggle", ".sx-comment-pin-toggle", function (event) {
            event.preventDefault();

            var button = $(this);
            var input = $("#" + button.data("input"));
            var isActive = !button.hasClass("is-active");

            button
                .toggleClass("is-active active", isActive)
                .attr("aria-pressed", isActive ? "true" : "false");
            input.val(isActive ? 1 : 0);

            if (event.originalEvent && event.originalEvent.detail > 0) {
                button.trigger("blur");
            }

            return false;
        });

    $("body")
        .off("click.sxLogShare")
        .on("click.sxLogShare", ".sx-log-share", function (event) {
            event.preventDefault();

            var button = $(this);
            var url = button.data("url");

            var markCopied = function () {
                button.addClass("is-copied is-active");
                if (sx.notify && sx.notify.info) {
                    sx.notify.info(button.data("success-message") || "Copied");
                }
                window.setTimeout(function () {
                    button.removeClass("is-copied is-active");
                }, 1300);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(markCopied);
                return false;
            }

            var textarea = document.createElement("textarea");
            textarea.value = url;
            textarea.setAttribute("readonly", "readonly");
            textarea.className = "sx-clipboard-buffer";
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand("copy");
                markCopied();
            } catch (ignore) {
                // The legacy clipboard fallback may be unavailable.
            }
            document.body.removeChild(textarea);

            return false;
        });

    window.sxScrollToLogComment = function () {
        if (!window.location.hash || window.location.hash.indexOf("#sx-log-") !== 0) {
            return;
        }

        var id = window.location.hash.replace("#", "");
        var target = document.getElementById(id);
        if (!target) {
            return;
        }

        var reduceMotion = window.matchMedia &&
            window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        target.scrollIntoView({
            behavior: reduceMotion ? "auto" : "smooth",
            block: "center"
        });

        var targetElement = $(target);
        targetElement.removeClass("sx-log-highlight");
        window.setTimeout(function () {
            targetElement.addClass("sx-log-highlight");
        }, reduceMotion ? 0 : 450);
        window.setTimeout(function () {
            targetElement.removeClass("sx-log-highlight");
        }, reduceMotion ? 1400 : 3200);
    };

    $(window)
        .off("hashchange.sxLogShare")
        .on("hashchange.sxLogShare", window.sxScrollToLogComment);
    $(document)
        .off("pjax:end.sxLogShare")
        .on("pjax:end.sxLogShare", function () {
            window.setTimeout(window.sxScrollToLogComment, 150);
        });
    window.setTimeout(window.sxScrollToLogComment, 350);

    $("body")
        .off("click.sxLogValueToggle")
        .on("click.sxLogValueToggle", ".sx-log-value-toggle", function (event) {
            event.preventDefault();

            var button = $(this);
            var wrapper = button.closest(".sx-log-value-collapsed");
            var isOpen = !wrapper.hasClass("is-open");

            wrapper.toggleClass("is-open", isOpen);
            button
                .attr("aria-expanded", isOpen ? "true" : "false")
                .text(isOpen ? button.data("close-label") : button.data("open-label"));

            return false;
        });

    // Images of other sites in old comments load only on request (CommentHtml).
    $("body")
        .off("click.sxLogImagesShow")
        .on("click.sxLogImagesShow", ".sx-log-images__show", function (event) {
            event.preventDefault();

            var notice = $(this).closest(".sx-log-images");
            notice.parent().find("img[data-sx-src]").each(function () {
                this.src = this.getAttribute("data-sx-src");
                this.removeAttribute("data-sx-src");
            });
            notice.remove();

            return false;
        });

    $("body")
        .off("click.sxLogPinToggle")
        .on("click.sxLogPinToggle", ".sx-log-pin-toggle", function (event) {
            event.preventDefault();

            var button = $(this);
            var data = {
                id: button.data("id"),
                is_pinned: button.data("value")
            };

            if (window.yii) {
                data[yii.getCsrfParam()] = yii.getCsrfToken();
            }

            button.addClass("is-loading").attr("aria-busy", "true");

            $.ajax({
                url: button.data("url"),
                type: "post",
                data: data,
                success: function (response) {
                    if (response && response.success === false) {
                        window.alert(response.message || button.data("error-message"));
                        button.removeClass("is-loading").removeAttr("aria-busy");
                        return;
                    }

                    var pjax = button.closest("[data-pjax-container]");
                    if (pjax.length && $.pjax) {
                        $.pjax.reload({container: "#" + pjax.attr("id"), async: false});
                    } else {
                        window.location.reload();
                    }
                },
                error: function () {
                    window.alert(button.data("error-message"));
                    button.removeClass("is-loading").removeAttr("aria-busy");
                }
            });

            return false;
        });
})(window.sx, window.sx && window.sx.$ ? window.sx.$ : window.jQuery);

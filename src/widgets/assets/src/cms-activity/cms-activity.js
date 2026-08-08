(function (sx, $) {
    "use strict";

    if (!sx || !$) {
        return;
    }

    var readThemeColor = function (name) {
        return window.getComputedStyle(document.documentElement)
            .getPropertyValue(name)
            .trim();
    };

    var syncEditorTheme = function (editor) {
        if (!editor || !editor.document) {
            return;
        }

        var body = editor.document.getBody();
        if (!body || !body.$) {
            return;
        }

        var surface = readThemeColor("--sx-color-surface");
        var text = readThemeColor("--sx-color-text");
        var muted = readThemeColor("--sx-color-text-muted");
        var accent = readThemeColor("--sx-color-accent");
        var frame = editor.container && editor.container.$
            ? editor.container.$.querySelector("iframe")
            : null;

        body.setStyles({
            "background-color": surface,
            "color": text
        });
        editor.document.getDocumentElement().setStyle(
            "color-scheme",
            document.documentElement.getAttribute("data-sx-theme") || "light"
        );

        if (frame) {
            frame.style.backgroundColor = surface;
        }

        Array.prototype.forEach.call(
            editor.document.$.querySelectorAll("a"),
            function (link) {
                link.style.color = accent;
            }
        );
        Array.prototype.forEach.call(
            editor.document.$.querySelectorAll("blockquote"),
            function (quote) {
                quote.style.color = muted;
            }
        );
    };

    var syncEditorsTheme = function () {
        if (!window.CKEDITOR || !window.CKEDITOR.instances) {
            return false;
        }

        Object.keys(window.CKEDITOR.instances).forEach(function (key) {
            syncEditorTheme(window.CKEDITOR.instances[key]);
        });

        return true;
    };

    var ckEditorEventsBound = false;
    var bindCkEditorTheme = function () {
        if (!window.CKEDITOR) {
            return false;
        }

        if (!ckEditorEventsBound) {
            window.CKEDITOR.on("instanceReady", function (event) {
                syncEditorTheme(event.editor);
            });
            ckEditorEventsBound = true;
        }

        syncEditorsTheme();
        return true;
    };

    var ckEditorBindAttempts = 0;
    var ckEditorBindTimer = window.setInterval(function () {
        ckEditorBindAttempts += 1;
        if (bindCkEditorTheme() || ckEditorBindAttempts >= 50) {
            window.clearInterval(ckEditorBindTimer);
        }
    }, 100);

    document.addEventListener("sx:themechange", function () {
        window.setTimeout(syncEditorsTheme, 0);
    });

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
            window.setTimeout(bindCkEditorTheme, 0);
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

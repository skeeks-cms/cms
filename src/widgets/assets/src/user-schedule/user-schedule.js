(function (sx, $) {
    "use strict";

    var scheduleSelector = ".sx-schedule-pjax[data-sx-schedule-refresh]";

    function disposeScheduleTooltips($schedule) {
        if (!$.fn.tooltip) {
            return;
        }

        $schedule.find("[data-toggle='tooltip']").each(function () {
            var $trigger = $(this);
            var tooltipId = $trigger.attr("aria-describedby");
            var instance = $trigger.data("bs.tooltip") || $trigger.data("tooltip");
            var $tip = instance && typeof instance.tip === "function" ? $(instance.tip()) : null;

            try {
                $trigger.tooltip("hide");
                $trigger.tooltip("dispose");
            } catch (disposeError) {
                try {
                    $trigger.tooltip("destroy");
                } catch (destroyError) {
                    // The trigger may already have been disposed by another handler.
                }
            }

            if ($tip && $tip.length) {
                $tip.remove();
            }

            if (tooltipId) {
                $(document.getElementById(tooltipId)).remove();
            }
        });
    }

    function replaceSchedule($schedule, html) {
        var nodes = $.parseHTML(html, document, true);
        var $replacement = $(nodes).filter(scheduleSelector).first();

        if (!$replacement.length) {
            $replacement = $("<div>").append(nodes).find(scheduleSelector).first();
        }

        if (!$replacement.length || $replacement.attr("id") !== $schedule.attr("id")) {
            return null;
        }

        disposeScheduleTooltips($schedule);
        $schedule.replaceWith($replacement);
        initScheduleRefresh($replacement);
        return $replacement;
    }

    function refreshSchedule($schedule, options) {
        options = options || {};

        if (!$schedule.length || $schedule.data("sx-schedule-request")) {
            return null;
        }

        var url = $schedule.data("sx-schedule-url");
        if (!url) {
            return null;
        }

        var request = $.ajax({
            url: url,
            type: options.type || "get",
            data: options.data || {},
            dataType: "html",
            cache: false,
            global: false,
            timeout: 15000
        });

        $schedule.data("sx-schedule-request", request);

        request.done(function (html) {
            replaceSchedule($schedule, html);
        });

        request.always(function () {
            $schedule.removeData("sx-schedule-request");
        });

        return request;
    }

    function initScheduleRefresh(context) {
        var $context = $(context || document);
        var $schedules = $context.find(scheduleSelector).add($context.filter(scheduleSelector));

        $schedules.each(function () {
            var $schedule = $(this);
            var interval = parseInt($schedule.data("sx-schedule-refresh"), 10);

            if ($.fn.tooltip) {
                $schedule.find("[data-toggle='tooltip']").tooltip();
            }

            if (!interval || $schedule.data("sx-schedule-refresh-timer")) {
                return;
            }

            var timer = window.setInterval(function () {
                if (!document.documentElement.contains($schedule[0])) {
                    window.clearInterval(timer);
                    return;
                }

                if (!document.hidden) {
                    refreshSchedule($schedule);
                }
            }, interval);

            $schedule.data("sx-schedule-refresh-timer", timer);
            $schedule.attr("data-sx-schedule-ready", "true");

            $schedule.find("form").off("submit.sxSchedule").on("submit.sxSchedule", function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();

                var $form = $(this);
                var $submit = $form.find(":submit");
                $submit.prop("disabled", true);

                var request = refreshSchedule($schedule, {
                    type: $form.attr("method") || "post",
                    data: $form.serialize()
                });

                if (request) {
                    request.fail(function () {
                        $submit.prop("disabled", false);
                    });
                } else {
                    $submit.prop("disabled", false);
                }
            });
        });
    }

    function initDocument() {
        initScheduleRefresh(document);
    }

    if (document.readyState === "loading") {
        $(initDocument);
    } else {
        initDocument();
    }

    $(document)
        .on("sx:schedule:refresh", function () {
            $(scheduleSelector).each(function () {
                refreshSchedule($(this));
            });
        })
        .on("visibilitychange", function () {
            if (!document.hidden) {
                $(document).trigger("sx:schedule:refresh");
            }
        })
        .on("pjax:end", function (event) {
            initScheduleRefresh(event.target);
        })
        .on("click", ".sx-total-link", function () {
            $(".sx-schedule-times").toggle();
            $(this).tooltip("hide");
            return false;
        });
})(sx, sx.$);

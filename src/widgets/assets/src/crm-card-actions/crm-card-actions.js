/**
 * The block of actions of a CRM card (CrmCardActionsWidget): the one-line
 * note field opens into the comment editor, "Отмена" folds it back; "Ещё" is
 * a native <details> closed by an outside click, a chosen item or Escape.
 * Delegated handlers: the block lives in a pjax container.
 */
(function (sx, $) {
    "use strict";

    if (!$) {
        return;
    }

    var toggle = function ($block, open) {
        var $form = $block.find(".sx-crm-actions__form");
        $block.toggleClass("is-open", open);
        $form.prop("hidden", !open);
        if (open) {
            var root = $form.find("[data-sx-rte]").get(0);
            if (root && sx && sx.RichText && sx.RichText.focus) {
                sx.RichText.focus(root);
            } else {
                $form.find("textarea, [contenteditable=true]").first().trigger("focus");
            }
        }
    };

    // Tabs that do not fit the row go into "Ещё" (their hidden copies there are shown).
    var fit = function (block) {
        var row = block.querySelector(".sx-crm-actions__tabs");
        var more = block.querySelector("[data-sx-crm-actions-more]");
        if (!row || !more) {
            return;
        }
        var tabs = Array.prototype.slice.call(row.querySelectorAll(":scope > [data-sx-crm-actions-fit]"));
        var copies = Array.prototype.slice.call(more.querySelectorAll("[data-sx-crm-actions-copy]"));
        // A tab with a choice has a copy per item of the choice.
        var copiesOf = function (tab) {
            var id = tab.getAttribute("data-sx-crm-actions-fit");
            return copies.filter(function (copy) { return copy.getAttribute("data-sx-crm-actions-copy") === id; });
        };
        var own = more.querySelectorAll(".sx-crm-actions__menu-item:not([data-sx-crm-actions-copy])").length;
        tabs.forEach(function (tab) { tab.hidden = false; });
        copies.forEach(function (copy) { copy.hidden = true; });
        more.hidden = false;
        var style = window.getComputedStyle(row);
        var width = row.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
        var used = function () {
            var sum = 0;
            Array.prototype.forEach.call(row.children, function (child) {
                if (!child.hidden) {
                    sum += child.getBoundingClientRect().width + 2;
                }
            });
            return sum;
        };
        var moved = 0;
        for (var i = tabs.length - 1; i >= 0 && used() > width; i--) {
            tabs[i].hidden = true;
            tabs[i].removeAttribute("open");
            copiesOf(tabs[i]).forEach(function (copy) { copy.hidden = false; });
            moved++;
        }
        more.hidden = !moved && !own;
        block.classList.add("is-fitted");
    };
    var fitAll = function () {
        Array.prototype.forEach.call(document.querySelectorAll("[data-sx-crm-actions]"), fit);
    };
    var timer = null;
    $(fitAll);
    $(window).off(".sxCrmActions").on("resize.sxCrmActions load.sxCrmActions", function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(fitAll, 100);
    });

    $(document)
        .off(".sxCrmActions")
        .on("pjax:end.sxCrmActions", fitAll)
        .on("click.sxCrmActions", "[data-sx-crm-actions-open]", function (e) {
            e.preventDefault();
            toggle($(this).closest("[data-sx-crm-actions]"), true);
        })
        .on("click.sxCrmActions", "[data-sx-crm-actions] [data-sx-comment-cancel]", function (e) {
            e.preventDefault();
            var $block = $(this).closest("[data-sx-crm-actions]");
            toggle($block, false);
            $block.find(".sx-crm-actions__placeholder").trigger("focus");
        })
        .on("click.sxCrmActions", function (e) {
            $("[data-sx-crm-actions-menu][open]").each(function () {
                if (!this.contains(e.target) || $(e.target).closest(".sx-crm-actions__menu-item").length) {
                    this.removeAttribute("open");
                }
            });
        })
        .on("keydown.sxCrmActions", function (e) {
            if (e.key === "Escape") {
                $("[data-sx-crm-actions-menu][open]").removeAttr("open");
            }
        });
})(window.sx, window.jQuery);

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
    // «Примечание» of the header menu «⋯»: the note of the block on this page, else the profile with #sx-crm-note.
    var openNote = function () {
        var $block = $("[data-sx-crm-actions]").first();
        if (!$block.length || !$block.find("[data-sx-crm-actions-open]").length) {
            return false;
        }
        toggle($block, true);
        $block.get(0).scrollIntoView({block: "center", behavior: "smooth"});
        return true;
    };
    // An opened menu stays inside the screen wherever its button is (a narrow header puts «⋯» at the left).
    document.addEventListener("toggle", function (e) {
        var details = e.target;
        if (!details.open || !details.matches || !details.matches("[data-sx-crm-actions-menu]")) {
            return;
        }
        var menu = details.querySelector(":scope > .sx-crm-actions__menu");
        if (!menu) {
            return;
        }
        menu.style.left = "";
        menu.style.right = "";
        menu.style.maxHeight = "";
        menu.style.overflowY = "";
        var gap = 8, box = details.getBoundingClientRect(), rect = menu.getBoundingClientRect();
        if (rect.left < gap) {
            menu.style.right = "auto";
            menu.style.left = (gap - box.left) + "px";
        } else if (rect.right > window.innerWidth - gap) {
            menu.style.left = "auto";
            menu.style.right = (box.right - window.innerWidth + gap) + "px";
        }
        // A long menu (16 items of «⋯» on a phone) scrolls inside the screen; Tab scrolls to the focused item.
        var room = window.innerHeight - rect.top - gap;
        if (rect.height > room && room >= 160) {
            menu.style.maxHeight = room + "px";
            menu.style.overflowY = "auto";
        }
    }, true);
    var timer = null;
    $(fitAll);
    $(function () {
        if (window.location.hash === "#sx-crm-note") {
            openNote();
        }
    });
    $(window).off(".sxCrmActions").on("resize.sxCrmActions load.sxCrmActions", function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(fitAll, 100);
    });

    $(document)
        .off(".sxCrmActions")
        .on("pjax:end.sxCrmActions", fitAll)
        // The deal of the note: its form is shown.
        .on("change.sxCrmActions", "[data-sx-crm-actions-deal-select]", function () {
            var value = String(this.value);
            $(this).closest("[data-sx-crm-actions]").find("[data-sx-crm-actions-deal]").each(function () {
                this.hidden = String(this.getAttribute("data-sx-crm-actions-deal")) !== value;
            });
            fitAll();
        })
        .on("click.sxCrmActions", "[data-sx-crm-actions-note]", function (e) {
            if (openNote()) {
                e.preventDefault();
                $(this).closest("[data-sx-crm-actions-menu]").removeAttr("open");
            }
        })
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

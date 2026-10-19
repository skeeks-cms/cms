(function (sx, $) {
    'use strict';

    var boardSelector = '[data-sx-lead-kanban]';
    var listSelector = '[data-sx-kanban-list]';
    var cardSelector = '[data-sx-kanban-card]';

    function revert(event) {
        var siblings = event.jFrom.children(cardSelector);
        if (event.oldIndex === null || event.oldIndex >= siblings.length) {
            event.jFrom.append(event.jItem);
        } else {
            siblings.eq(event.oldIndex).before(event.jItem);
        }
    }

    function shiftCount(list, delta) {
        var counter = $(list).closest('[data-sx-kanban-column]').find('[data-sx-kanban-count]');
        counter.text(Math.max(0, parseInt(counter.text(), 10) + delta));
    }

    function move(board, event) {
        var column = event.jContainer.closest('[data-sx-kanban-column]').data('sxKanbanColumn');
        var data = {pk: event.jItem.data('pk'), column: column};
        data[yii.getCsrfParam()] = yii.getCsrfToken();

        board.addClass('is-busy');
        $.ajax({
            url: board.data('moveUrl'),
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (!response || !response.success) {
                revert(event);
                sx.notify.error(response && response.message ? response.message : 'Не удалось перенести лид.');
                return;
            }
            if (response.data && response.data.openUrl) {
                // A result or a rejection reason is required: finish in the form.
                revert(event);
                new sx.classes.backend.widgets.Action({
                    isOpenNewWindow: true,
                    url: response.data.openUrl
                }).go();
                return;
            }
            shiftCount(event.from, -1);
            shiftCount(event.container, 1);
            if (response.data && response.data.card) {
                event.jItem.replaceWith(response.data.card);
            }
        }).fail(function () {
            revert(event);
            sx.notify.error('Не удалось перенести лид. Обновите страницу.');
        }).always(function () {
            board.removeClass('is-busy');
        });
    }

    function init(element) {
        var board = $(element);
        if (board.data('sxLeadKanbanReady')) {
            return;
        }
        board.data('sxLeadKanbanReady', true);

        sx.backend.sortable.create(board.find(listSelector), {
            group: 'sx-lead-kanban-' + board.attr('id'),
            itemSelector: cardSelector,
            cancel: 'a, button, input, textarea, select',
            onUpdate: function (event) {
                // Order inside a column is not stored; only column changes matter.
                if (event.from === event.container || board.hasClass('is-busy')) {
                    if (event.from !== event.container) {
                        revert(event);
                    }
                    return;
                }
                move(board, event);
            }
        });
    }

    $(function () {
        $(boardSelector).each(function () { init(this); });
    });

    // Choosing an employee applies the filter at once; search waits for Enter.
    $(document).on('change', '[data-sx-lead-kanban-filters] [data-sx-lead-kanban-submit]', function () {
        this.form.submit();
    });

    // The settings menu is a native <details>: close it on an outside click or Escape.
    $(document).on('click', function (event) {
        $('[data-sx-lead-kanban-menu][open]').each(function () {
            if (!this.contains(event.target)) {
                this.removeAttribute('open');
            }
        });
    }).on('keydown', function (event) {
        if (event.key === 'Escape') {
            $('[data-sx-lead-kanban-menu][open]').removeAttr('open');
        }
    });
}(sx, sx.$));

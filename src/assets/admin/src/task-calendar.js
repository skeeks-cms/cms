(function (sx, $) {
    'use strict';

    // A task opens in a window, as in the other task lists.
    $(document).on('click', '[data-sx-task-open]', function (event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.button === 1) {
            return;
        }
        event.preventDefault();
        new sx.classes.backend.widgets.Action({isOpenNewWindow: true, url: $(this).data('sxTaskOpen')}).go();
    });

    // The «now» line of the week and day moves every minute.
    function moveNowLines() {
        var now = Math.floor(Date.now() / 1000);
        $('[data-sx-task-calendar] .sx-task-calendar__col.is-today').each(function () {
            var col = $(this);
            var start = parseInt(col.data('dayStart'), 10);
            var span = parseInt(col.data('span'), 10);
            var line = col.children('.sx-task-calendar__now');
            if (now < start || now >= start + span) {
                line.remove();
                return;
            }
            if (!line.length) {
                line = $('<div class="sx-task-calendar__now"></div>').appendTo(col);
            }
            line.css('top', ((now - start) / span * 100) + '%');
        });
    }

    $(function () {
        moveNowLines();
        setInterval(moveNowLines, 60000);

        // A week or a day opens scrolled to the current hour or the first task.
        $('[data-sx-task-calendar] .sx-task-calendar__timeline').each(function () {
            var timeline = $(this);
            var target = timeline.find('.sx-task-calendar__now').first();
            if (!target.length) {
                timeline.find('.sx-task-calendar__block').each(function () {
                    if (!target.length || $(this).position().top < target.position().top) {
                        target = $(this);
                    }
                });
            }
            if (target.length && timeline[0].scrollHeight > timeline[0].clientHeight) {
                timeline.scrollTop(Math.max(0, target.position().top - 80));
            }
        });
    });

    // «Сроки»: a card moved to another column gets the first free time of that column's day.
    var boardSelector = '[data-sx-task-deadlines]';
    var listSelector = '[data-sx-deadlines-list]';
    var cardSelector = '[data-sx-deadlines-card]';

    function revert(event) {
        var siblings = event.jFrom.children(cardSelector);
        if (event.oldIndex === null || event.oldIndex >= siblings.length) {
            event.jFrom.append(event.jItem);
        } else {
            siblings.eq(event.oldIndex).before(event.jItem);
        }
    }

    function move(board, event) {
        var day = event.jContainer.closest('[data-sx-deadlines-column]').data('day');
        if (!day) {
            revert(event);
            return;
        }
        var data = {day: day};
        data[yii.getCsrfParam()] = yii.getCsrfToken();
        board.addClass('is-busy');
        $.ajax({
            url: board.data('moveUrl') + (board.data('moveUrl').indexOf('?') === -1 ? '?' : '&') + 'pk=' + event.jItem.data('pk'),
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (!response || !response.success) {
                revert(event);
                sx.notify.error(response && response.message ? response.message : 'Не удалось перенести задачу.');
                return;
            }
            (response.warnings || []).forEach(function (text) { sx.notify.info(text); });
            sx.notify.success('Задача перенесена на ' + response.task.start_text + '.');
            setTimeout(function () { window.location.reload(); }, 600);
        }).fail(function () {
            revert(event);
            sx.notify.error('Не удалось перенести задачу. Обновите страницу.');
        }).always(function () {
            board.removeClass('is-busy');
        });
    }

    $(function () {
        $(boardSelector).each(function () {
            var board = $(this);
            sx.backend.sortable.create(board.find(listSelector), {
                group: 'sx-task-deadlines-' + board.attr('id'),
                itemSelector: cardSelector,
                handle: '[data-sx-deadlines-handle]',
                cancel: 'a, button, input, textarea, select',
                onUpdate: function (event) {
                    if (event.from === event.container || board.hasClass('is-busy')) {
                        if (event.from !== event.container) {
                            revert(event);
                        }
                        return;
                    }
                    move(board, event);
                }
            });
        });
    });
}(sx, sx.$));

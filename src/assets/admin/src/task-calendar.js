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

    /**
     * A board or a timeline marked [data-sx-fit-screen] ends at the bottom of the screen: its own scrollbars
     * (the horizontal one too) stay in sight instead of being far below on the page.
     */
    function fitScreen() {
        $('[data-sx-fit-screen]').each(function () {
            var el = $(this);
            // "height" for a board whose columns scroll each on their own, max-height otherwise.
            el.css(el.data('sxFitScreen') === 'height' ? 'height' : 'max-height', Math.max(320, window.innerHeight - el.offset().top - 16));
        });
    }

    $(window).on('resize load', fitScreen);

    $(function () {
        fitScreen();
        setTimeout(fitScreen, 400);
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
        // A fixed task keeps its time of day; an auto task takes the first free time of that day.
        var data = event.jItem.data('fixed') ? {day: day, keep_time: 1} : {day: day};
        board.addClass('is-busy');
        sx.taskMove(board.data('moveUrl'), event.jItem.data('pk'), data, {
            kind: 'day',
            done: function (state, response) {
                board.removeClass('is-busy');
                if (state === 'moved') {
                    event.jItem.find('[data-sx-deadlines-time]').text(response.task.text);
                    return;
                }
                if (state === false) {
                    revert(event);
                }
                window.location.reload();
            }
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

/**
 * One move of a task from every task view (owner, 31.10.2026): the request, then a message at the bottom —
 * «Было 17:40 → 18:00» or «Перенесено на пн 2 нояб. 17:40», the reminder's new time, warnings — and «Отменить»,
 * which restores the former start and mode exactly. `opts.kind`: "time" (calendar, Gantt) or "day" (list, «Сроки»);
 * `opts.done(state, response)` runs with 'moved', 'undone' or false (a reload or a redraw).
 */
(function (sx, $) {
    'use strict';

    var box = null;
    var timer = null;

    function show(html, undo) {
        if (!box) {
            box = $('<div class="sx-task-undo" role="status"></div>').appendTo('body');
        }
        clearTimeout(timer);
        box.empty().append($('<span class="sx-task-undo__text"></span>').html(html));
        if (undo) {
            $('<button type="button" class="sx-task-undo__btn">Отменить</button>').on('click', function () {
                box.removeClass('is-shown');
                undo();
            }).appendTo(box);
        }
        $('<button type="button" class="sx-task-undo__close" title="Закрыть">&times;</button>').on('click', function () {
            box.removeClass('is-shown');
        }).appendTo(box);
        box.addClass('is-shown');
        timer = setTimeout(function () { box.removeClass('is-shown'); }, 12000);
    }

    function esc(text) {
        return $('<span>').text(text || '').html();
    }

    function post(url, id, data) {
        data = $.extend({}, data);
        data[yii.getCsrfParam()] = yii.getCsrfToken();
        return $.ajax({
            url: url + (url.indexOf('?') === -1 ? '?' : '&') + 'pk=' + id,
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
    }

    sx.taskMove = function (url, id, data, opts) {
        opts = opts || {};
        var done = opts.done || function () {};
        post(url, id, data).done(function (response) {
            if (!response || !response.success) {
                sx.notify.error(response && response.message ? response.message : 'Не удалось перенести задачу.');
                done(false);
                return;
            }
            var prev = response.previous || {};
            var task = response.task || {};
            var minutes = function (seconds) {
                var m = Math.round((seconds || 0) / 60);
                return m >= 60 ? (Math.floor(m / 60) + ' ч' + (m % 60 ? ' ' + (m % 60) + ' мин' : '')) : m + ' мин';
            };
            var html = opts.kind === 'time'
                ? 'Было ' + esc(prev.text) + ' → <b>' + esc(task.text) + '</b>'
                : (opts.kind === 'duration'
                    ? 'Длительность ' + minutes(prev.plan_duration) + ' → <b>' + minutes(task.plan_duration) + '</b>'
                    : 'Перенесено на <b>' + esc(task.text) + '</b>');
            if (task.reminder) {
                html += '<br><small>' + esc(task.reminder) + '</small>';
            }
            (response.warnings || []).forEach(function (text) {
                html += '<br><small class="sx-task-undo__warning">' + esc(text) + '</small>';
            });
            show(html, function () {
                // The former start, mode and reminder exactly (an auto task goes back to its place and is laid out).
                var back = {
                    plan_start_at: prev.plan_start_at,
                    fixed: prev.is_fixed_start ? 1 : 0,
                    restore: 1,
                    plan_duration: prev.plan_duration,
                    remind_at: prev.remind_at || '',
                    reminded_at: prev.reminded_at || ''
                };
                post(url, id, back).done(function (r) {
                    if (r && r.success) {
                        show('Вернули на <b>' + esc(r.task.text) + '</b>');
                    } else {
                        sx.notify.error(r && r.message ? r.message : 'Не удалось отменить перенос.');
                    }
                    done('undone');
                }).fail(function () {
                    sx.notify.error('Не удалось отменить перенос.');
                });
            });
            done('moved', response);
        }).fail(function () {
            sx.notify.error('Не удалось перенести задачу. Обновите страницу.');
            done(false);
        });
    };
}(sx, sx.$));

/**
 * The calendar's week and day: a block dragged up, down or to another day changes the start in steps of
 * 5 minutes, with the new time shown while dragging; then «Было → стало · Отменить» (sx.taskMove).
 */
(function (sx, $) {
    'use strict';

    var STEP = 300;

    function fmt(ts) {
        var d = new Date(ts * 1000);
        return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
    }

    $(document).on('pointerdown', '.sx-task-calendar__block[data-sx-task-drag]', function (e) {
        if (e.button !== 0) {
            return;
        }
        var block = $(this);
        var calendar = block.closest('[data-sx-task-calendar]');
        var startCol = block.closest('.sx-task-calendar__col');
        var x0 = e.clientX, y0 = e.clientY;
        var moved = false;
        var start = parseInt(block.data('start'), 10);
        var newStart = start;
        var col = startCol;
        var top0 = parseFloat(block[0].style.top);
        var tip = $('<div class="sx-task-calendar__drag-tip"></div>');
        try { this.setPointerCapture(e.pointerId); } catch (err) {}

        block.on('pointermove.sxdrag', function (ev) {
            var dx = ev.clientX - x0, dy = ev.clientY - y0;
            if (!moved && Math.abs(dx) < 4 && Math.abs(dy) < 4) {
                return;
            }
            if (!moved) {
                moved = true;
                block.addClass('is-dragging');
            }
            // The column under the pointer (another day of the week).
            var under = $(document.elementsFromPoint(ev.clientX, ev.clientY)).filter('.sx-task-calendar__col').first();
            if (under.length && under[0] !== col[0]) {
                col = under;
                col.append(block);
            }
            var span = parseInt(col.data('span'), 10);
            var dayStart = parseInt(col.data('dayStart'), 10);
            var height = col[0].getBoundingClientRect().height;
            var startDay = parseInt(startCol.data('dayStart'), 10);
            var offset = start - startDay + dy / height * span;
            newStart = dayStart + Math.round(offset / STEP) * STEP;
            newStart = Math.max(dayStart, Math.min(dayStart + span - STEP, newStart));
            block.css('top', ((newStart - dayStart) / span * 100) + '%');
            tip.text(fmt(newStart));
            if (!tip.parent().length) {
                tip.appendTo(block);
            }
            ev.preventDefault();
        }).on('pointerup.sxdrag pointercancel.sxdrag', function (ev) {
            block.off('.sxdrag').removeClass('is-dragging');
            tip.remove();
            if (!moved) {
                return; // a click opens the task
            }
            // A drag is not a click.
            block.one('click', function (c) { c.preventDefault(); c.stopImmediatePropagation(); });
            if (newStart === start) {
                return;
            }
            sx.taskMove(calendar.data('moveUrl'), block.data('sxTaskDrag'), {plan_start_at: newStart}, {
                kind: 'time',
                done: function (state) {
                    if (state === 'moved') {
                        block.data('start', newStart).attr('data-start', newStart);
                        block.find('.sx-task-calendar__task-time').contents().first().replaceWith(fmt(newStart));
                        return;
                    }
                    window.location.reload();
                }
            });
        });
        e.preventDefault();
    });
}(sx, sx.$));

/**
 * The pin of a task as a toggle (owner, 31.10.2026): [data-sx-task-pin] — fixing keeps the current start at once;
 * unfixing asks first («Снять закрепление с 17:40?», «Снять» / «Оставить»), mentioning a reminder when there is one.
 * The question is the standard dialog of the backend, sx.backend.confirm() (BackendConfirmAsset of skeeks/cms-backend).
 */
(function (sx, $) {
    'use strict';

    function confirmUnpin(options) {
        if (sx.backend && sx.backend.confirm) {
            sx.backend.confirm(options);
            return;
        }
        if (window.confirm(options.title + '\n\n' + options.text)) {
            options.onOk();
        }
    }

    function pin(el, fixed) {
        var data = {fixed: fixed ? 1 : 0};
        data[yii.getCsrfParam()] = yii.getCsrfToken();
        var url = el.data('url');
        $.ajax({
            url: url + (url.indexOf('?') === -1 ? '?' : '&') + 'pk=' + el.data('sxTaskPin'),
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (!response || !response.success) {
                sx.notify.error(response && response.message ? response.message : 'Не удалось изменить закрепление.');
                return;
            }
            sx.notify.success(response.message);
            setTimeout(function () { window.location.reload(); }, 900);
        }).fail(function () {
            sx.notify.error('Не удалось изменить закрепление.');
        });
    }

    $(document).on('click', '[data-sx-task-pin]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var el = $(this);
        if (!el.data('fixed')) {
            pin(el, true);
            return;
        }
        var text = 'Задача станет обычной, и система сама поставит её в свободное время. '
            + 'Если вы обещали это время клиенту, лучше перенесите задачу в карточке.';
        if (el.data('reminder')) {
            text += '\n\nНапоминание тоже сдвинется вместе с задачей.';
        }
        confirmUnpin({
            title: 'Снять закрепление с ' + el.data('time') + '?',
            text: text,
            ok: 'Снять',
            cancel: 'Оставить',
            onOk: function () { pin(el, false); }
        });
    });
}(sx, sx.$));

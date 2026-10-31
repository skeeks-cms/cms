(function (sx, $) {
    'use strict';

    var SCALES = {
        // seconds per pixel, snap step of a move in seconds, width of a tick in seconds
        day: {pps: 160 / 3600, snap: 300, tick: 3600},
        week: {pps: 216 / 86400, snap: 900, tick: 86400},
        month: {pps: 44 / 86400, snap: 86400, tick: 86400}
    };
    var ROW = 34;

    function store(key, value) {
        try {
            if (value === undefined) {
                return window.localStorage.getItem('sx-task-gantt-' + key);
            }
            window.localStorage.setItem('sx-task-gantt-' + key, value);
        } catch (e) {}
        return null;
    }

    function Gantt(element) {
        var self = this;
        this.el = $(element);
        this.cfg = this.el.data('sxTaskGantt');
        this.tasks = this.cfg.tasks;
        this.work = this.cfg.work;
        this.now = this.cfg.now;
        this.scale = SCALES[store('scale')] ? store('scale') : 'week';
        this.group = store('group') || '';
        this.anchor = this.now;
        this.parts = new Intl.DateTimeFormat('en-GB', {
            timeZone: this.cfg.tz, year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23', weekday: 'short'
        });
        this.fmtTime = new Intl.DateTimeFormat('ru-RU', {timeZone: this.cfg.tz, hour: '2-digit', minute: '2-digit'});
        this.fmtDay = new Intl.DateTimeFormat('ru-RU', {timeZone: this.cfg.tz, day: 'numeric', month: 'short'});
        this.fmtWeekday = new Intl.DateTimeFormat('ru-RU', {timeZone: this.cfg.tz, weekday: 'short'});
        this.fmtMonth = new Intl.DateTimeFormat('ru-RU', {timeZone: this.cfg.tz, month: 'long', year: 'numeric'});
        this.fmtFull = new Intl.DateTimeFormat('ru-RU', {timeZone: this.cfg.tz, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'});

        this.el.find('[data-gantt-group]').val(this.group).on('change', function () {
            self.group = $(this).val();
            store('group', self.group);
            self.render();
        });
        this.el.find('[data-gantt-scale]').on('click', function () {
            self.scale = $(this).data('ganttScale');
            store('scale', self.scale);
            self.render(true);
        });
        this.el.find('[data-gantt-shift]').on('click', function () {
            self.shift(parseInt($(this).data('ganttShift'), 10));
        });
        this.el.find('[data-gantt-today]').on('click', function () {
            self.anchor = self.now;
            self.render(true);
        });
        this.el.find('[data-gantt-user]').on('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('executor_id', $(this).val());
            window.location.href = url.toString();
        });

        this.render(true);
        setInterval(function () {
            self.now = Math.floor(Date.now() / 1000);
            self.drawNow();
        }, 60000);
    }

    /** Date parts of a moment in the site's time zone. */
    Gantt.prototype.p = function (ts) {
        var r = {};
        this.parts.formatToParts(new Date(ts * 1000)).forEach(function (part) {
            r[part.type] = part.value;
        });
        return {y: +r.year, m: +r.month, d: +r.day, h: +r.hour, i: +r.minute, wd: r.weekday};
    };

    /** The moment of a local date and time of the site's time zone. */
    Gantt.prototype.at = function (y, m, d, h, i) {
        var guess = Date.UTC(y, m - 1, d, h || 0, i || 0) / 1000;
        var q = this.p(guess);
        var offset = (Date.UTC(q.y, q.m - 1, q.d, q.h, q.i) / 1000) - guess;
        return guess - offset;
    };

    Gantt.prototype.range = function () {
        var a = this.p(this.anchor);
        var from, to;
        if (this.scale === 'day') {
            from = this.at(a.y, a.m, a.d);
            to = this.at(a.y, a.m, a.d + 1);
        } else if (this.scale === 'week') {
            var shift = ({Mon: 0, Tue: 1, Wed: 2, Thu: 3, Fri: 4, Sat: 5, Sun: 6})[a.wd] || 0;
            from = this.at(a.y, a.m, a.d - shift);
            to = this.at(a.y, a.m, a.d - shift + 7);
        } else {
            from = this.at(a.y, a.m, 1);
            to = this.at(a.y, a.m + 1, 1);
        }
        return {from: from, to: to};
    };

    Gantt.prototype.shift = function (direction) {
        var a = this.p(this.anchor);
        if (this.scale === 'day') {
            this.anchor = this.at(a.y, a.m, a.d + direction, 12);
        } else if (this.scale === 'week') {
            this.anchor = this.at(a.y, a.m, a.d + 7 * direction, 12);
        } else {
            this.anchor = this.at(a.y, a.m + direction, 1, 12);
        }
        this.render(true);
    };

    Gantt.prototype.title = function (r) {
        if (this.scale === 'day') {
            return this.fmtFull.format(new Date(r.from * 1000));
        }
        if (this.scale === 'week') {
            return this.fmtDay.format(new Date(r.from * 1000)) + ' — ' + this.fmtDay.format(new Date((r.to - 1) * 1000));
        }
        var t = this.fmtMonth.format(new Date((r.from + 3600) * 1000));
        return t.charAt(0).toUpperCase() + t.slice(1);
    };

    Gantt.prototype.rows = function (r) {
        var self = this;
        var tasks = this.tasks.filter(function (t) {
            return (t.end > r.from && t.start < r.to) || t.late;
        });
        // Overdue tasks before the period go after the others: the period's own tasks come first.
        var before = tasks.filter(function (t) { return t.end <= r.from; });
        tasks = tasks.filter(function (t) { return t.end > r.from; }).concat(before);
        if (!this.group) {
            return tasks.map(function (t) { return {task: t}; });
        }
        var groups = {};
        var order = [];
        tasks.forEach(function (t) {
            var key = (self.group === 'project' ? t.project : t.client) || '';
            if (!groups[key]) {
                groups[key] = [];
                order.push(key);
            }
            groups[key].push(t);
        });
        order.sort(function (a, b) {
            return a === '' ? 1 : (b === '' ? -1 : a.localeCompare(b, 'ru'));
        });
        var rows = [];
        order.forEach(function (key) {
            rows.push({title: key || (self.group === 'project' ? 'Без проекта' : 'Без клиента'), count: groups[key].length});
            groups[key].forEach(function (t) { rows.push({task: t}); });
        });
        return rows;
    };

    Gantt.prototype.render = function (scrollToNow) {
        var self = this;
        var sc = SCALES[this.scale];
        var r = this.range();
        this.r = r;
        var width = Math.round((r.to - r.from) * sc.pps);
        var rows = this.rows(r);

        this.el.find('[data-gantt-scale]').removeClass('btn-primary').addClass('btn-default')
            .filter('[data-gantt-scale="' + this.scale + '"]').removeClass('btn-default').addClass('btn-primary');
        this.el.find('[data-gantt-title]').text(this.title(r));

        var side = $('<div class="sx-task-gantt__side"><div class="sx-task-gantt__side-head">Задача</div></div>');
        var main = $('<div class="sx-task-gantt__main"></div>');
        var scale = $('<div class="sx-task-gantt__scale"></div>').css('width', width);
        var canvas = $('<div class="sx-task-gantt__canvas"></div>').css({width: width, height: Math.max(rows.length, 1) * ROW});

        // Days: weekends and today; in the day scale — hours.
        for (var t = r.from; t < r.to;) {
            var q = this.p(t);
            var next = this.scale === 'day' ? t + 3600 : this.at(q.y, q.m, q.d + 1);
            var left = (t - r.from) * sc.pps;
            var w = (next - t) * sc.pps;
            var label = this.scale === 'day' ? this.fmtTime.format(new Date(t * 1000))
                : (this.scale === 'week' ? this.fmtWeekday.format(new Date(t * 1000)) + ', ' + this.fmtDay.format(new Date(t * 1000)) : q.d);
            var cls = 'sx-task-gantt__tick';
            var dayParts = this.p(t);
            if (this.scale !== 'day' && (dayParts.wd === 'Sat' || dayParts.wd === 'Sun')) {
                cls += ' is-weekend';
            }
            var nq = this.p(this.now);
            if (this.scale !== 'day' && dayParts.y === nq.y && dayParts.m === nq.m && dayParts.d === nq.d) {
                cls += ' is-today';
            }
            scale.append($('<div></div>').addClass(cls).css({left: left, width: w}).text(label));
            canvas.append($('<div class="sx-task-gantt__col"></div>').addClass(cls.replace('sx-task-gantt__tick', '')).css({left: left, width: w}));
            t = next;
        }
        // Working hours of the employee.
        this.work.forEach(function (p) {
            if (p[1] <= r.from || p[0] >= r.to) {
                return;
            }
            var a = Math.max(p[0], r.from), b = Math.min(p[1], r.to);
            canvas.append($('<div class="sx-task-gantt__work"></div>').css({left: (a - r.from) * sc.pps, width: (b - a) * sc.pps}));
        });

        rows.forEach(function (row, index) {
            if (!row.task) {
                side.append($('<div class="sx-task-gantt__group"></div>').text(row.title + ' · ' + row.count));
                canvas.append($('<div class="sx-task-gantt__group-line"></div>').css({top: index * ROW}));
                return;
            }
            var task = row.task;
            var name = $('<a class="sx-task-gantt__name" href="#"></a>').attr('title', task.name).text(task.name)
                .on('click', function (e) { e.preventDefault(); self.open(task); });
            var item = $('<div class="sx-task-gantt__row"></div>').append(name);
            if (task.reminder) {
                item.append($('<i class="fas fa-bell sx-task-gantt__bell"></i>').toggleClass('is-sent', task.reminded).attr('title', task.reminder));
            }
            if (task.replanned) {
                item.append($('<i class="fas fa-history sx-task-gantt__bell is-sent"></i>').attr('title', task.replanned));
            }
            side.append(item);
            canvas.append($('<div class="sx-task-gantt__row-line"></div>').css({top: index * ROW}));
            canvas.append(self.bar(task, index));
        });
        if (!rows.length) {
            side.append('<div class="sx-task-gantt__empty">Нет задач в этом периоде</div>');
        }

        main.append(scale).append(canvas);
        this.el.find('[data-gantt-body]').empty().append(side).append(main);
        this.main = main;
        this.canvas = canvas;
        // Marks of overdue tasks stay at the visible left edge.
        main.on('scroll', function () {
            canvas.find('.sx-task-gantt__task--before').css('left', main.scrollLeft());
        });
        this.drawNow();

        if (scrollToNow) {
            var target = this.now > r.from && this.now < r.to ? (this.now - r.from) * sc.pps - 120 : 0;
            // After the layout: the stylesheet may come after the script.
            setTimeout(function () { main.scrollLeft(Math.max(0, target)).trigger('scroll'); }, 50);
        }
    };

    Gantt.prototype.drawNow = function () {
        if (!this.canvas) {
            return;
        }
        this.canvas.find('.sx-task-gantt__now').remove();
        if (this.now > this.r.from && this.now < this.r.to) {
            this.canvas.append($('<div class="sx-task-gantt__now"></div>').css('left', (this.now - this.r.from) * SCALES[this.scale].pps));
        }
    };

    Gantt.prototype.label = function (start, end) {
        var day = this.scale === 'day' ? '' : this.fmtDay.format(new Date(start * 1000)) + ' ';
        return day + this.fmtTime.format(new Date(start * 1000)) + '–' + this.fmtTime.format(new Date(end * 1000));
    };

    Gantt.prototype.bar = function (task, index) {
        var self = this;
        var sc = SCALES[this.scale];
        var r = this.r;
        if (task.end <= r.from) {
            // Overdue before the period: a mark at its left edge, the date of the start on it.
            return $('<div class="sx-task-gantt__task sx-task-gantt__task--before is-late"></div>')
                .css({left: 0, width: 96, top: index * ROW + 6})
                .attr('title', task.name + ' · просрочена с ' + this.fmtDay.format(new Date(task.start * 1000)))
                .append($('<span class="sx-task-gantt__task-text"></span>').text('← с ' + this.fmtDay.format(new Date(task.start * 1000))))
                .on('click', function () { self.open(task); });
        }
        var left = (task.start - r.from) * sc.pps;
        var width = Math.max(6, (task.end - task.start) * sc.pps);
        var bar = $('<div class="sx-task-gantt__task"></div>')
            .addClass(task.inWork ? 'is-in-work' : (task.late ? 'is-late' : (task.fixed ? 'is-fixed' : 'is-auto')))
            .toggleClass('is-movable', task.movable)
            .attr('data-id', task.id)
            .css({left: left, width: width, top: index * ROW + 6})
            .attr('title', task.name + ' · ' + this.label(task.start, task.end) + ' · ' + task.statusText
                + (task.fixed ? ' · закреплено на время' : ' · по графику') + (task.reminder ? ' · ' + task.reminder : '') + (task.replanned ? ' · ' + task.replanned : ''));
        var text = $('<span class="sx-task-gantt__task-text"></span>').text(this.fmtTime.format(new Date(task.start * 1000)) + ' ' + task.name);
        if (task.fixed) {
            text.prepend('<i class="fas fa-thumbtack sx-task-gantt__pin"></i> ');
        }
        bar.append(text);
        if (task.movable) {
            bar.append('<span class="sx-task-gantt__handle" title="Потяните, чтобы изменить длительность"></span>');
        }

        bar.on('pointerdown', function (e) {
            if (e.button !== 0) {
                return;
            }
            var resize = $(e.target).hasClass('sx-task-gantt__handle');
            var x0 = e.clientX;
            var moved = false;
            var newStart = task.start;
            var newDuration = task.end - task.start;
            var tip = $('<div class="sx-task-gantt__tip"></div>').appendTo(self.canvas).hide();
            try {
                this.setPointerCapture(e.pointerId);
            } catch (err) {}
            e.preventDefault();

            function snap(seconds) {
                return Math.round(seconds / sc.snap) * sc.snap;
            }

            $(this).on('pointermove.gantt', function (ev) {
                var dx = ev.clientX - x0;
                if (!moved && Math.abs(dx) < 4) {
                    return;
                }
                if (!task.movable) {
                    return;
                }
                moved = true;
                if (resize) {
                    newDuration = Math.max(300, snap((task.end - task.start) + dx / sc.pps));
                    bar.css('width', Math.max(6, newDuration * sc.pps));
                } else {
                    newStart = task.start + snap(dx / sc.pps);
                    bar.css('left', (newStart - r.from) * sc.pps);
                }
                tip.text(self.label(newStart, newStart + newDuration)).css({left: (newStart - r.from) * sc.pps, top: index * ROW - 20}).show();
            }).on('pointerup.gantt pointercancel.gantt', function (ev) {
                $(this).off('.gantt');
                tip.remove();
                if (!moved) {
                    if (ev.type === 'pointerup') {
                        self.open(task);
                    }
                    return;
                }
                if (resize) {
                    self.save(task, {plan_duration: newDuration});
                } else if (newStart !== task.start) {
                    self.save(task, {plan_start_at: newStart});
                }
            });
        });

        return bar;
    };

    Gantt.prototype.open = function (task) {
        new sx.classes.backend.widgets.Action({isOpenNewWindow: true, url: task.url}).go();
    };

    Gantt.prototype.save = function (task, values) {
        var self = this;
        var data = $.extend({}, values);
        data[yii.getCsrfParam()] = yii.getCsrfToken();
        this.el.addClass('is-busy');
        $.ajax({
            url: this.cfg.moveUrl + (this.cfg.moveUrl.indexOf('?') === -1 ? '?' : '&') + 'pk=' + task.id,
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (!response || !response.success) {
                sx.notify.error(response && response.message ? response.message : 'Не удалось изменить задачу.');
            } else {
                (response.warnings || []).forEach(function (text) { sx.notify.info(text); });
            }
        }).fail(function () {
            sx.notify.error('Не удалось изменить задачу. Обновите страницу.');
        }).always(function () {
            self.reload();
        });
    };

    /** The planner may have moved other auto tasks: take every task again. */
    Gantt.prototype.reload = function () {
        var self = this;
        $.getJSON(this.cfg.dataUrl).done(function (data) {
            self.tasks = data.tasks;
            self.work = data.work;
            self.now = data.now;
            var left = self.main ? self.main.scrollLeft() : 0;
            self.render(false);
            self.main.scrollLeft(left);
        }).always(function () {
            self.el.removeClass('is-busy');
        });
    };

    $(function () {
        $('[data-sx-task-gantt]').each(function () {
            if (!$(this).data('sxGanttReady')) {
                $(this).data('sxGanttReady', true);
                new Gantt(this);
            }
        });
    });
}(sx, sx.$));

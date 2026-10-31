/**
 * The date and time field (skeeks/cms DateTimeInputWidget).
 *
 * Root [data-sx-datetime] (JSON: type, save, display, tz, step, min, max,
 * clear, quick, defaultTime, placeholder) holds the hidden input
 * .sx-dt__value (the saved value), the hidden mirror .sx-dt__display (the
 * shown text), the trigger .sx-dt__trigger and the clear button .sx-dt__clear.
 * Everything is delegated from the document: fields loaded by PJAX or into
 * windows need no initialization. One window is open at a time.
 *
 * Wall time ({y, m, d, H, i}) is in cfg.tz; "U" is converted with Intl.
 * min/max are wall strings "Y-m-d H:i" and are compared as strings.
 *
 * API: sx.DateTimeInput.open(root), close(), setValue(root, savedValue).
 * A change fires "change" on .sx-dt__value and .sx-dt__display; setting the
 * value from outside and firing "change" on either of them updates the field.
 */
(function (window, document) {
    'use strict';

    var $ = window.jQuery;

    var MONTHS = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    var MONTHS_GEN = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    var MONTHS_SHORT = ['янв.', 'февр.', 'мар.', 'апр.', 'мая', 'июн.', 'июл.', 'авг.', 'сент.', 'окт.', 'нояб.', 'дек.'];
    var DAYS_SHORT = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
    var DAYS = ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'];
    var WEEK = [1, 2, 3, 4, 5, 6, 0];
    var SHEET = '(max-width: 575.98px)';

    var cur = null;
    var busy = false;
    var formats = {};

    /* ---------- wall time ---------- */

    function pad(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function wall(ts, tz) {
        var f = formats[tz];
        if (!f) {
            var options = {hourCycle: 'h23', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit'};
            try {
                f = new Intl.DateTimeFormat('en-US', Object.assign({timeZone: tz}, options));
            } catch (e) {
                f = new Intl.DateTimeFormat('en-US', options);
            }
            formats[tz] = f;
        }
        var p = {};
        f.formatToParts(new Date(ts * 1000)).forEach(function (x) {
            p[x.type] = x.value;
        });
        return {y: +p.year, m: +p.month, d: +p.day, H: +p.hour % 24, i: +p.minute, s: +p.second};
    }

    function offset(ts, tz) {
        var w = wall(ts, tz);
        return Date.UTC(w.y, w.m - 1, w.d, w.H, w.i, w.s) / 1000 - ts;
    }

    function toTs(w, tz) {
        var guess = Date.UTC(w.y, w.m - 1, w.d, w.H || 0, w.i || 0, 0) / 1000;
        var o = offset(guess, tz);
        var ts = guess - o;
        var o2 = offset(ts, tz);
        return o2 === o ? ts : guess - o2;
    }

    function dayNum(w) {
        return Math.floor(Date.UTC(w.y, w.m - 1, w.d) / 86400000);
    }

    function fromNum(n) {
        var x = new Date(n * 86400000);
        return {y: x.getUTCFullYear(), m: x.getUTCMonth() + 1, d: x.getUTCDate()};
    }

    function dow(w) {
        return new Date(Date.UTC(w.y, w.m - 1, w.d)).getUTCDay();
    }

    function addDays(w, n) {
        return fromNum(dayNum(w) + n);
    }

    function dayKey(w) {
        return w.y + '-' + pad(w.m) + '-' + pad(w.d);
    }

    function timeKey(w) {
        return dayKey(w) + ' ' + pad(w.H || 0) + ':' + pad(w.i || 0);
    }

    function now(cfg) {
        return wall(Math.floor(Date.now() / 1000), cfg.tz);
    }

    function withTime(day, H, i) {
        return {y: day.y, m: day.m, d: day.d, H: H, i: i, s: 0};
    }

    /* ---------- formats ---------- */

    function format(w, pattern, cfg) {
        var out = '';
        for (var k = 0; k < pattern.length; k++) {
            var c = pattern.charAt(k);
            switch (c) {
                case '\\': out += pattern.charAt(++k); break;
                case 'd': out += pad(w.d); break;
                case 'j': out += w.d; break;
                case 'm': out += pad(w.m); break;
                case 'n': out += w.m; break;
                case 'Y': out += w.y; break;
                case 'y': out += pad(w.y % 100); break;
                case 'H': out += pad(w.H || 0); break;
                case 'G': out += (w.H || 0); break;
                case 'i': out += pad(w.i || 0); break;
                case 's': out += pad(w.s || 0); break;
                case 'U': out += toTs(w, cfg.tz); break;
                case 'D': out += DAYS_SHORT[dow(w)]; break;
                case 'M': out += MONTHS_SHORT[w.m - 1]; break;
                default: out += c;
            }
        }
        return out;
    }

    function parse(value, cfg) {
        value = String(value == null ? '' : value).trim();
        if (!value || /^0000-00-00/.test(value)) {
            return null;
        }
        if (cfg.save !== 'U') {
            var re = '', order = [];
            for (var k = 0; k < cfg.save.length; k++) {
                var c = cfg.save.charAt(k);
                if (c === '\\') {
                    re += cfg.save.charAt(++k).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                } else if ('djmnYyHGis'.indexOf(c) !== -1) {
                    re += c === 'Y' ? '(\\d{4})' : (c === 'y' ? '(\\d{2})' : '(\\d{1,2})');
                    order.push(c);
                } else {
                    re += c.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                }
            }
            var match = new RegExp('^' + re + '$').exec(value);
            if (match) {
                var w = {y: 1970, m: 1, d: 1, H: 0, i: 0, s: 0};
                order.forEach(function (t, n) {
                    var v = +match[n + 1];
                    if (t === 'd' || t === 'j') { w.d = v; }
                    if (t === 'm' || t === 'n') { w.m = v; }
                    if (t === 'Y') { w.y = v; }
                    if (t === 'y') { w.y = 2000 + v; }
                    if (t === 'H' || t === 'G') { w.H = v; }
                    if (t === 'i') { w.i = v; }
                    if (t === 's') { w.s = v; }
                });
                return w;
            }
        }
        return /^-?\d+$/.test(value) ? wall(+value, cfg.tz) : null;
    }

    function label(w, cfg) {
        if (cfg.display !== 'human') {
            return format(w, cfg.display, cfg);
        }
        var t = now(cfg);
        var diff = dayNum(w) - dayNum(t);
        var text = diff === 0 ? 'Сегодня' : (diff === 1 ? 'Завтра' : (diff === -1 ? 'Вчера'
            : DAYS_SHORT[dow(w)] + ', ' + w.d + ' ' + MONTHS_SHORT[w.m - 1] + (w.y !== t.y ? ' ' + w.y : '')));
        return cfg.type === 'datetime' ? text + ', ' + pad(w.H || 0) + ':' + pad(w.i || 0) : text;
    }

    function shortDay(w) {
        return DAYS_SHORT[dow(w)] + ', ' + w.d + ' ' + MONTHS_SHORT[w.m - 1];
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    /* ---------- limits and defaults ---------- */

    function dayAllowed(w, cfg) {
        var k = dayKey(w);
        return !(cfg.min && k < cfg.min.slice(0, 10)) && !(cfg.max && k > cfg.max.slice(0, 10));
    }

    function valueAllowed(w, cfg) {
        if (cfg.type === 'date') {
            return dayAllowed(w, cfg);
        }
        var k = timeKey(w);
        return !(cfg.min && k < cfg.min) && !(cfg.max && k > cfg.max);
    }

    /** The time of a day chosen without one: the default, not earlier than now (today) and min. */
    function timeFor(day, cfg) {
        var p = String(cfg.defaultTime || '09:00').split(':');
        var minutes = (+p[0] || 0) * 60 + (+p[1] || 0);
        var t = now(cfg);
        var floor = -1;
        if (dayKey(day) === dayKey(t)) {
            floor = t.H * 60 + t.i;
        }
        if (cfg.min && cfg.min.slice(0, 10) === dayKey(day)) {
            floor = Math.max(floor, +cfg.min.slice(11, 13) * 60 + +cfg.min.slice(14, 16) - 1);
        }
        if (minutes <= floor) {
            minutes = Math.min(Math.ceil((floor + 1) / cfg.step) * cfg.step, 1440 - cfg.step);
        }
        return {H: Math.floor(minutes / 60), i: minutes % 60};
    }

    function quickDate(option, cfg) {
        var t = now(cfg);
        switch (option.key) {
            case 'today': return {y: t.y, m: t.m, d: t.d};
            case 'tomorrow': return addDays(t, 1);
            case 'weekEnd': return addDays(t, (5 - dow(t) + 7) % 7);
            case 'nextWeek': return addDays(t, 7);
            case 'monthEnd': return fromNum(Math.floor(Date.UTC(t.y, t.m, 0) / 86400000));
            default: return addDays(t, option.days || 0);
        }
    }

    /* ---------- the field ---------- */

    function cfgOf(root) {
        if (!root._sxDt) {
            root._sxDt = JSON.parse(root.getAttribute('data-sx-datetime'));
        }
        return root._sxDt;
    }

    function part(root, name) {
        return root.querySelector('.sx-dt__' + name);
    }

    function fire(el) {
        if (!el) {
            return;
        }
        if ($) {
            $(el).trigger('change');
        } else {
            el.dispatchEvent(new Event('change', {bubbles: true}));
        }
    }

    function render(root, w) {
        var cfg = cfgOf(root);
        var text = w ? label(w, cfg) : '';
        part(root, 'label').textContent = text || cfg.placeholder;
        part(root, 'display').value = text;
        root.classList.toggle('is-empty', !w);
    }

    function setValue(root, w) {
        var cfg = cfgOf(root);
        var input = part(root, 'value');
        if (w && cfg.type === 'date') {
            w = withTime(w, 0, 0);
        }
        var value = w ? format(w, cfg.save, cfg) : '';
        render(root, w);
        if (input.value !== value) {
            input.value = value;
            busy = true;
            try {
                fire(input);
                fire(part(root, 'display'));
            } finally {
                busy = false;
            }
        }
    }

    function onExternalChange(e) {
        if (busy) {
            return;
        }
        var el = e.target;
        var root = el.closest && el.closest('[data-sx-datetime]');
        if (!root) {
            return;
        }
        if (el.classList.contains('sx-dt__value')) {
            render(root, parse(el.value, cfgOf(root)));
            if (cur && cur.root === root) {
                close(false);
            }
        } else if (el.classList.contains('sx-dt__display') && !el.value && part(root, 'value').value) {
            setValue(root, null);
        }
    }

    /* ---------- the window ---------- */

    function open(root) {
        if (cur && cur.root === root) {
            return;
        }
        close(false);

        var cfg = cfgOf(root);
        var w = parse(part(root, 'value').value, cfg);
        var t = now(cfg);
        var start = w || t;
        var sheet = window.matchMedia && window.matchMedia(SHEET).matches;

        var pop = document.createElement('div');
        pop.className = 'sx-dt-pop' + (sheet ? ' is-sheet' : '') + (cfg.type === 'date' ? ' is-date' : '');
        pop.id = root.id + '-pop';
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-modal', sheet ? 'true' : 'false');
        pop.setAttribute('aria-label', cfg.type === 'date' ? 'Выбор даты' : 'Выбор даты и времени');

        var host = root.closest('.modal') || document.body;
        var backdrop = null;
        if (sheet) {
            backdrop = document.createElement('div');
            backdrop.className = 'sx-dt-backdrop';
            host.appendChild(backdrop);
        }
        host.appendChild(pop);

        cur = {
            root: root,
            cfg: cfg,
            pop: pop,
            backdrop: backdrop,
            sheet: sheet,
            draft: w,
            dirty: false,
            view: 'days',
            month: {y: start.y, m: start.m},
            focus: dayKey(start)
        };

        var trigger = part(root, 'trigger');
        trigger.setAttribute('aria-expanded', 'true');
        trigger.setAttribute('aria-controls', pop.id);
        root.classList.add('is-open');

        pop.addEventListener('click', onPopClick);
        pop.addEventListener('keydown', onPopKey);
        document.addEventListener('pointerdown', onOutside, true);
        window.addEventListener('resize', place);
        window.addEventListener('scroll', place, true);

        draw('[data-day="' + cur.focus + '"]');
    }

    function close(focusTrigger) {
        if (!cur) {
            return;
        }
        var c = cur;
        cur = null;
        document.removeEventListener('pointerdown', onOutside, true);
        window.removeEventListener('resize', place);
        window.removeEventListener('scroll', place, true);
        c.pop.remove();
        if (c.backdrop) {
            c.backdrop.remove();
        }
        var trigger = part(c.root, 'trigger');
        trigger.setAttribute('aria-expanded', 'false');
        c.root.classList.remove('is-open');
        if (focusTrigger && document.contains(trigger)) {
            trigger.focus();
        }
    }

    /** Applies the chosen value (datetime: the draft) and closes. */
    function commit(w, focusTrigger) {
        var root = cur.root;
        close(focusTrigger);
        setValue(root, w);
    }

    function place() {
        if (!cur || cur.sheet) {
            return;
        }
        var pop = cur.pop;
        var r = part(cur.root, 'trigger').getBoundingClientRect();
        var vw = document.documentElement.clientWidth;
        var vh = window.innerHeight;
        var left = Math.max(8, Math.min(r.left, vw - pop.offsetWidth - 8));
        var top = r.bottom + 6;
        if (top + pop.offsetHeight > vh - 8) {
            top = r.top - pop.offsetHeight - 6 >= 8 ? r.top - pop.offsetHeight - 6 : Math.max(8, vh - pop.offsetHeight - 8);
        }
        pop.style.left = left + 'px';
        pop.style.top = top + 'px';
    }

    function icon(name) {
        var paths = {
            prev: '<path d="M15 6l-6 6 6 6"/>',
            next: '<path d="M9 6l6 6-6 6"/>',
            clock: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>'
        };
        return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + paths[name] + '</svg>';
    }

    function drawDays() {
        var cfg = cur.cfg;
        var t = now(cfg);
        var first = {y: cur.month.y, m: cur.month.m, d: 1};
        var title = MONTHS[first.m - 1] + ' ' + first.y;
        var start = dayNum(first) - (dow(first) + 6) % 7;
        var selected = cur.draft ? dayKey(cur.draft) : '';
        var focusInMonth = cur.focus.slice(0, 7) === first.y + '-' + pad(first.m);
        var html = '<div class="sx-dt-pop__head">'
            + '<button type="button" class="sx-dt-pop__nav" data-act="prev" aria-label="Предыдущий месяц">' + icon('prev') + '</button>'
            + '<div class="sx-dt-pop__title" aria-live="polite">' + title + '</div>'
            + '<button type="button" class="sx-dt-pop__nav" data-act="next" aria-label="Следующий месяц">' + icon('next') + '</button>'
            + '</div><div class="sx-dt-pop__days" role="grid" aria-label="' + title + '"><div class="sx-dt-pop__row" role="row">';
        WEEK.forEach(function (d) {
            html += '<span class="sx-dt-pop__wd' + (d === 0 || d === 6 ? ' is-weekend' : '') + '" role="columnheader" title="' + DAYS[d] + '">' + DAYS_SHORT[d] + '</span>';
        });
        html += '</div>';
        for (var n = 0; n < 42; n++) {
            var w = fromNum(start + n);
            var k = dayKey(w);
            var wd = dow(w);
            if (n % 7 === 0) {
                if (n >= 35 && w.m !== first.m) {
                    break;
                }
                html += (n ? '</div>' : '') + '<div class="sx-dt-pop__row" role="row">';
            }
            var classes = 'sx-dt-pop__day'
                + (w.m !== first.m ? ' is-other' : '')
                + (wd === 0 || wd === 6 ? ' is-weekend' : '')
                + (k === dayKey(t) ? ' is-today' : '')
                + (k === selected ? ' is-selected' : '');
            var focusable = focusInMonth ? k === cur.focus : (w.m === first.m && w.d === 1);
            html += '<button type="button" role="gridcell" class="' + classes + '" data-day="' + k + '"'
                + ' tabindex="' + (focusable ? 0 : -1) + '"'
                + ' aria-label="' + DAYS[wd] + ', ' + w.d + ' ' + MONTHS_GEN[w.m - 1] + ' ' + w.y + '"'
                + (k === selected ? ' aria-selected="true"' : '')
                + (k === dayKey(t) ? ' aria-current="date"' : '')
                + (dayAllowed(w, cfg) ? '' : ' aria-disabled="true"')
                + '>' + w.d + '</button>';
        }
        return html + '</div></div>';
    }

    function drawTime() {
        var cfg = cur.cfg;
        var d = cur.draft || withTime(fromDayKey(cur.focus), null, null);
        var html = '<div class="sx-dt-pop__head">'
            + '<button type="button" class="sx-dt-pop__nav" data-act="days" aria-label="Назад к календарю">' + icon('prev') + '</button>'
            + '<div class="sx-dt-pop__title">' + escape(label(withTime(d, d.H || 0, d.i || 0), {type: 'date', display: 'human', tz: cfg.tz})) + '</div>'
            + '<span class="sx-dt-pop__nav-space"></span></div>'
            + '<div class="sx-dt-pop__caption" id="' + cur.pop.id + '-h">Часы</div>'
            + '<div class="sx-dt-pop__grid sx-dt-pop__hours" role="group" aria-labelledby="' + cur.pop.id + '-h">';
        for (var H = 0; H < 24; H++) {
            var hourOk = valueAllowed(withTime(d, H, 59), cfg) || valueAllowed(withTime(d, H, 0), cfg);
            html += '<button type="button" class="sx-dt-pop__cell' + (d.H === H ? ' is-selected' : '') + '" data-hour="' + H + '"'
                + ' tabindex="-1"' + (d.H === H ? ' aria-pressed="true"' : ' aria-pressed="false"') + (hourOk ? '' : ' aria-disabled="true"') + '>' + pad(H) + '</button>';
        }
        html += '</div><div class="sx-dt-pop__caption" id="' + cur.pop.id + '-m">Минуты</div>'
            + '<div class="sx-dt-pop__grid sx-dt-pop__minutes" role="group" aria-labelledby="' + cur.pop.id + '-m">';
        for (var i = 0; i < 60; i += cfg.step) {
            var ok = d.H == null || valueAllowed(withTime(d, d.H, i), cfg);
            html += '<button type="button" class="sx-dt-pop__cell' + (d.i === i && d.H != null ? ' is-selected' : '') + '" data-minute="' + i + '"'
                + ' tabindex="-1"' + (d.i === i ? ' aria-pressed="true"' : ' aria-pressed="false"') + (ok ? '' : ' aria-disabled="true"') + '>' + pad(i) + '</button>';
        }
        return html + '</div>';
    }

    function fromDayKey(k) {
        return {y: +k.slice(0, 4), m: +k.slice(5, 7), d: +k.slice(8, 10)};
    }

    function drawSide() {
        var cfg = cur.cfg;
        if (!cfg.quick.length) {
            return '';
        }
        var html = '<div class="sx-dt-pop__side" role="group" aria-label="Быстрый выбор">';
        cfg.quick.forEach(function (option, n) {
            var day = quickDate(option, cfg);
            var ok = dayAllowed(day, cfg);
            var selected = cur.draft && dayKey(cur.draft) === dayKey(day);
            html += '<button type="button" class="sx-dt-pop__quick' + (selected ? ' is-selected' : '') + '" data-quick="' + n + '"' + (ok ? '' : ' aria-disabled="true"') + '>'
                + '<span class="sx-dt-pop__quick-label">' + escape(option.label) + '</span>'
                + '<span class="sx-dt-pop__quick-day">' + shortDay(day) + '</span></button>';
        });
        return html + '</div>';
    }

    function drawFoot() {
        var cfg = cur.cfg;
        var html = '<div class="sx-dt-pop__foot">';
        if (cfg.type === 'datetime') {
            var time = cur.draft && cur.draft.H != null ? pad(cur.draft.H) + ':' + pad(cur.draft.i || 0) : 'Время';
            html += '<button type="button" class="sx-dt-pop__time' + (cur.view === 'time' ? ' is-active' : '') + '" data-act="time" aria-label="Время: ' + time + '">' + icon('clock') + '<span>' + time + '</span></button>';
        }
        html += '<span class="sx-dt-pop__spacer"></span>';
        if (cfg.clear) {
            html += '<button type="button" class="sx-dt-pop__link" data-act="clear">Очистить</button>';
        }
        if (cfg.type === 'datetime') {
            html += '<button type="button" class="sx-dt-pop__done" data-act="done"' + (cur.draft ? '' : ' disabled') + '>Готово</button>';
        }
        return html + '</div>';
    }

    function draw(focusSelector) {
        var pop = cur.pop;
        pop.innerHTML = '<div class="sx-dt-pop__body"><div class="sx-dt-pop__main">'
            + (cur.view === 'time' ? drawTime() : drawDays()) + '</div>' + drawSide() + '</div>' + drawFoot();
        place();
        var target = focusSelector && pop.querySelector(focusSelector);
        if (target) {
            target.focus({preventScroll: true});
        }
    }

    /** Puts a day into the draft; datetime keeps the chosen time or takes the default one. */
    function pickDay(day) {
        var cfg = cur.cfg;
        var time = cur.draft && cur.draft.H != null ? cur.draft : timeFor(day, cfg);
        var w = withTime(day, time.H, time.i);
        if (cfg.type === 'datetime' && !valueAllowed(w, cfg)) {
            time = timeFor(day, cfg);
            w = withTime(day, time.H, time.i);
        }
        return w;
    }

    function onPopClick(e) {
        var btn = e.target.closest('button');
        if (!btn || !cur || btn.getAttribute('aria-disabled') === 'true' || btn.disabled) {
            return;
        }
        var cfg = cur.cfg;
        var act = btn.getAttribute('data-act');

        if (act === 'prev' || act === 'next') {
            var m = cur.month.m + (act === 'prev' ? -1 : 1);
            cur.month = {y: cur.month.y + (m < 1 ? -1 : (m > 12 ? 1 : 0)), m: (m + 11) % 12 + 1};
            draw('[data-act="' + act + '"]');
        } else if (act === 'time') {
            cur.view = cur.view === 'time' ? 'days' : 'time';
            draw(cur.view === 'time' ? '.sx-dt-pop__hours .is-selected, .sx-dt-pop__hours [data-hour="9"]' : '[data-day="' + cur.focus + '"]');
            fixTimeFocus();
        } else if (act === 'days') {
            cur.view = 'days';
            draw('[data-day="' + cur.focus + '"]');
        } else if (act === 'clear') {
            commit(null, true);
        } else if (act === 'done') {
            commit(cur.draft, true);
        } else if (btn.hasAttribute('data-day')) {
            var day = fromDayKey(btn.getAttribute('data-day'));
            var again = cur.draft && dayKey(cur.draft) === dayKey(day);
            cur.focus = dayKey(day);
            cur.draft = pickDay(day);
            cur.dirty = true;
            if (cfg.type === 'date' || (again && e.detail === 0)) {
                commit(cur.draft, true);
                return;
            }
            cur.month = {y: day.y, m: day.m};
            draw('[data-day="' + cur.focus + '"]');
        } else if (btn.hasAttribute('data-quick')) {
            var option = cfg.quick[+btn.getAttribute('data-quick')];
            commit(pickDay(quickDate(option, cfg)), true);
        } else if (btn.hasAttribute('data-hour')) {
            var base = cur.draft || withTime(fromDayKey(cur.focus), 0, 0);
            var H = +btn.getAttribute('data-hour');
            var minute = base.H != null ? base.i : 0;
            cur.draft = withTime(base, H, minute);
            cur.dirty = true;
            if (!valueAllowed(cur.draft, cfg)) {
                for (var i = 0; i < 60 && !valueAllowed(cur.draft, cfg); i += cfg.step) {
                    cur.draft = withTime(base, H, i);
                }
            }
            draw('.sx-dt-pop__minutes .is-selected');
            fixTimeFocus('.sx-dt-pop__minutes');
        } else if (btn.hasAttribute('data-minute')) {
            var b = cur.draft || withTime(fromDayKey(cur.focus), 0, 0);
            cur.draft = withTime(b, b.H != null ? b.H : 0, +btn.getAttribute('data-minute'));
            cur.dirty = true;
            cur.view = 'days';
            cur.focus = dayKey(cur.draft);
            draw('[data-act="done"]');
        }
    }

    /** Roving focus of a time grid: the selected cell or the first allowed one. */
    function fixTimeFocus(scope) {
        if (!cur || cur.view !== 'time') {
            return;
        }
        ['.sx-dt-pop__hours', '.sx-dt-pop__minutes'].forEach(function (grid) {
            var cells = cur.pop.querySelectorAll(grid + ' .sx-dt-pop__cell');
            var active = cur.pop.querySelector(grid + ' .is-selected') || cur.pop.querySelector(grid + ' .sx-dt-pop__cell:not([aria-disabled])') || cells[0];
            if (active) {
                active.tabIndex = 0;
                if (scope === grid || (!scope && grid === '.sx-dt-pop__hours' && !cur.pop.contains(document.activeElement))) {
                    active.focus({preventScroll: true});
                }
            }
        });
    }

    function moveDay(from, n) {
        var target = addDays(fromDayKey(from), n);
        cur.focus = dayKey(target);
        if (target.m !== cur.month.m || target.y !== cur.month.y) {
            cur.month = {y: target.y, m: target.m};
        }
        draw('[data-day="' + cur.focus + '"]');
    }

    function moveMonth(from, months) {
        var d = fromDayKey(from);
        var total = d.y * 12 + d.m - 1 + months;
        var y = Math.floor(total / 12), m = total % 12 + 1;
        var last = fromNum(Math.floor(Date.UTC(y, m, 0) / 86400000)).d;
        cur.month = {y: y, m: m};
        cur.focus = dayKey({y: y, m: m, d: Math.min(d.d, last)});
        draw('[data-day="' + cur.focus + '"]');
    }

    function onPopKey(e) {
        if (!cur) {
            return;
        }
        var el = e.target;
        var key = e.key;

        if (key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            close(true);
            return;
        }
        if (key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            if (cur.draft) {
                commit(cur.draft, true);
            }
            return;
        }
        if (key === 'Tab') {
            var items = Array.prototype.filter.call(cur.pop.querySelectorAll('button'), function (b) {
                return !b.disabled && b.tabIndex >= 0;
            });
            var index = items.indexOf(el);
            if (items.length && (e.shiftKey ? index <= 0 : index === items.length - 1)) {
                e.preventDefault();
                items[e.shiftKey ? items.length - 1 : 0].focus();
            }
            return;
        }

        if (el.hasAttribute('data-day')) {
            var k = el.getAttribute('data-day');
            var d = fromDayKey(k);
            var steps = {ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7};
            if (steps[key]) {
                e.preventDefault();
                moveDay(k, steps[key]);
            } else if (key === 'Home' || key === 'End') {
                e.preventDefault();
                var wd = (dow(d) + 6) % 7;
                moveDay(k, key === 'Home' ? -wd : 6 - wd);
            } else if (key === 'PageUp' || key === 'PageDown') {
                e.preventDefault();
                moveMonth(k, (key === 'PageUp' ? -1 : 1) * (e.shiftKey ? 12 : 1));
            }
            return;
        }

        var grid = el.closest('.sx-dt-pop__grid');
        if (grid) {
            var cells = Array.prototype.slice.call(grid.querySelectorAll('.sx-dt-pop__cell'));
            var cols = 6;
            var moves = {ArrowLeft: -1, ArrowRight: 1, ArrowUp: -cols, ArrowDown: cols, Home: -cells.length, End: cells.length};
            if (moves[key] !== undefined) {
                e.preventDefault();
                var to = Math.max(0, Math.min(cells.length - 1, cells.indexOf(el) + moves[key]));
                cells.forEach(function (c) {
                    c.tabIndex = -1;
                });
                cells[to].tabIndex = 0;
                cells[to].focus();
            }
        }
    }

    function onOutside(e) {
        if (!cur || cur.pop.contains(e.target) || cur.root.contains(e.target)) {
            return;
        }
        // Clicking away keeps a draft the user changed (datetime); a date is applied by its click already.
        if (cur.dirty && cur.draft) {
            commit(cur.draft, false);
        } else {
            close(false);
        }
    }

    /* ---------- delegated field events ---------- */

    document.addEventListener('click', function (e) {
        var el = e.target;
        if (!el.closest) {
            return;
        }
        var trigger = el.closest('.sx-dt__trigger');
        if (trigger) {
            var root = trigger.closest('[data-sx-datetime]');
            if (cur && cur.root === root) {
                close(false);
            } else {
                open(root);
            }
            return;
        }
        var clear = el.closest('.sx-dt__clear');
        if (clear) {
            e.preventDefault();
            var r = clear.closest('[data-sx-datetime]');
            close(false);
            setValue(r, null);
            part(r, 'trigger').focus();
            return;
        }
        var lbl = el.closest('label[for]');
        if (lbl) {
            var input = document.getElementById(lbl.htmlFor);
            if (input && input.classList.contains('sx-dt__value')) {
                e.preventDefault();
                part(input.closest('[data-sx-datetime]'), 'trigger').focus();
            }
        }
    });

    document.addEventListener('keydown', function (e) {
        var trigger = e.target.closest && e.target.closest('.sx-dt__trigger');
        if (trigger && e.key === 'ArrowDown' && !(cur && cur.root.contains(trigger))) {
            e.preventDefault();
            open(trigger.closest('[data-sx-datetime]'));
        }
    });

    if ($) {
        $(document).on('change', '.sx-dt__value, .sx-dt__display', onExternalChange);
        $(document).on('pjax:send', function () {
            close(false);
        });
    } else {
        document.addEventListener('change', onExternalChange, true);
    }

    window.sx = window.sx || {};
    window.sx.DateTimeInput = {
        open: open,
        close: function () {
            close(false);
        },
        setValue: function (root, value) {
            setValue(root, parse(value, cfgOf(root)));
        }
    };
})(window, document);

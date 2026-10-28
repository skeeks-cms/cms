(function (sx, $) {
    'use strict';

    var rootSelector = '[data-sx-lead-identity]';

    function part(root, name) {
        return root.find('[data-sx-identity-part="' + name + '"]');
    }

    function field(section, role) {
        return section.find('[data-sx-identity-field="' + role + '"]');
    }

    var badges = {
        company: {'new': 'новая', foreign: 'у другого сотрудника', kept: 'только в сделке'},
        client: {'new': 'новый', foreign: 'у другого сотрудника', kept: 'только в сделке'}
    };

    /**
     * One field per part, as in Bitrix24: "search" (typing shows the
     * dropdown), "new" (the field is the name, phone and email are open and
     * checked), "existing" / "foreign" / "kept" (a choice, ⇄ changes it).
     */
    function setState(root, name, state, title) {
        var section = part(root, name);
        var input = section.find('[data-sx-identity-search]');
        section.attr('data-state', state);
        input.prop('readonly', state !== 'search' && state !== 'new')
            .attr('placeholder', state === 'new' ? (name === 'company' ? 'Название новой компании' : 'Имя нового контакта') : input.data('placeholder'));
        if (title !== undefined) {
            input.val(title);
        }
        var badge = (badges[name] || {})[state] || '';
        section.find('[data-sx-identity-badge]').text(badge).prop('hidden', !badge);
        section.find('[data-sx-identity-clear]').prop('hidden', state === 'search');
        section.find('[data-sx-identity-new]').prop('hidden', state !== 'new');
        if (state !== 'new') {
            section.find('[data-sx-identity-check]').prop('hidden', true).empty();
        }
        closeResults(section);
        refreshPanels(root);
    }

    /** Fills the hidden fields of a part; the new record's phone and email go to their open fields. */
    function choose(root, name, values, title, state) {
        var section = part(root, name);
        if (!section.length) {
            return;
        }
        section.find('[data-sx-identity-field]').each(function () {
            var role = $(this).data('sxIdentityField');
            $(this).val(values[role] !== undefined && values[role] !== null ? values[role] : '');
        });
        section.find('[data-sx-identity-new-field]').each(function () {
            var role = $(this).data('sxIdentityNewField');
            $(this).val(values.mode === 'new' && values[role] ? values[role] : '');
        });
        setState(root, name, state || values.mode || 'search', title);
    }

    function clear(root, name) {
        var section = part(root, name);
        section.find('[data-sx-identity-field], [data-sx-identity-new-field]').val('');
        // The reason belonged to the record just dropped.
        if (root.find('[data-sx-identity-duplicate-panel]').data('part') === name) {
            resetDuplicate(root);
        }
        setState(root, name, 'search', '');
        section.find('[data-sx-identity-search]').trigger('focus');
    }

    function resetDuplicate(root) {
        var panel = root.find('[data-sx-identity-duplicate-panel]');
        panel.find('[data-sx-identity-duplicate-ref]').val('');
        panel.find('textarea').val('');
        panel.removeData('part').prop('hidden', true);
    }

    function refreshPanels(root) {
        var foreign = field(part(root, 'company'), 'mode').val() === 'foreign' || field(part(root, 'client'), 'mode').val() === 'foreign';
        root.find('[data-sx-identity-foreign-panel]').prop('hidden', !foreign);
    }

    function closeResults(section) {
        section.find('[data-sx-identity-results]').prop('hidden', true).empty();
        section.find('[data-sx-identity-search]').attr('aria-expanded', 'false');
    }

    function splitName(full) {
        var words = $.trim(full || '').split(/\s+/);
        return {first: words.shift() || '', last: words.join(' ')};
    }

    /** Values of a new company or contact from a parsed line (CrmContactParser). */
    function createValues(name, data) {
        if (name === 'company') {
            return {mode: 'new', name: data.title || data.name || '', phones: (data.phones || []).join(', '), emails: (data.emails || []).join(', ')};
        }
        var person = splitName(data.person || data.title || data.name);
        return {mode: 'new', first_name: person.first, last_name: person.last, phone: (data.phones || [])[0] || '', email: (data.emails || [])[0] || ''};
    }

    function newName(name, values) {
        return name === 'company' ? values.name : $.trim(values.first_name + ' ' + values.last_name);
    }

    /** The typed name and contacts of a new record go to the hidden fields of the form. */
    function syncNew(root, name) {
        var section = part(root, name);
        var typed = $.trim(section.find('[data-sx-identity-search]').val());
        if (name === 'company') {
            field(section, 'name').val(typed);
        } else {
            var person = splitName(typed);
            field(section, 'first_name').val(person.first);
            field(section, 'last_name').val(person.last);
        }
        section.find('[data-sx-identity-new-field]').each(function () {
            field(section, $(this).data('sxIdentityNewField')).val($.trim($(this).val()));
        });
    }

    function ajaxPart(root, section, params, target, key) {
        var request = $.ajax({
            url: root.data('matchUrl'),
            data: $.extend({lead_id: root.data('leadId') || '', foreign: root.data('allowForeign')}, params),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        section.data(key, request);
        request.done(function (html) {
            // Only the answer to the last typed text.
            if (section.data(key) !== request) {
                return;
            }
            target.html(html).prop('hidden', $.trim(html) === '');
            if (target.is('[data-sx-identity-results]')) {
                section.find('[data-sx-identity-search]').attr('aria-expanded', target.prop('hidden') ? 'false' : 'true');
            }
        });
    }

    /** The dropdown of the field. */
    function search(root, name) {
        var section = part(root, name);
        var q = $.trim(section.find('[data-sx-identity-search]').val());
        if (q.length < 2) {
            closeResults(section);
            return;
        }
        ajaxPart(root, section, {q: q, 'for': name}, section.find('[data-sx-identity-results]'), 'sxIdentitySearchRequest');
    }

    /** «Есть совпадения» of a new record: its name, phone and email against the base. */
    function check(root, name) {
        var section = part(root, name);
        var values = [section.find('[data-sx-identity-search]').val()].concat(section.find('[data-sx-identity-new-field]').map(function () {
            return $(this).val();
        }).get());
        var q = $.map(values, function (value) { return $.trim(value) || null; }).join(', ');
        var target = section.find('[data-sx-identity-check]');
        if (q.length < 2) {
            target.prop('hidden', true).empty();
            return;
        }
        ajaxPart(root, section, {q: q, 'for': name, check: 1}, target, 'sxIdentityCheckRequest');
    }

    function init(element) {
        var root = $(element);
        if (root.data('sxIdentityReady')) {
            return;
        }
        root.data('sxIdentityReady', true);

        var timers = {};
        var later = function (key, callback, delay) {
            clearTimeout(timers[key]);
            timers[key] = setTimeout(callback, delay);
        };
        var partName = function (element) {
            return $(element).closest('[data-sx-identity-part]').data('sxIdentityPart');
        };

        root.on('input', '[data-sx-identity-search]', function () {
            var name = partName(this);
            if (part(root, name).attr('data-state') === 'new') {
                syncNew(root, name);
                later(name, function () { check(root, name); }, 450);
            } else {
                later(name, function () { search(root, name); }, 300);
            }
        });
        root.on('input', '[data-sx-identity-new-field]', function () {
            var name = partName(this);
            syncNew(root, name);
            later(name, function () { check(root, name); }, 450);
        });
        // A pasted line («Ромашка, +7 …, info@…») is searched at once.
        root.on('paste', '[data-sx-identity-search]', function () {
            var name = partName(this);
            if (part(root, name).attr('data-state') === 'search') {
                later(name, function () { search(root, name); }, 0);
            }
        });
        root.on('focus', '[data-sx-identity-search]', function () {
            var name = partName(this);
            if (part(root, name).attr('data-state') === 'search' && $.trim($(this).val()).length >= 2) {
                search(root, name);
            }
        });
        root.on('keydown', '[data-sx-identity-search], [data-sx-identity-new-field]', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                // Enter takes the first row: a found record or «создать».
                var first = part(root, partName(this)).find('[data-sx-identity-results] [role="option"]').first();
                if (first.length) {
                    first.trigger('click');
                }
            } else if (event.key === 'Escape') {
                closeResults(part(root, partName(this)));
            }
        });
        // The dropdown closes on a click outside its field.
        $(document).on('mousedown', function (event) {
            root.find('[data-sx-identity-box]').each(function () {
                if (!$.contains(this, event.target)) {
                    closeResults($(this).closest('[data-sx-identity-part]'));
                }
            });
        });
        root.on('click', '[data-sx-identity-check-close]', function () {
            $(this).closest('[data-sx-identity-check]').prop('hidden', true);
        });

        // An own record: found in the dropdown or «Использовать» instead of the new one.
        root.on('click', '[data-sx-identity-pick]', function () {
            var button = $(this);
            var name = button.data('sxIdentityPick') === 'company' ? 'company' : 'client';
            if (root.find('[data-sx-identity-duplicate-panel]').data('part') === name) {
                resetDuplicate(root);
            }
            choose(root, name, {mode: 'existing', id: button.data('id')}, button.data('title'));
            var companies = button.data('companies') || [];
            if (name === 'client' && companies.length === 1 && part(root, 'company').attr('data-state') === 'search') {
                choose(root, 'company', {mode: 'existing', id: companies[0].id}, companies[0].name);
            }
        });

        // «Работать с этим клиентом»: the deal will be the employee's, the main responsible is notified.
        root.on('click', '[data-sx-identity-foreign]', function () {
            var button = $(this);
            var name = button.data('sxIdentityForeign') === 'company' ? 'company' : 'client';
            if (root.find('[data-sx-identity-duplicate-panel]').data('part') === name) {
                resetDuplicate(root);
            }
            choose(root, name, {mode: 'foreign', ref: button.data('ref')},
                button.data('title') + (button.data('responsible') ? ' — ответственный ' + button.data('responsible') : ''));
        });

        // «+ создать …» from the typed line: the field becomes the name, phone and email open.
        root.on('click', '[data-sx-identity-create]', function () {
            var button = $(this);
            var kind = button.data('sxIdentityCreate');
            var data = button.data('create') || {};
            if (kind === 'lead-contacts') {
                var kept = {mode: '', phone: (data.phones || [])[0] || '', email: (data.emails || [])[0] || ''};
                choose(root, 'client', kept, $.map([kept.phone, kept.email], function (value) { return value || null; }).join(', '), 'kept');
                return;
            }
            var name = kind === 'client' ? 'client' : 'company';
            var created = createValues(name, data);
            choose(root, name, created, newName(name, created));
            if (kind === 'company-and-client' && part(root, 'client').length) {
                // The phones and emails of the line go to the company; the contact gets the name.
                var person = createValues('client', {person: data.person});
                choose(root, 'client', person, newName('client', person));
            }
            var section = part(root, name);
            (created.name || created.first_name ? section.find('[data-sx-identity-new-field]').first() : section.find('[data-sx-identity-search]')).trigger('focus');
            check(root, name);
        });

        // "Это другая компания": the new record is saved with a reason and marked as a possible duplicate.
        root.on('click', '[data-sx-identity-duplicate]', function () {
            var button = $(this);
            var name = button.data('sxIdentityDuplicate') === 'company' ? 'company' : 'client';
            var panel = root.find('[data-sx-identity-duplicate-panel]');
            panel.find('[data-sx-identity-duplicate-ref]').val(button.data('ref'));
            panel.find('[data-sx-identity-duplicate-title]').text(
                (name === 'company' ? 'Новая компания — не «' : 'Новый контакт — не «') + button.data('title') + '». '
                + (root.data('reasonRequired') ? 'Напишите, почему это другая запись.' : 'Можно написать, почему это другая запись.')
            );
            panel.data('part', name).prop('hidden', false);
            // From the dropdown the new record is made from the typed line; from the check it already exists.
            if (button.data('create') && part(root, name).attr('data-state') !== 'new') {
                var created = createValues(name, button.data('create'));
                choose(root, name, created, newName(name, created));
            }
            part(root, name).find('[data-sx-identity-check]').prop('hidden', true);
            panel.find('textarea').trigger('focus');
        });

        root.on('click', '[data-sx-identity-clear]', function () {
            clear(root, partName(this));
        });

        // After a validation error the form comes back with the choice already made (the title is rendered).
        root.find('[data-sx-identity-part]').each(function () {
            var section = $(this);
            var name = section.data('sxIdentityPart');
            var mode = field(section, 'mode').val();
            var kept = !mode && name === 'client' && (field(section, 'phone').val() || field(section, 'email').val());
            setState(root, name, mode || (kept ? 'kept' : 'search'));
            if (mode === 'new') {
                check(root, name);
            }
        });
        if ($.trim(root.find('[data-sx-identity-duplicate-ref]').val())) {
            root.find('[data-sx-identity-duplicate-panel]').prop('hidden', false);
        }
        refreshPanels(root);
    }

    /**
     * Duplicate check while a company or a contact is filled in: the usual
     * fields are watched, matches appear under them by themselves. A line
     * pasted into the name («Ромашка, +7 …, info@…») is split into the fields.
     */
    function dupcheckFields(box) {
        var form = box.closest('form');
        var watch = box.data('watch') || {};
        var value = function (selector) {
            return form.find(selector).map(function () { return $.trim($(this).val()); }).get().filter(Boolean).join(' ');
        };
        return {form: form, name: value(watch.name), phones: value(watch.phones), emails: value(watch.emails), watch: watch};
    }

    function dupcheckRun(box, splitPasted) {
        var fields = dupcheckFields(box);
        var q = [fields.name, fields.phones, fields.emails].filter(Boolean).join(', ');
        var results = box.find('[data-sx-dupcheck-results]');
        if (q.length < 2) {
            results.prop('hidden', true).empty();
            return;
        }
        var request = $.ajax({url: box.data('matchUrl'), data: {q: q, 'for': box.data('sxDupcheck')}, headers: {'X-Requested-With': 'XMLHttpRequest'}});
        box.data('sxDupcheckRequest', request);
        request.done(function (html) {
            if (box.data('sxDupcheckRequest') !== request) {
                return;
            }
            var list = $('<div>').html(html);
            if (splitPasted) {
                dupcheckSplit(box, fields, list.find('[data-sx-dupcheck-parsed]').data('create') || {});
            }
            var found = list.find('.sx-lead-results__item').length > 0;
            results.html(found ? html : '').prop('hidden', !found);
            // A strong match (phone, email, INN): the company is saved only with a reason.
            box.find('[data-sx-dupcheck-reason]').prop('hidden', !list.find('[data-sx-dupcheck-strong]').length && !$.trim(box.find('[data-sx-dupcheck-reason] textarea').val()));
        });
    }

    /** The parts of a pasted line go to their fields (only empty ones, never over typed values). */
    function dupcheckSplit(box, fields, data) {
        // The field the line was pasted into is cleared first if the line has no name for it.
        fields.form.find(fields.watch.name).each(function () {
            if (/[,;\t\n]/.test($(this).val())) {
                $(this).val('');
            }
        });
        var form = fields.form;
        var set = function (selector, value) {
            var input = form.find(selector).first();
            // Only empty fields: typed values are never overwritten.
            if (value && input.length && !$.trim(input.val())) {
                input.val(value).trigger('change');
            }
        };
        if (box.data('sxDupcheck') === 'company') {
            set('[name="CmsCompany[name]"]', data.title || data.name);
            set('[data-sx-dupcheck-phones]', (data.phones || []).join(', '));
            set('[data-sx-dupcheck-emails]', (data.emails || []).join(', '));
        } else {
            var person = splitName(data.person || data.title || data.name);
            set('[name="CmsUser[first_name]"]', person.first);
            set('[name="CmsUser[last_name]"]', person.last);
            set('[name="CmsUser[phone]"]', (data.phones || [])[0]);
            set('[name="CmsUser[email]"]', (data.emails || [])[0]);
        }
    }

    function dupcheckInit(element) {
        var box = $(element);
        if (box.data('sxDupcheckReady')) {
            return;
        }
        box.data('sxDupcheckReady', true);
        var form = box.closest('form');
        var watch = box.data('watch') || {};
        var selector = [watch.name, watch.phones, watch.emails].filter(Boolean).join(', ');
        var timer = null;
        form.on('input change', selector, function () {
            clearTimeout(timer);
            timer = setTimeout(function () { dupcheckRun(box, false); }, 450);
        });
        form.on('paste', selector, function (event) {
            var text = ((event.originalEvent || event).clipboardData || window.clipboardData || {getData: function () { return ''; }}).getData('text');
            // A whole line (with separators) pasted into a field: split it into the fields.
            var whole = /[,;\t\n]/.test(text || '');
            setTimeout(function () { dupcheckRun(box, whole); }, 0);
        });
    }

    /**
     * Forms of sorting and of the deal form post by AJAX. The clicked button
     * may carry op/target; on success a backend window is closed (the opener
     * reloads), a page follows data.redirect.
     */
    $(document).on('click', '[data-sx-lead-ajax-form] [type="submit"]', function () {
        $(this).closest('form').data('sxSubmitter', this);
    });
    $(document).on('submit', '[data-sx-lead-ajax-form]', function (event) {
        event.preventDefault();
        var form = $(this);
        if (form.hasClass('is-busy')) {
            return false;
        }
        var submitter = form.data('sxSubmitter');
        form.removeData('sxSubmitter');
        if (submitter && submitter.dataset.confirm && !window.confirm(submitter.dataset.confirm)) {
            return false;
        }
        var data = form.serializeArray();
        if (submitter && submitter.name) {
            data.push({name: submitter.name, value: submitter.value});
        }
        form.addClass('is-busy');
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: $.param(data),
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (!response || !response.success) {
                sx.notify.error(response && response.message ? response.message : 'Не удалось сохранить.');
                return;
            }
            sx.notify.success(response.message || 'Готово');
            if (sx.Window && sx.Window._openerWindowWidget) {
                sx.Window.openerWidgetTriggerEvent('model-update', {submitBtn: 'save'});
                return;
            }
            if (response.data && response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
                window.location.reload();
            }
        }).fail(function () {
            sx.notify.error('Не удалось сохранить. Обновите страницу и попробуйте ещё раз.');
        }).always(function () {
            form.removeClass('is-busy');
        });
        return false;
    });

    // "Добавить в карточку" of a contact of the request in the deal card.
    $(document).on('click', '[data-sx-lead-copy-contact]', function () {
        var button = $(this);
        var data = {kind: button.data('kind'), id: button.data('id'), target: button.data('target')};
        data[yii.getCsrfParam()] = yii.getCsrfToken();
        $.ajax({url: button.data('url'), method: 'POST', data: data, dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (response && response.success) {
                sx.notify.success(response.message);
                button.prop('disabled', true).text('Добавлен');
            } else {
                sx.notify.error(response && response.message ? response.message : 'Не удалось добавить контакт.');
            }
        });
        return false;
    });

    /**
     * «Работать с этим клиентом» sent at once: a create form of a company or a
     * client (the deal is created for the employee) or a client «Доступ только
     * по согласованию» in the deal form (only the request goes).
     */
    $(document).on('click', '[data-sx-identity-work]', function () {
        var button = $(this);
        if (!window.confirm(button.data('sxConfirm'))) {
            return false;
        }
        var comment = window.prompt('Комментарий для основного ответственного (необязательно)', '');
        if (comment === null) {
            return false;
        }
        var actions = button.closest('.sx-lead-hint__actions, .sx-lead-drop__actions, .sx-lead-check__actions');
        var data = {ref: button.data('ref'), comment: comment};
        data[yii.getCsrfParam()] = yii.getCsrfToken();
        actions.find('button').prop('disabled', true);
        $.ajax({url: button.data('url'), method: 'POST', data: data, dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (response && response.success) {
                sx.notify.success(response.message);
                var note = $('<span class="sx-lead-hint__meta">').text(response.message);
                if (response.data && response.data.url) {
                    note.append(' ', $('<a target="_blank" data-pjax="0">').attr('href', response.data.url).text('Открыть сделку'));
                }
                actions.empty().append(note);
            } else {
                sx.notify.error(response && response.message ? response.message : 'Не удалось отправить.');
                actions.find('button').prop('disabled', false);
            }
        }).fail(function () {
            sx.notify.error('Не удалось отправить. Обновите страницу и попробуйте ещё раз.');
            actions.find('button').prop('disabled', false);
        });
        return false;
    });

    // «Доступ к клиенту»: a level, removing an employee, «только по согласованию».
    $(document).on('change click', '[data-sx-crm-access]', function (event) {
        var control = $(this);
        var op = control.data('sxCrmAccess');
        // Buttons act on click (remove, add), the level select and the flag on change.
        if ((op === 'remove' || op === 'add' || op === 'pool' || op === 'take') !== (event.type === 'click')) {
            return;
        }
        if ((op === 'remove' || op === 'pool' || op === 'take') && !window.confirm(control.data('sxConfirm'))) {
            return false;
        }
        var data = {op: op, user_id: control.data('userId') || '', level: op === 'level' ? control.val() : '',
            value: op === 'approval' ? (control.prop('checked') ? 1 : 0) : ''};
        if (op === 'add') {
            data.user_id = $(control.data('userInput')).val() || '';
            data.level = $(control.data('levelInput')).val() || '';
        }
        if (op === 'protection') {
            data.value = control.val();
        }
        data[yii.getCsrfParam()] = yii.getCsrfToken();
        $.ajax({url: control.data('url'), method: 'POST', data: data, dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (response && response.success) {
                sx.notify.success(response.message);
                if (op === 'remove') {
                    control.closest('li').remove();
                }
                if (op === 'add' || op === 'pool' || op === 'protection' || op === 'take') {
                    window.location.reload();
                }
            } else {
                sx.notify.error(response && response.message ? response.message : 'Не удалось изменить доступ.');
            }
        });
        return op === 'approval' ? true : false;
    });

    // A request to me: approve, decline, or cancel my own one.
    $(document).on('click', '[data-sx-crm-request-decide]', function () {
        var button = $(this);
        var decision = button.data('sxCrmRequestDecide');
        var comment = '';
        if (decision === 'decline') {
            comment = window.prompt('Почему отклонить? (необязательно)', '');
            if (comment === null) {
                return false;
            }
        } else if (!window.confirm(button.data('sxConfirm') || button.data('confirm') || 'Подтвердить?')) {
            return false;
        }
        var level = button.closest('.sx-lead-hint__actions').find('[data-sx-crm-request-level]').val() || '';
        var data = {id: button.data('id'), decision: decision, comment: comment, level: level};
        data[yii.getCsrfParam()] = yii.getCsrfToken();
        $.ajax({url: button.data('url'), method: 'POST', data: data, dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (response && response.success) {
                sx.notify.success(response.message);
                window.location.reload();
            } else {
                sx.notify.error(response && response.message ? response.message : 'Не удалось.');
            }
        });
        return false;
    });

    $(function () {
        $(rootSelector).each(function () { init(this); });
        $('[data-sx-dupcheck]').each(function () { dupcheckInit(this); });
    });
}(sx, sx.$));

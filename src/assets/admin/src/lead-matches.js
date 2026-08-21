(function ($) {
    'use strict';

    var selector = '[data-sx-lead-matches]';

    function showError(element) {
        element.removeAttribute('aria-busy');
        element.innerHTML = '<section class="sx-surface sx-surface--raised">' +
            '<div class="sx-surface__body sx-lead-matches__error">' +
            '<span>Не удалось проверить совпадения в CRM.</span>' +
            '<button type="button" class="sx-button sx-button--secondary sx-button--sm" data-sx-lead-matches-retry>Повторить</button>' +
            '</div></section>';
    }

    function load(element) {
        if (!element || element.dataset.loading === '1') {
            return;
        }
        element.dataset.loading = '1';
        element.setAttribute('aria-busy', 'true');

        fetch(element.dataset.url, {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        }).then(function (html) {
            if (!html.trim()) {
                element.remove();
                return;
            }
            element.innerHTML = html;
            element.removeAttribute('aria-busy');
            element.dataset.loaded = '1';
        }).catch(function () {
            delete element.dataset.loading;
            showError(element);
        });
    }

    function init(scope) {
        $(scope || document).find(selector).each(function () {
            if (this.dataset.loaded !== '1') {
                load(this);
            }
        });
    }

    $(document).on('click', '[data-sx-lead-matches-retry]', function () {
        var element = this.closest(selector);
        if (element) {
            element.innerHTML = '<div class="sx-lead-matches__loading">Проверяем компании и клиентов…</div>';
            load(element);
        }
    });
    $(function () { init(document); });
    $(document).on('pjax:end', function (event) { init(event.target); });
}(jQuery));

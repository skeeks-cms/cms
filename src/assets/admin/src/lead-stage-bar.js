(function (sx, $) {
    'use strict';

    function move(bar, column) {
        var data = {pk: bar.data('pk'), column: column};
        data[yii.getCsrfParam()] = yii.getCsrfToken();

        bar.addClass('is-busy');
        $.ajax({
            url: bar.data('moveUrl'),
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (response) {
            if (!response || !response.success) {
                sx.notify.error(response && response.message ? response.message : 'Не удалось сменить этап.');
                return;
            }
            if (response.data && response.data.openUrl) {
                // A result or a rejection reason is required: finish in the form.
                new sx.classes.backend.widgets.Action({isOpenNewWindow: true, url: response.data.openUrl}).go();
                return;
            }
            window.location.reload();
        }).fail(function () {
            sx.notify.error('Не удалось сменить этап. Обновите страницу.');
        }).always(function () {
            bar.removeClass('is-busy');
        });
    }

    $(document).on('click', '[data-sx-lead-stage-bar] [data-column]', function () {
        var button = $(this);
        var bar = button.closest('[data-sx-lead-stage-bar]');
        if (button.prop('disabled') || bar.hasClass('is-busy')) {
            return;
        }
        var column = button.data('column');
        var confirmText = button.data('confirmText');
        if (confirmText) {
            sx.confirm(confirmText, {
                yes: function () { move(bar, column); },
                no: function () {}
            });
            return;
        }
        move(bar, column);
    });
}(sx, sx.$));

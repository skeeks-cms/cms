/* Profile → "Уведомления": show kinds of a category on demand; "Как в разделе (…)" follows the category. */
(function () {
    document.addEventListener('click', function (e) {
        var button = e.target.closest('[data-sx-notify-expand]');
        if (!button) {
            return;
        }
        var category = button.getAttribute('data-sx-notify-expand');
        var open = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.textContent = open ? 'свернуть' : 'по видам';
        document.querySelectorAll('[data-sx-notify-kind-of="' + category + '"]').forEach(function (row) {
            row.hidden = !open;
        });
    });

    // "Как в разделе (…)" of the kinds follows the category select of the same channel.
    document.addEventListener('change', function (e) {
        var match = e.target.name && e.target.name.match(/^modes\[([^\]]+)\]\[([^\]]+)\]$/);
        if (!match) {
            return;
        }
        var label = 'Как в разделе (' + e.target.options[e.target.selectedIndex].text + ')';
        document.querySelectorAll('[data-sx-notify-kind-of="' + match[1] + '"] select').forEach(function (select) {
            var channel = /\]\[([^\]]+)\]$/.exec(select.name);
            if (channel && channel[1] === match[2] && select.options[0].value === '') {
                select.options[0].text = label;
            }
        });
    });
})();

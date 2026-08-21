(function ($) {
    'use strict';

    var statusSelector = '[data-sx-lead-status]';
    var conditionalSelector = '[data-sx-lead-statuses]';

    function update(statusInput) {
        var $input = $(statusInput);
        var $form = $input.closest('form');
        var $scope = $form.length ? $form : $(document);
        var status = $input.val();

        $scope.find(conditionalSelector).each(function () {
            var statuses = ($(this).attr('data-sx-lead-statuses') || '').split(/\s+/);
            $(this).prop('hidden', statuses.indexOf(status) === -1);
        });
    }

    $(document).on('change', statusSelector, function () { update(this); });
    $(function () { $(statusSelector).each(function () { update(this); }); });
}(jQuery));

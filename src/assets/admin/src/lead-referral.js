/* «Откуда узнали»: the chosen chip is marked; a click on the chosen one clears the choice. */
(function ($) {
    $(document).on('click.sxLeadReferral', '.sx-lead-referral__input', function () {
        var $input = $(this);
        var $group = $input.closest('.sx-lead-referral');
        if ($input.data('sxWasChecked')) {
            $input.prop('checked', false);
            $group.find('.sx-lead-referral__input[value=""]').prop('checked', true);
        }
        $group.find('.sx-lead-referral__input').each(function () {
            var $item = $(this);
            $item.data('sxWasChecked', $item.prop('checked') && $item.val() !== '');
            $item.closest('.sx-chip').toggleClass('is-active', $item.prop('checked') && $item.val() !== '')
                .attr('aria-pressed', $item.prop('checked') && $item.val() !== '' ? 'true' : 'false');
        });
        $input.trigger('change');
    });
    $(function () {
        $('.sx-lead-referral__input:checked').each(function () {
            $(this).data('sxWasChecked', $(this).val() !== '');
        });
    });
})(jQuery);

/**
 * A soft phone mask for inputs with the attribute data-sx-phone-input.
 *
 * A Russian number is shown as the CMS stores phones: «+7 900 000-00-00» (8…, 7… and a bare 9… become +7).
 * A number written with another country code («+375 …») is left as typed. The value is never cleared or cut:
 * an unfinished number stays for the visitor to fix, the server checks it (PhoneValidator).
 *
 * While typing at the end of the input the number is formatted at once; an edit in the middle or deleting is
 * formatted when the input loses focus, so the caret never jumps. No dependencies: delegated listeners also
 * cover forms loaded later (pjax, popups).
 */
(function () {
    'use strict';

    if (window.sxPhoneInput) {
        return;
    }

    var selector = '[data-sx-phone-input]';

    function formatRu(digits) {
        var d = digits.slice(1, 11);
        var out = '+7';
        if (d.length > 0) {
            out += ' ' + d.slice(0, 3);
        }
        if (d.length > 3) {
            out += ' ' + d.slice(3, 6);
        }
        if (d.length > 6) {
            out += '-' + d.slice(6, 8);
        }
        if (d.length > 8) {
            out += '-' + d.slice(8, 10);
        }
        return out;
    }

    /** The formatted value, or the value itself when it is not a Russian number. */
    function format(value) {
        var trimmed = String(value || '').trim();
        if (trimmed === '') {
            return '';
        }
        // Letters or other text: not a number, leave it to the visitor and the server.
        if (/[^\d\s+\-().]/.test(trimmed)) {
            return value;
        }
        var digits = trimmed.replace(/\D/g, '');
        var plus = trimmed.charAt(0) === '+';
        if (digits === '') {
            return plus ? '+' : value;
        }
        if (plus) {
            if (digits.charAt(0) !== '7') {
                return value;
            }
        } else if (digits.charAt(0) === '8' || digits.charAt(0) === '7') {
            digits = '7' + digits.slice(1);
        } else if (digits.charAt(0) === '9') {
            digits = '7' + digits;
        } else {
            return value;
        }
        if (digits.length > 11) {
            return value;
        }
        return formatRu(digits);
    }

    function apply(input) {
        var formatted = format(input.value);
        if (formatted !== input.value) {
            input.value = formatted;
        }
    }

    function isPhoneInput(target) {
        return target && target.matches && target.matches(selector);
    }

    document.addEventListener('input', function (e) {
        var input = e.target;
        if (!isPhoneInput(input)) {
            return;
        }
        if (e.inputType && e.inputType.indexOf('delete') === 0) {
            return;
        }
        var atEnd = input.selectionStart === input.value.length && input.selectionEnd === input.value.length;
        if (!atEnd) {
            return;
        }
        apply(input);
        try {
            input.setSelectionRange(input.value.length, input.value.length);
        } catch (err) {
            // type=tel supports selection; ignore exotic inputs
        }
    }, true);

    document.addEventListener('focusout', function (e) {
        if (isPhoneInput(e.target)) {
            apply(e.target);
        }
    }, true);

    window.sxPhoneInput = {format: format};
})();

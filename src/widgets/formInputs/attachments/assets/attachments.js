/**
 * Form attachments (skeeks/cms AttachmentsInputWidget).
 *
 * Root [data-sx-attach]: data-upload-url, data-name (token input name),
 * data-max-size, data-max-size-message, data-drop. Files are uploaded into
 * the temporary area at once and the chip keeps the token in a hidden input.
 *
 * The area of a field is its closest form or [data-sx-attach-zone]: files
 * dropped or pasted there, the "Прикрепить" button ([data-sx-attach-open])
 * and files given by its rich-text editors ("sx-rte:attach") are attached.
 *
 * API: sx.Attachments.add(root, files), isBusy(context).
 * Event: "sx-attach:change" on the root after a file is attached or removed.
 */
(function (sx, $) {
    'use strict';

    var AREA = 'form, [data-sx-attach-zone]';

    function notify(type, text) {
        if (window.sx && sx.notify && typeof sx.notify[type] === 'function') {
            sx.notify[type](text);
        } else {
            window.alert(text);
        }
    }

    /** The field of the area around an element, or an empty set. */
    function fieldOf(el) {
        var $own = $(el).closest('[data-sx-attach]');
        if ($own.length) {
            return $own;
        }
        return $(el).closest(AREA).find('[data-sx-attach]').first();
    }

    function hasFiles(dt) {
        return dt && Array.prototype.indexOf.call(dt.types || [], 'Files') !== -1;
    }

    function isBusy(context) {
        var $fields = $(context).find('[data-sx-attach]').addBack('[data-sx-attach]');
        return $fields.filter(function () {
            return ($(this).data('uploads') || 0) > 0;
        }).length > 0;
    }

    function changed($root) {
        $root.trigger('sx-attach:change');
    }

    function upload($root, file, $chip) {
        var max = parseInt($root.data('max-size'), 10) || 0;
        if (max && file.size > max) {
            var mb = Math.round(max / 1024 / 1024);
            notify('error', ($root.data('max-size-message') || '«{name}» больше {size} МБ.')
                .replace('{name}', file.name).replace('{size}', mb));
            return $.Deferred().reject().promise();
        }
        var data = new FormData();
        data.append('file', file, file.name || 'file');
        if (window.yii && yii.getCsrfParam()) {
            data.append(yii.getCsrfParam(), yii.getCsrfToken());
        }
        $root.data('uploads', ($root.data('uploads') || 0) + 1).addClass('is-uploading');
        return $.ajax({
            url: $root.data('upload-url'),
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function (e) {
                        if (e.lengthComputable) {
                            $chip.find('.sx-attach__size').text(Math.round(e.loaded / e.total * 100) + '%');
                        }
                    });
                }
                return xhr;
            }
        }).then(function (response) {
            if (!response || !response.success) {
                notify('error', (response && response.message) || 'Файл «' + file.name + '» не загрузился.');
                return $.Deferred().reject().promise();
            }
            return response;
        }, function () {
            notify('error', 'Файл «' + file.name + '» не загрузился.');
            return $.Deferred().reject().promise();
        }).always(function () {
            var left = $root.data('uploads') - 1;
            $root.data('uploads', left).toggleClass('is-uploading', left > 0);
        });
    }

    /** Attaches files: a chip each, the token in it once uploaded. */
    function add(root, files) {
        var $root = $(root);
        var template = $root.find('[data-sx-attach-template]').get(0);
        if (!template || !files) {
            return;
        }
        Array.prototype.forEach.call(files, function (file) {
            var $chip = $(template.content.firstElementChild.cloneNode(true)).addClass('is-loading');
            var name = file.name || 'file';
            $chip.find('.sx-attach__name').text(name).attr('title', name);
            $chip.find('.sx-attach__size').text('0%');
            $chip.find('[data-sx-attach-remove]').prop('hidden', true);
            $chip.find('input[type=hidden]').remove();
            $root.find('[data-sx-attach-list]').append($chip);
            upload($root, file, $chip).done(function (response) {
                $chip.removeClass('is-loading');
                $chip.find('.sx-attach__name').text(response.name).attr('title', response.name);
                $chip.find('.sx-attach__size').text(response.sizeText);
                $chip.find('[data-sx-attach-remove]').prop('hidden', false);
                $('<input type="hidden">').attr('name', $root.data('name') + '[]').val(response.token).appendTo($chip);
                changed($root);
            }).fail(function () {
                $chip.remove();
            });
        });
    }

    $(document).on('click', '[data-sx-attach-open]', function () {
        var target = $(this).attr('data-sx-attach-open');
        var $root = target && target !== 'true' && target !== '1' ? $('#' + target) : fieldOf(this);
        $root.find('[data-sx-attach-input]').trigger('click');
    });

    $(document).on('change', '[data-sx-attach-input]', function () {
        add($(this).closest('[data-sx-attach]'), this.files);
        this.value = '';
    });

    $(document).on('click', '[data-sx-attach-remove]', function () {
        var $root = $(this).closest('[data-sx-attach]');
        $(this).closest('[data-sx-attach-file]').remove();
        changed($root);
    });

    // A rich-text editor of the area hands over files that do not go into its text.
    $(document).on('sx-rte:attach', '[data-sx-rte]', function (e, files) {
        var $root = fieldOf(this);
        if ($root.length) {
            add($root, files);
        }
    });

    function dropField(e) {
        var $root = fieldOf(e.target);
        return $root.length && $root.data('drop') ? $root : null;
    }

    // Files dropped anywhere else on the area are attached (the editor's own zones stop the event).
    $(document).on('dragover', function (e) {
        if (hasFiles(e.originalEvent.dataTransfer) && dropField(e)) {
            e.preventDefault();
        }
    });

    $(document).on('drop', function (e) {
        var dt = e.originalEvent.dataTransfer;
        var $root = dt && dt.files && dt.files.length ? dropField(e) : null;
        if ($root) {
            e.preventDefault();
            add($root, dt.files);
        }
    });

    // Files pasted outside a text field (the editor handles its own paste).
    $(document).on('paste', function (e) {
        var data = e.originalEvent.clipboardData;
        if (!data || !data.files || !data.files.length
            || $(e.target).closest('[data-sx-rte-editor], input, textarea, [contenteditable]').length) {
            return;
        }
        var $root = dropField(e);
        if ($root) {
            e.preventDefault();
            add($root, data.files);
        }
    });

    // A form is not sent while files are still uploading.
    document.addEventListener('submit', function (e) {
        if (isBusy(e.target)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            notify('error', 'Дождитесь, пока загрузятся файлы.');
        }
    }, true);

    sx.Attachments = {
        add: add,
        isBusy: isBusy
    };
})(sx, jQuery);

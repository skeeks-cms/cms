/*!
 * Light rich-text editor of a form field (RichTextInputWidget).
 *
 * The root [data-sx-rte] holds the toolbar, the contenteditable text and a
 * hidden textarea (data-input) with the value. The value is written only when
 * the text is edited, so an untouched field posts its old value unchanged.
 * Editors added later (AJAX, pjax) start by themselves.
 *
 * API: sx.RichText.init(root), load(root), focus(root, atStart), isEmpty(root),
 * isBusy(root), insertImages(root, files), isComplex(html).
 * Event: "sx-rte:attach" (files) on the root, when files are dropped onto
 * the "attach" zone (the widget's attachLabel).
 */
(function ($) {
    'use strict';

    var EMAIL = /^[^\s@<>,;]+@[^\s@<>,;]+\.[^\s@<>,;]+$/;
    var IMAGE = /^image\/(png|jpe?g|gif|webp)$/;
    var BLOCKS = {P: 'Текст', H1: 'Заголовок 1', H2: 'Заголовок 2', H3: 'Заголовок 3', H4: 'Заголовок 4'};

    function notify(type, text) {
        if (window.sx && sx.notify && typeof sx.notify[type] === 'function') {
            sx.notify[type](text);
        } else {
            window.alert(text);
        }
    }

    function rootOf(el) {
        return $(el).closest('[data-sx-rte]');
    }

    function editorOf($root) {
        return $root.find('[data-sx-rte-editor]').get(0);
    }

    function inputOf($root) {
        return $('#' + $root.data('input'));
    }

    /** Scripts, styles, embeds or event handlers: a page fragment, not editor text. */
    function isComplex(html) {
        return /<(script|style|iframe|object|embed|svg|form|input|button|select|textarea|video|audio|canvas|link|meta|base)\b|<\?|\son[a-z]+\s*=|javascript:/i.test(String(html || ''));
    }

    /** Old HTML shown in the editor without running anything from it. */
    function fragment(html) {
        var doc = new DOMParser().parseFromString('<body>' + String(html || '') + '</body>', 'text/html');
        $(doc.body).find('script,style,iframe,object,embed,link,meta,base,form,input,button,select,textarea').remove();
        $(doc.body).find('*').each(function () {
            var el = this;
            Array.prototype.slice.call(el.attributes).forEach(function (attr) {
                if (/^on/i.test(attr.name) || /^\s*(javascript|vbscript):/i.test(attr.value)) {
                    el.removeAttribute(attr.name);
                }
            });
        });
        var result = document.createDocumentFragment();
        Array.prototype.slice.call(doc.body.childNodes).forEach(function (node) {
            result.appendChild(document.importNode(node, true));
        });
        return result;
    }

    function init(root) {
        var $root = $(root);
        if (!$root.length || $root.data('sxRteReady')) {
            return;
        }
        $root.data('sxRteReady', true);
        load($root);
        if ($root.data('autofocus')) {
            setTimeout(function () {
                focus($root, true);
            }, 50);
        }
    }

    /** (Re)reads the value into the editor: after another mode edited the textarea. */
    function load(root) {
        var $root = $(root);
        var editor = editorOf($root);
        var html = inputOf($root).val() || '';
        // A site page with scripts, styles or embeds is edited as HTML: the visual editor would break it.
        var locked = !!$root.data('trusted') && isComplex(html);
        $root.toggleClass('is-locked', locked);
        editor.setAttribute('contenteditable', locked ? 'false' : 'true');
        editor.innerHTML = '';
        if (locked) {
            editor.textContent = 'В тексте есть скрипты, стили или встроенные блоки — визуальный редактор их испортит. Измените текст в режиме HTML.';
        } else {
            editor.appendChild(fragment(html));
        }
        $root.removeData('range');
        return !locked;
    }

    function hasContent(editor) {
        return $.trim((editor.textContent || '').replace(/ /g, ' ')) !== '' || $(editor).find('img').length > 0;
    }

    /** The edited text into the value. */
    function sync($root) {
        var editor = editorOf($root);
        if ($root.hasClass('is-locked')) {
            return;
        }
        inputOf($root).val(hasContent(editor) ? editor.innerHTML : '').trigger('change');
    }

    function focus(root, atStart) {
        var $root = $(root);
        var editor = editorOf($root);
        editor.focus();
        var range = document.createRange();
        range.selectNodeContents(editor);
        range.collapse(!!atStart);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function prepare($root) {
        try {
            document.execCommand('defaultParagraphSeparator', false, $root.data('paragraph') || 'p');
            // Colors, fonts and sizes as style="" (the server keeps only known properties).
            document.execCommand('styleWithCSS', false, true);
        } catch (e) {
        }
    }

    /* Selection: it survives a click into a menu or the link box. */

    function saveRange($root) {
        var selection = window.getSelection();
        var editor = editorOf($root);
        if (selection.rangeCount && editor.contains(selection.anchorNode)) {
            $root.data('range', selection.getRangeAt(0).cloneRange());
        }
    }

    function restoreRange($root) {
        var range = $root.data('range');
        var editor = editorOf($root);
        if (range && editor.contains(range.commonAncestorContainer)) {
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
    }

    document.addEventListener('selectionchange', function () {
        var selection = window.getSelection();
        var $editor = selection.rangeCount ? $(selection.anchorNode).closest('[data-sx-rte-editor]') : $();
        if ($editor.length) {
            saveRange(rootOf($editor));
        }
    });

    /* Formatting */

    function closestBlock(tags) {
        var selection = window.getSelection();
        return selection.rangeCount ? $(selection.anchorNode).closest(tags).get(0) : null;
    }

    function updateTools($root) {
        $root.find('[data-sx-rte-cmd]').each(function () {
            var cmd = $(this).data('sx-rte-cmd');
            if (['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'].indexOf(cmd) !== -1) {
                var on = false;
                try {
                    on = document.queryCommandState(cmd);
                } catch (e) {
                }
                $(this).toggleClass('is-active', on);
            }
        });
        var font = '';
        try {
            font = String(document.queryCommandValue('fontName') || '').replace(/["']/g, '').split(',')[0];
        } catch (e) {
        }
        $root.find('[data-sx-rte-font]').text(font || 'Шрифт');
        var block = closestBlock('h1,h2,h3,h4');
        $root.find('[data-sx-rte-block]').text(BLOCKS[block ? block.nodeName : 'P']);
    }

    function closeMenus($root) {
        $root.find('[data-sx-rte-menu-list]').prop('hidden', true);
    }

    $(document).on('mousedown', '[data-sx-rte-cmd], [data-sx-rte-menu]', function (e) {
        // Keep the selection in the editor.
        e.preventDefault();
    });

    $(document).on('click', '[data-sx-rte-cmd]', function () {
        var $root = rootOf(this);
        var editor = editorOf($root);
        var cmd = $(this).data('sx-rte-cmd');
        var value = $(this).attr('data-value');
        editor.focus();
        prepare($root);
        restoreRange($root);
        if (cmd === 'createLink') {
            saveRange($root);
            closeMenus($root);
            showLinkInput($root);
            return;
        }
        if (cmd === 'formatBlock') {
            var paragraph = '<' + ($root.data('paragraph') || 'p') + '>';
            if (value === 'blockquote') {
                document.execCommand('formatBlock', false, closestBlock('blockquote') ? paragraph : '<blockquote>');
            } else {
                // The chosen heading again turns it back into text.
                var current = closestBlock('h1,h2,h3,h4');
                var same = current && current.nodeName.toLowerCase() === value;
                document.execCommand('formatBlock', false, value === 'p' || same ? paragraph : '<' + value + '>');
            }
        } else if (cmd === 'hiliteColor') {
            // Chrome and Firefox name the background command differently.
            if (!document.execCommand('hiliteColor', false, value)) {
                document.execCommand('backColor', false, value);
            }
        } else {
            document.execCommand(cmd, false, value === undefined ? null : value);
        }
        closeMenus($root);
        updateTools($root);
        sync($root);
    });

    $(document).on('click', '[data-sx-rte-menu]', function () {
        var $root = rootOf(this);
        var $list = $root.find('[data-sx-rte-menu-list="' + $(this).data('sx-rte-menu') + '"]');
        var open = $list.prop('hidden');
        closeMenus($root);
        $list.prop('hidden', !open);
        if (open) {
            // A menu near the right edge (narrow screens) moves left to stay on screen.
            var list = $list.css('transform', '').get(0);
            var rect = list.getBoundingClientRect();
            var shift = Math.min(0, document.documentElement.clientWidth - 8 - rect.right);
            shift = Math.max(shift, 8 - rect.left);
            if (shift) {
                $list.css('transform', 'translateX(' + Math.round(shift) + 'px)');
            }
        }
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.sx-rte__menu-wrap').length) {
            $('[data-sx-rte-menu-list]').prop('hidden', true);
        }
    });

    /* "На весь экран": the editor covers the window; Esc or the button returns it. */
    function setFullscreen($root, on) {
        $root.toggleClass('is-fullscreen', on);
        $root.find('[data-sx-rte-fullscreen]').toggleClass('is-active', on).attr('aria-pressed', on ? 'true' : 'false')
            .attr('title', on ? 'Свернуть (Esc)' : 'На весь экран (Esc — свернуть)');
        $('html').toggleClass('sx-rte-fullscreen-open', $('.sx-rte.is-fullscreen').length > 0);
        editorOf($root).focus();
        restoreRange($root);
    }

    $(document).on('mousedown', '[data-sx-rte-fullscreen]', function (e) {
        e.preventDefault();
    });

    $(document).on('click', '[data-sx-rte-fullscreen]', function () {
        var $root = rootOf(this);
        closeMenus($root);
        setFullscreen($root, !$root.hasClass('is-fullscreen'));
    });

    $(document).on('keydown', '[data-sx-rte]', function (e) {
        if (e.key !== 'Escape') {
            return;
        }
        if ($(this).find('[data-sx-rte-menu-list]:not([hidden])').length) {
            e.stopPropagation();
            closeMenus($(this));
        } else if ($(this).hasClass('is-fullscreen') && !$(e.target).closest('.sx-rte__linkbox').length) {
            // Esc leaves the full screen first, before a window or panel closes.
            e.preventDefault();
            e.stopImmediatePropagation();
            setFullscreen($(this), false);
        }
    });

    $(document).on('focusin', '[data-sx-rte-editor]', function () {
        var $root = rootOf(this);
        init($root);
        prepare($root);
        $root.addClass('is-focused');
    });

    $(document).on('focusout', '[data-sx-rte-editor]', function () {
        rootOf(this).removeClass('is-focused');
    });

    var INLINE = /^(#text|B|STRONG|I|EM|U|S|STRIKE|SUB|SUP|SPAN|FONT|A|CODE|BR|IMG)$/;

    /**
     * Browsers wrap only the lines after the first Enter: the first line typed
     * into an empty editor stays bare text. Wrap the line being typed into a
     * paragraph so every line of the text has the same markup.
     */
    function wrapBareLine($root) {
        var editor = editorOf($root);
        var selection = window.getSelection();
        if (!selection.rangeCount || !selection.isCollapsed) {
            return;
        }
        var node = selection.anchorNode;
        if (node === editor) {
            node = editor.childNodes[selection.anchorOffset - 1] || editor.childNodes[selection.anchorOffset] || null;
        }
        while (node && node.parentNode !== editor) {
            node = node.parentNode;
        }
        if (!node || !INLINE.test(node.nodeName) || $.trim(node.textContent || '') === '' && node.nodeName !== 'IMG') {
            return;
        }
        prepare($root);
        document.execCommand('formatBlock', false, '<' + ($root.data('paragraph') || 'p') + '>');
    }

    $(document).on('input', '[data-sx-rte-editor]', function () {
        var $root = rootOf(this);
        wrapBareLine($root);
        sync($root);
    });

    $(document).on('keyup mouseup', '[data-sx-rte-editor]', function () {
        updateTools(rootOf(this));
    });

    function showLinkInput($root) {
        $root.find('.sx-rte__linkbox').remove();
        var $box = $('<div class="sx-rte__linkbox">'
            + '<input type="url" class="sx-rte__link-input" placeholder="https://…" aria-label="Адрес ссылки">'
            + '<button type="button" class="sx-rte__link-apply">Вставить</button>'
            + '<button type="button" class="sx-rte__link-cancel">Отмена</button></div>');
        $root.find('.sx-rte__tools').after($box);
        var $input = $box.find('input').trigger('focus');
        var current = closestLink($root);
        if (current) {
            $input.val(current.getAttribute('href') || '');
        }
        var apply = function () {
            var url = $.trim($input.val());
            $box.remove();
            editorOf($root).focus();
            restoreRange($root);
            if (!url) {
                return;
            }
            if (!/^(https?:\/\/|mailto:|tel:)/i.test(url)) {
                url = (EMAIL.test(url) ? 'mailto:' : 'https://') + url;
            }
            if (window.getSelection().isCollapsed && !current) {
                document.execCommand('insertHTML', false, $('<a>').attr('href', url).text(url).prop('outerHTML'));
            } else if (window.getSelection().isCollapsed && current) {
                current.setAttribute('href', url);
            } else {
                document.execCommand('createLink', false, url);
            }
            sync($root);
        };
        $box.find('.sx-rte__link-apply').on('click', apply);
        $box.find('.sx-rte__link-cancel').on('click', function () {
            $box.remove();
        });
        $input.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                apply();
            } else if (e.key === 'Escape') {
                e.stopPropagation();
                $box.remove();
            }
        });
    }

    function closestLink($root) {
        var range = $root.data('range');
        var el = range ? $(range.startContainer).closest('a').get(0) : null;
        return el && editorOf($root).contains(el) ? el : null;
    }

    /* Paste: text from Word or a browser keeps only simple formatting. */

    var PASTE_TAGS = ['P', 'DIV', 'BR', 'B', 'STRONG', 'I', 'EM', 'U', 'S', 'STRIKE', 'SUB', 'SUP', 'A', 'UL', 'OL', 'LI', 'BLOCKQUOTE',
        'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'PRE', 'CODE', 'HR'];

    /** Address of a pasted picture we can copy to us: http(s) or an embedded raster image. */
    var REMOTE_IMAGE = /^(https?:\/\/|data:image\/(png|jpe?g|gif|webp);base64,)/i;
    var MAX_PASTED_IMAGES = 30;

    /**
     * @param {boolean} images keep pictures (marked data-sx-rte-import) to copy them to us
     */
    function cleanPasted(html, images) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var count = 0;
        var clean = function (node) {
            Array.prototype.slice.call(node.childNodes).forEach(function (child) {
                if (child.nodeType === 3) {
                    return;
                }
                if (child.nodeName === 'IMG') {
                    // srcset of responsive pictures may hold the only absolute address.
                    var src = child.getAttribute('src') || '';
                    if (!REMOTE_IMAGE.test(src) && child.srcset) {
                        src = child.srcset.split(',')[0].trim().split(/\s+/)[0];
                    }
                    if (!images || !REMOTE_IMAGE.test(src) || ++count > MAX_PASTED_IMAGES) {
                        child.remove();
                        return;
                    }
                    var img = doc.createElement('img');
                    img.setAttribute('src', src);
                    img.setAttribute('alt', '');
                    img.setAttribute('data-sx-rte-import', '1');
                    node.replaceChild(img, child);
                    return;
                }
                if (child.nodeType !== 1 || /^(SCRIPT|STYLE|META|LINK|TITLE|IFRAME|OBJECT|EMBED|SVG|VIDEO|AUDIO|BUTTON|INPUT|SELECT|TEXTAREA)$/.test(child.nodeName)) {
                    child.remove();
                    return;
                }
                clean(child);
                if (PASTE_TAGS.indexOf(child.nodeName) === -1) {
                    while (child.firstChild) {
                        node.insertBefore(child.firstChild, child);
                    }
                    child.remove();
                    return;
                }
                var href = child.nodeName === 'A' ? child.getAttribute('href') : null;
                Array.prototype.slice.call(child.attributes).forEach(function (attr) {
                    child.removeAttribute(attr.name);
                });
                if (href && /^(https?:|mailto:|tel:)/i.test(href)) {
                    child.setAttribute('href', href);
                }
            });
        };
        // Word puts its own comments and conditional blocks around the text.
        clean(doc.body);
        return doc.body.innerHTML.replace(/<!--[\s\S]*?-->/g, '');
    }

    $(document).on('paste', '[data-sx-rte-editor]', function (e) {
        var data = e.originalEvent.clipboardData;
        var $root = rootOf(this);
        if (!data) {
            return;
        }
        var html = data.getData('text/html');
        // Text with pictures from a site or Word comes as HTML; Word may add a picture
        // of the whole selection as a file, which must not replace the text.
        var hasText = html && $.trim($('<div>').html(html.replace(/<(script|style)[\s\S]*?<\/\1>/gi, '')).text()) !== '';
        // A screenshot or a copied picture: upload it and put it into the text.
        if (data.files && data.files.length && !hasText) {
            e.preventDefault();
            saveRange($root);
            insertImages($root, data.files);
            return;
        }
        e.preventDefault();
        if (html) {
            document.execCommand('insertHTML', false, cleanPasted(html, !!$root.data('import-url')));
            importPasted($root);
        } else {
            document.execCommand('insertText', false, data.getData('text/plain'));
        }
        sync($root);
    });

    /**
     * Pictures of pasted text are copied to us like pictures chosen from a disk:
     * downloaded by the server into the temporary area (embedded ones uploaded),
     * then shown from there and stored when the record is saved. A picture that
     * cannot be copied is removed: the text never keeps a link to someone else's server.
     */
    function importPasted($root) {
        var $images = $(editorOf($root)).find('img[data-sx-rte-import]');
        if (!$images.length) {
            return;
        }
        var failed = 0;
        var left = $images.length;
        var done = function () {
            if (--left === 0) {
                sync($root);
                if (failed) {
                    notify('error', 'Не удалось перенести к нам картинок: ' + failed + '. Они убраны из текста — вставьте их файлом.');
                }
            }
        };
        $images.each(function () {
            var img = this;
            var src = img.getAttribute('src');
            $(img).addClass('is-importing');
            var request;
            if (/^data:/i.test(src)) {
                var file = dataUrlFile(src);
                request = file ? uploadImage($root, file, true) : $.Deferred().reject().promise();
            } else {
                request = importImage($root, src);
            }
            request.done(function (response) {
                $(img).removeAttr('class data-sx-rte-import')
                    .attr('src', response.url).attr('data-sx-upload', response.token);
            }).fail(function () {
                failed++;
                $(img).remove();
            }).always(done);
        });
    }

    function importImage($root, url) {
        var data = {url: url};
        if (window.yii && yii.getCsrfParam()) {
            data[yii.getCsrfParam()] = yii.getCsrfToken();
        }
        $root.data('uploads', ($root.data('uploads') || 0) + 1).addClass('is-uploading');
        return $.post($root.data('import-url'), data).then(function (response) {
            return response && response.success ? response : $.Deferred().reject().promise();
        }).always(function () {
            var left = $root.data('uploads') - 1;
            $root.data('uploads', left).toggleClass('is-uploading', left > 0);
        });
    }

    function dataUrlFile(src) {
        var m = /^data:(image\/(png|jpe?g|gif|webp));base64,(.*)$/i.exec(src.replace(/\s+/g, ''));
        if (!m) {
            return null;
        }
        try {
            var bytes = atob(m[3]);
            var array = new Uint8Array(bytes.length);
            for (var i = 0; i < bytes.length; i++) {
                array[i] = bytes.charCodeAt(i);
            }
            return new File([array], 'картинка.' + m[2].replace('jpeg', 'jpg'), {type: m[1]});
        } catch (e) {
            return null;
        }
    }

    /* Images: the button, paste and drag and drop. */

    function isBusy(root) {
        return ($(root).data('uploads') || 0) > 0;
    }

    function uploadImage($root, file, quiet) {
        var say = quiet ? function () {} : notify;
        var max = parseInt($root.data('max-size'), 10) || 0;
        if (max && file.size > max) {
            say('error', '«' + file.name + '» больше ' + Math.round(max / 1024 / 1024) + ' МБ.');
            return $.Deferred().reject().promise();
        }
        var data = new FormData();
        data.append('file', file, file.name || 'image.png');
        if (window.yii && yii.getCsrfParam()) {
            data.append(yii.getCsrfParam(), yii.getCsrfToken());
        }
        $root.data('uploads', ($root.data('uploads') || 0) + 1).addClass('is-uploading');
        return $.ajax({
            url: $root.data('upload-url'),
            type: 'POST',
            data: data,
            processData: false,
            contentType: false
        }).then(function (response) {
            if (!response || !response.success) {
                say('error', (response && response.message) || 'Картинка не загрузилась.');
                return $.Deferred().reject().promise();
            }
            return response;
        }, function () {
            say('error', 'Картинка «' + file.name + '» не загрузилась.');
            return $.Deferred().reject().promise();
        }).always(function () {
            var left = $root.data('uploads') - 1;
            $root.data('uploads', left).toggleClass('is-uploading', left > 0);
        });
    }

    /** Pictures go into the text; other files to the form's attachments, if it has them. */
    function insertImages(root, files) {
        var $root = $(root);
        var others = [];
        Array.prototype.forEach.call(files, function (file) {
            if (!IMAGE.test(file.type) || !$root.data('upload-url')) {
                others.push(file);
                return;
            }
            uploadImage($root, file).done(function (response) {
                editorOf($root).focus();
                prepare($root);
                restoreRange($root);
                var img = $('<img alt="">').attr('src', response.url).attr('data-sx-upload', response.token).prop('outerHTML');
                document.execCommand('insertHTML', false, img);
                sync($root);
            });
        });
        if (others.length) {
            attach($root, others);
        }
    }

    function attach($root, files) {
        if ($root.data('attach')) {
            $root.trigger('sx-rte:attach', [files]);
        } else {
            notify('error', $root.data('upload-url') ? 'В текст можно вставить только картинку: PNG, JPEG, GIF или WebP.' : 'Картинки в этот текст не вставляются.');
        }
    }

    $(document).on('click', '[data-sx-rte-image]', function () {
        var $root = rootOf(this);
        saveRange($root);
        $root.find('[data-sx-rte-image-input]').trigger('click');
    });

    $(document).on('change', '[data-sx-rte-image-input]', function () {
        insertImages(rootOf(this), this.files);
        this.value = '';
    });

    // Two drop zones over the editor while files are dragged over the page.
    var dragDepth = 0;

    function hasFiles(e) {
        var dt = e.originalEvent && e.originalEvent.dataTransfer;
        return dt && Array.prototype.indexOf.call(dt.types || [], 'Files') !== -1;
    }

    function showZones(show) {
        $('[data-sx-rte-drop]').each(function () {
            var visible = show && $(this).closest('[data-sx-rte]').is(':visible');
            $(this).prop('hidden', !visible).closest('[data-sx-rte]').toggleClass('is-dragging', visible);
        });
        if (!show) {
            $('[data-sx-rte-zone]').removeClass('is-over');
        }
    }

    $(document).on('dragenter', function (e) {
        if (hasFiles(e)) {
            dragDepth++;
            showZones(true);
        }
    });

    $(document).on('dragleave', function (e) {
        if (hasFiles(e) && --dragDepth <= 0) {
            dragDepth = 0;
            showZones(false);
        }
    });

    $(document).on('drop dragend', function () {
        dragDepth = 0;
        showZones(false);
    });

    $(document).on('dragover', '[data-sx-rte-zone]', function (e) {
        if (hasFiles(e)) {
            e.preventDefault();
            $(this).addClass('is-over').siblings().removeClass('is-over');
        }
    });

    $(document).on('dragleave', '[data-sx-rte-zone]', function () {
        $(this).removeClass('is-over');
    });

    $(document).on('drop', '[data-sx-rte-zone]', function (e) {
        var dt = e.originalEvent.dataTransfer;
        var $root = rootOf(this);
        e.preventDefault();
        // The form's own drop handler must not attach the same files again.
        e.stopPropagation();
        dragDepth = 0;
        showZones(false);
        if (!dt || !dt.files || !dt.files.length) {
            return;
        }
        if ($(this).data('sx-rte-zone') === 'attach') {
            attach($root, Array.prototype.slice.call(dt.files));
        } else {
            insertImages($root, dt.files);
        }
    });

    // A form is not sent while pictures are still uploading.
    document.addEventListener('submit', function (e) {
        var busy = $(e.target).find('[data-sx-rte]').filter(function () {
            return isBusy(this);
        });
        if (busy.length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            notify('error', 'Дождитесь, пока загрузятся картинки.');
        }
    }, true);

    function initAll(context) {
        $(context || document).find('[data-sx-rte]').each(function () {
            init(this);
        });
    }

    $(function () {
        initAll(document);
        // Editors inserted later: AJAX forms, pjax reloads, windows.
        if (window.MutationObserver) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                        if (node.nodeType === 1) {
                            if (node.matches('[data-sx-rte]')) {
                                init(node);
                            } else if (node.querySelector('[data-sx-rte]')) {
                                initAll(node);
                            }
                        }
                    });
                });
            }).observe(document.body, {childList: true, subtree: true});
        }
    });

    window.sx = window.sx || {};
    window.sx.RichText = {
        init: function (root) {
            init(root);
        },
        initAll: initAll,
        /** @return {boolean} false when the text was not opened (a locked trusted page) */
        load: function (root) {
            $(root).data('sxRteReady', true);
            return load(root);
        },
        focus: focus,
        isEmpty: function (root) {
            return !hasContent(editorOf($(root)));
        },
        isBusy: isBusy,
        insertImages: insertImages,
        isComplex: isComplex
    };
})(jQuery);

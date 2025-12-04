(function (sx, $, _) {

    sx.createNamespace('classes', sx);

    sx.classes.LinkActivation = sx.classes.Component.extend({

        construct: function (Selector, opts) {
            opts = opts || {};
            this.Selector = Selector || false;

            this.applyParentMethod(sx.classes.Component, 'construct', [opts]);
        },

        _onDomReady: function () {
            var self = this;

            if (!this.Selector) return;

            this.jQuerySelector = $(this.Selector);

            if ($.pjax) {
                $(document).on('pjax:complete', function (e) {
                    self._replace($(self.Selector, $(e.target)));
                });
            }

            this._replace(this.jQuerySelector);
        },

        /**
         * Обработка содержания блока
         * — заменяем только текстовые узлы
         * — не трогаем существующие <a>
         */
        _replace: function (jQuerySelector) {
            var self = this;

            jQuerySelector.each(function () {
                self.processNode(this);
            });
        },

        /**
         * Рекурсивная обработка DOM-узлов
         */
        processNode: function (node) {
            var self = this;

            // Если это <a> — ничего не делаем
            if (node.nodeType === 1 && node.tagName.toLowerCase() === 'a') {
                return;
            }

            // Если это текст — обрабатываем
            if (node.nodeType === 3) {
                var original = node.nodeValue;

                // Защита сущностей
                var safe = original
                    .replace(/&nbsp;/g, '__NBSP__')
                    .replace(/&amp;/g, '__AMP__');

                // Заменяем URL на ссылки
                var replaced = safe.replace(self.getRegex(), function (url) {
                    url = url.replace(/__NBSP__$/, "")
                             .replace(/__AMP__$/, "");
                    return '<a href="' + url + '" target="_blank" data-pjax="0">' + url + '</a>';
                });

                // Если изменений нет — выходим
                if (replaced === safe) return;

                // Создаём временный контейнер
                var wrapper = document.createElement("span");
                wrapper.innerHTML = replaced;

                // Заменяем текстовый узел на HTML
                node.replaceWith(...wrapper.childNodes);
                return;
            }

            // Дети узла — обрабатываем рекурсивно
            if (node.childNodes && node.childNodes.length) {
                var children = Array.from(node.childNodes); 
                children.forEach(function (child) {
                    self.processNode(child);
                });
            }
        },

        /**
         * Универсальная регулярка URL
         */
        getRegex: function () {
            return new RegExp(
                "(https?:\\/\\/[^\\s\"'<>]+)",
                "gi"
            );
        }

    });

})(sx, sx.$, sx._);

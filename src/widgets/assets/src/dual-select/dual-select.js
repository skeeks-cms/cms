/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2018
 */
(function (sx, $, _) {
    sx.createNamespace('classes', sx);

    sx.classes.DualSelect = sx.classes.Component.extend({

        _init: function () {

        },

        _onDomReady: function () {
            var self = this;

            this.jWrapper = $("#" + this.get('id'));

            this.jHidden = $(".sx-sortable-hidden", this.jWrapper);
            this.jVisible = $(".sx-sortable-visible", this.jWrapper);

            this.jElement = $(".sx-select-element", this.jWrapper);

            this.jHidden.sortable({
                //items: "li:not(.ui-state-disabled)"
                connectWith: "." + this.get('id') + "-conncected",
                /*dropOnEmpty: false,*/
                out: function() {
                    self._update();
                }
            });

            this.jVisible.sortable({
                /*cancel: ".ui-state-disabled",*/
                connectWith: "." + this.get('id') + "-conncected",
                /*dropOnEmpty: false,*/
                out: function() {
                    self._update();
                }
            });

            this.jVisible.disableSelection();
            this.jHidden.disableSelection();

            if (this.get('ajaxUrl')) {
                this._loadItems();
            }
        },

        _loadItems: function() {
            var self = this;

            self.jWrapper.addClass('sx-dual-select-loading');

            $.getJSON(this.get('ajaxUrl'))
                .done(function(response) {
                    var items = response.items || response.columns || {};

                    _.each(items, function(label, value) {
                        var jVisibleItem = $('li', self.jVisible).filter(function() {
                            return String($(this).data('value')) === String(value);
                        });

                        if (jVisibleItem.length) {
                            jVisibleItem.text(label);
                            return;
                        }

                        if ($('li', self.jHidden).filter(function() {
                            return String($(this).data('value')) === String(value);
                        }).length) {
                            return;
                        }

                        $('<li>', {
                            'data-value': value
                        }).text(label).appendTo(self.jHidden);
                    });

                    self._update();
                })
                .always(function() {
                    self.jWrapper.removeClass('sx-dual-select-loading');
                });
        },

        _update: function () {
            var self = this;

            this.jElement.empty();

            $('li', this.jVisible).each(function() {
                self.jElement.append(
                    $('<option>', {
                        'selected' : 'selected',
                        'value' : $(this).data('value')
                    }).append($(this).text())
                )
            });

            this.trigger('change');
            this.jElement.change();
        }
    });
})(sx, sx.$, sx._);

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
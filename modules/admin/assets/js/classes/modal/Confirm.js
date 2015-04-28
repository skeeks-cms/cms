/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */

(function(sx, $, _)
{
    sx.createNamespace('classes', sx);

    sx.classes.modal.Confirm    = sx.classes.modal._Confirm.extend({

        _init: function()
        {
            this.applyParentMethod(sx.classes.modal._Confirm, '_init', []);

        },

        /**
         * @returns {sx.classes.modal._Confirm}
         */
        show: function()
        {
            var self = this;

            var jSubmit = $("<button>", {
                'class': "btn btn-primary",
                'type': "button"
            }).append(this.get('submitText', "Да"));

            var Dialog = new sx.classes.modal.Dialog({
                'title' : this.get("title", "Подтвердите ваше дейсвтие."),
                'content' : this.get("text"),
                'closeBtnText' : this.get('closeBtnText', 'Отмена'),
            });

            Dialog.bind('afterHide', function(e, data)
            {
                self.trigger("no", self);
            });

            jSubmit.on("click", function()
            {
                Dialog.hide();
                self.trigger("yes", self);
                return false;
            });

            Dialog.jFooter.prepend(jSubmit);
            Dialog.show();

            return this;
        },
    });

})(sx, sx.$, sx._);
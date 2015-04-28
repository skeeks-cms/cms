/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */

(function(sx, $, _)
{
    sx.createNamespace('classes', sx);

    sx.classes.modal.Prompt    = sx.classes.modal._Prompt.extend({

        _init: function()
        {
            this.applyParentMethod(sx.classes.modal._Prompt, '_init', []);

        },

        /**
         * @returns {sx.classes.modal._Promt}
         */
        show: function()
        {
            var self = this;

            var jInput = $("<input>", {
                'value': this.get("value", ""),
                'class': "form-control",
                'name': "sx-promt-value",
                'id': "sx-promt-value"
            });

            var jForm = $("<form>", {

            });

            jForm.on('submit', function()
            {
                var val = jInput.val();
                Dialog.hide();

                if (val)
                {
                    self.trigger("yes", val);
                }

                return false;
            });

            var jSubmit = $("<button>", {
                'class': "btn btn-primary",
                'type': "button"
            }).append(this.get('submitBtnText', "Отправить"));

            var Dialog = new sx.classes.modal.Dialog({
                'title' : this.get("text"),
                'content' : jForm.append(jInput),
                'closeBtnText' : this.get('closeBtnText', 'Отмена'),
            });

            Dialog.bind('afterHide', function(e, data)
            {
                self.trigger("no", self);
            });

            Dialog.bind('afterShow', function(e, data)
            {
                _.delay(function()
                {
                    jInput.focus();
                }, 200);

            });

            jSubmit.on("click", function()
            {
                jForm.submit();
                return false;
            });

            Dialog.jFooter.prepend(jSubmit);

            Dialog.show();

            /*var result = prompt(this.get("text"), );

            if (result)
            {
                this.trigger("yes", result);
            } else
            {
                this.trigger("no", this);
            }

            this.trigger("closed", this);

            return this;*/
        },
    });

})(sx, sx.$, sx._);
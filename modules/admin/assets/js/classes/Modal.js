/*!
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.04.2015
 */

(function(sx, $, _)
{
    sx.createNamespace('classes', sx);
    /**
     * Настройка блокировщика для админки по умолчанию. Глобальное перекрытие
     * @type {void|*|Function}
     */
    sx.classes.modal.Dialog  = sx.classes.modal._Dialog.extend({

        _init: function()
        {
            var self = this;

            this.applyParentMethod(sx.classes.modal._Dialog, '_init', []);

            this.onDomReady(function()
            {
               //Нужно рендерить
                if (!self.jWrapper()[0])
                {
                    self._render();
                }
            });
        },

        /**
         * @returns {*|HTMLElement}
         */
        jWrapper: function()
        {
            return $('#' + this.get('id'));
        },

        /**
         * @returns {*|HTMLElement}
         */
        jDialogs: function()
        {
            if (!$('#sx-dialogs')[0])
            {
                $("<div>", {
                    'id' : 'sx-dialogs',
                }).appendTo("body");
            }

            return $('#sx-dialogs');
        },

        _render: function()
        {
            var self = this;

            $("<div>", {
                'id' : this.get('id'),
                'class' : 'modal fade',
                'role' : 'dialog',
                'style' : 'display: none;'
            })
            .appendTo(this.jDialogs());

            this.jFooter = $("<div>", {'class' : 'modal-footer'}).append(
                '<button type="button" class="btn btn-default" data-dismiss="modal">' + self.get('closeBtnText', 'Закрыть') + '</button>'
            );

            this.jWrapper()
                .append(
                    $("<div>", {'class' : 'modal-dialog'}).append(
                        $("<div>", {'class' : 'modal-content'})
                            .append(
                                $("<div>", {'class' : 'modal-header'}).append(
                                    '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                    '<h4 class="modal-title" id="exampleModalLabel">' +  this.get('title')  + '</h4>'
                                )
                            )
                            .append(
                                $("<div>", {'class' : 'modal-body'}).append(
                                    this.get('content')
                                )
                            )
                            .append(this.jFooter)
                    )
                );


            this.jWrapper().on('hide.bs.modal', function (e) {
                self.trigger("afterHide", self);
            });

            this.jWrapper().on('show.bs.modal', function (e) {
                self.trigger("afterShow", self);
            });
        },


        /**
         * @returns {sx.classes.modal._Dialog}
         */
        show: function()
        {
            var self = this;
            this.trigger("beforeShow", this);
            this.isShowed = true;

            _.delay(function()
            {
                self.jWrapper().modal('show');
            }, 200);

            return this;
        },

        /**
         * @returns {sx.classes.modal._Dialog}
         */
        hide: function()
        {
            this.trigger("beforeHide", this);
            this.isShowed = false;
            this.jWrapper().modal('hide');
            return this;
        },

    });

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
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

            var content = this.get('content');
            //Если в контенте указан #sx-id
            if (content && !(content instanceof jQuery) )
            {
                if (sx.helpers.String.substr(content, 0, 1) == '#')
                {
                    if ($(content)[0])
                    {
                        content = $(content).html();
                    }
                }
            }

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
                                    content
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


})(sx, sx.$, sx._);
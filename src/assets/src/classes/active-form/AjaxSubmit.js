/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 *     
 * @see https://cms.skeeks.com/blog/410-kak-otpravit-formu-v-yii2-i-skeeks-cms-cherez-ajax
 */

(function (sx, $, _) {

    sx.createNamespace('classes.activeForm', sx);

    /**
     * @see https://cms.skeeks.com/blog/410-kak-otpravit-formu-v-yii2-i-skeeks-cms-cherez-ajax
     *
     * @event start начало отправки формы
     * @event stop завершение отправки формы
     *
     * @event success успешная отправка произведена
     * @event error ошибки, может валидация, может ajax, может ошибка данных
     *
     * @event afterValidate перед началом отправки ajax, после валидации в том числе и неудачной валидации
     *
     * @event validateAjaxComplete
     * @event validateAjaxError
     *
     * Отправка формы через ajax
     */
    sx.classes.activeForm.AjaxSubmit = sx.classes.Component.extend({

        construct: function (id, opts) {
            var self = this;
            opts = opts || {};
            this.id = id;
            this.applyParentMethod(sx.classes.Component, 'construct', [opts]); // TODO: make a workaround for magic parent calling
        },

        _onDomReady: function () {

            var self = this;

            this.jForm = $("#" + this.id);
            this.beforeSubmitProcess = false;

            this.AjaxQuery = sx.ajax.preparePostQuery(this.jForm.attr('action'));

            this.AjaxQuery.set('beforeSend', function (e) {
                self.InProgress = true;

                if (self.AjaxQuery._executing > 1) {
                    console.log('Форма уже в процессе отправки!');
                    return false;
                }
            });

            this.InProgress = null;
            this.IsSubmitProcess = false;

            this.Blocker = new sx.classes.Blocker(this.jForm);
            this.AjaxQueryHandler = new sx.classes.AjaxHandlerStandartRespose(this.AjaxQuery, {
                'enableBlocker': false,
            });


            this.AjaxQuery.on("always", function (e, data) {
                _.delay(function () {
                    self.trigger('stop');
                }, self.get('stopDelay', 500));
            });

            this.AjaxQueryHandler.on('success', function (e, data) {
                //console.log('this.AjaxQueryHandler.success');
                self.trigger('success', data);
            });

            this.AjaxQueryHandler.on('error', function (e, data) {

                if (data.data && data.data.validation) {
                    self.jForm.yiiActiveForm('updateMessages', data.data.validation, true);
                }

                self.trigger('error', {
                    'message': data.message
                });
            });


            this.on('error', function (e, data) {
                /*console.log('error');
                console.log(data);*/
            });

            this.on('start', function () {
                //console.log('start');
                self.beforeSubmitProcess = true;
                self.Blocker.block();
            });

            this.on('stop', function () {
                self.beforeSubmitProcess = false;
                self.Blocker.unblock();
                self.InProgress = false;
                self.IsSubmitProcess = false;
            });


            /*this.jForm.on('beforeValidate', function (event, messages, deferreds) {

                console.log('beforeValidate');

                self.trigger('beforeValidate', {
                    'messages' : messages,
                    'deferreds' : deferreds,
                    'event' : event,
                });
            });*/

            this.jForm.on('afterValidate', function (event, messages, errorAttributes) {

                if (self.beforeSubmitProcess === false) {
                    console.log('Это просто валидация, форму еще не отправляли');
                    return false;
                }

                if (self.InProgress === true) {
                    console.log('Еще идет предыдущая отправка!');
                    return false;
                }

                //console.log('afterValidate');

                self.trigger('afterValidate', {
                    'activeFormAjaxSubmit': self,
                    'messages': messages,
                    'errorAttributes': errorAttributes,
                    'event': event,
                });

                if (_.size(errorAttributes) > 0) {

                    self.trigger('error', {
                        'message': 'Проверьте заполненные поля в форме',
                    });
                    //sx.notify.error('Проверьте заполненные поля в форме');
                    self.trigger('stop');
                    return false;
                }

                self.AjaxQuery.setData($(this).serialize()).execute();
                /*var callback = $afterValidateCallback;
                callback(Jform, ajax);*/
                return false;
            });


            this.jForm.on('submit', function (event, attribute, message) {
                //console.log('submit');
                
                self.AjaxQuery.setUrl(self.jForm.attr('action'))

                if (self.IsSubmitProcess === false) {
                    self.IsSubmitProcess = true;

                    self.trigger('start');
                    self.trigger('submit', {
                        'message': message,
                        'attribute': attribute,
                        'event': event,
                    });
                }

                return false;
            });


            this.jForm.on('beforeSubmit', function (event, attribute, message) {
                return false;
            });


            this.jForm.on('ajaxComplete', function (event, jqXHR, textStatus) {

                //console.log('ajaxComplete');

                self.trigger('validateAjaxComplete', {
                    'e': event,
                    'jqXHR': jqXHR,
                    'textStatus': textStatus,
                });

                if (jqXHR.status == 403) {

                    self.trigger('validateAjaxError', {
                        'e': event,
                        'jqXHR': jqXHR,
                        'textStatus': textStatus,
                        'message': jqXHR.responseJSON.message,
                    });

                }
                if (jqXHR.status == 404) {

                    self.trigger('validateAjaxError', {
                        'e': event,
                        'jqXHR': jqXHR,
                        'textStatus': textStatus,
                        'message': jqXHR.responseJSON.message,
                    });
                }
            });
        }
    });
})(sx, sx.$, sx._);
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\traits;

/**
 * Class ActiveFormAjaxSubmit
 * @package skeeks\cms\traits
 */
trait ActiveFormAjaxSubmitTrait
{
    public function registerJs()
    {
        $afterValidateCallback = $this->afterValidateCallback;
        if ($afterValidateCallback) {
            $this->view->registerJs(<<<JS
                    
                $('#{$this->id}').on('beforeSubmit', function (event, attribute, message) {
                    return false;
                });

                $('#{$this->id}').on('ajaxComplete', function (event, jqXHR, textStatus) {
                    if (jqXHR.status == 403)
                    {
                        sx.notify.error(jqXHR.responseJSON.message);
                    }
                    if (jqXHR.status == 404)
                    {
                        sx.notify.error(jqXHR.responseJSON.message);
                    }
                });

                $('#{$this->id}').on('afterValidate', function (event, messages) {

                    if (event.result === false)
                    {
                        sx.notify.error('Проверьте заполненные поля в форме');
                        return false;
                    }

                    var Jform = $(this);
                    var ajax = sx.ajax.preparePostQuery($(this).attr('action'), $(this).serialize());


                    var callback = $afterValidateCallback;

                    callback(Jform, ajax);

                    ajax.execute();

                    return false;
                });

JS
            );


        } else {
            $this->view->registerJs(<<<JS

                $('#{$this->id}').on('beforeSubmit', function (event, attribute, message) {
                    return false;
                });


                $('#{$this->id}').on('afterValidate', function (event, messages) {

                    if (event.result === false)
                    {
                        sx.notify.error('Проверьте заполненные поля в форме');
                        return false;
                    }

                    var Jform = $(this);
                    var ajax = sx.ajax.preparePostQuery($(this).attr('action'), $(this).serialize());

                    var handler = new sx.classes.AjaxHandlerStandartRespose(ajax, {
                        'blockerSelector' : '#' + $(this).attr('id'),
                        'enableBlocker' : true,
                    });

                    ajax.execute();

                    return false;
                });

JS
            );
        }

    }

    public function run()
    {
        parent::run();
        $this->registerJs();
    }
}
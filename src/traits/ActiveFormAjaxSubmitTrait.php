<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\traits;

use skeeks\cms\assets\ActiveFormAjaxSubmitAsset;

/**
 * Trait ActiveFormAjaxSubmitTrait
 * @package skeeks\cms\traits
 */
trait ActiveFormAjaxSubmitTrait
{
    /**
     * @var null
     */
    public $clientCallback = null;

    /**
     * @deprecated
     * @var string
     */
    public $afterValidateCallback = "";

    public function registerJs()
    {
        ActiveFormAjaxSubmitAsset::register($this->view);

        $this->view->registerJs(<<<JS
sx.ActiveForm = new sx.classes.activeForm.AjaxSubmit('{$this->id}');
JS
    );
        $afterValidateCallback = $this->afterValidateCallback;
        $clientCallback = $this->clientCallback;

        if ($clientCallback) {
            $this->view->registerJs(<<<JS
            var callback = $clientCallback;
            callback(sx.ActiveForm);
JS
    );
        }
        elseif ($afterValidateCallback) {
            $this->view->registerJs(<<<JS
            sx.ActiveForm.AjaxQueryHandler.set();
            sx.ActiveForm.on('afterValidate', function() {
                var callback = $afterValidateCallback;
                callback(sx.ActiveForm.jForm, sx.ActiveForm.AjaxQuery);
            });
JS
    );
        }


        /*$afterValidateCallback = $this->afterValidateCallback;
        if ($afterValidateCallback) {
            $this->view->registerJs(<<<JS
            
                    
                $('#{$this->id}').on('beforeSubmit', function (event, attribute, message) {
                    console.log('beforeSubmit');
                    return false;
                });

                $('#{$this->id}').on('submit', function (event, attribute, message) {
                    console.log('submit');
                    return false;
                });

                $('#{$this->id}').on('beforeValidate', function (event, messages, deferreds) {
                    console.log('beforeValidate');
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

                $('#{$this->id}').on('afterValidate', function (event, messages, errorAttributes) {

                    console.log('afterValidate');
                    
                    if (_.size(errorAttributes) > 0)
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


                $('#{$this->id}').on('submit', function (event, attribute, message) {
                    return false;
                });

                $('#{$this->id}').on('beforeValidate', function (event, messages, deferreds) {
                });
                
                $('#{$this->id}').on('afterValidate', function (event, messages, errorAttributes) {

                    if (_.size(errorAttributes) > 0)
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
        }*/

    }

    public function run()
    {
        $this->registerJs();
        return parent::run();
    }
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.03.2015
 */
namespace skeeks\cms\base\widgets;

/**
 * Class ActiveFormAjaxSubmit
 * @package skeeks\cms\base\widgets
 */
class ActiveFormAjaxSubmit extends ActiveForm
{
    /**
     * @var Form
     */
    public $modelForm;

    public function __construct($config = [])
    {
        $this->enableAjaxValidation         = true;
        parent::__construct($config);
    }

    public function run()
    {
        parent::run();

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

            new sx.classes.AjaxHandlerBlocker(ajax, {
                'wrapper': '#' + $(this).attr('id')
            });
            //new sx.classes.AjaxHandlerNoLoader(ajax); //отключение глобального загрузчика
            new sx.classes.AjaxHandlerNotifyErrors(ajax, {
                'error': "Не удалось отправить форму",
            }); //отключение глобального загрузчика

            ajax.onError(function(e, data)
                {

                })
                .onSuccess(function(e, data)
                {
                    var response = data.response;
                    if (response.success == true)
                    {
                        $('input, select, textarea', Jform).each(function(i,s)
                        {
                            if ($(this).attr('name') != '_csrf' && $(this).attr('name') != 'sx-auto-form')
                            {
                                $(this).val('');
                            }
                        });

                        sx.notify.success(response.message);
                    } else
                    {
                        sx.notify.error(response.message);
                    }

                })
                .execute();

            return false;
        });


JS
);
    }

}

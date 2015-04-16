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

    public $afterValidateCallback = "";

    public function registerJs()
    {
        $afterValidateCallback = $this->afterValidateCallback;
        if ($afterValidateCallback)
        {
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


                        var callback = $afterValidateCallback;

                        //TODO: добавить проверки
                        callback(Jform, ajax);

                        ajax.execute();

                        return false;
                    });

JS
);


        } else
        {
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

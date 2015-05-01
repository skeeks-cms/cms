<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.03.2015
 */
namespace skeeks\cms\widgets;
use skeeks\cms\base\widgets\ActiveFormAjaxSubmit;
use skeeks\modules\cms\form\models\Form;
use yii\base\Model;

/**
 * Class ActiveForm
 * @package skeeks\modules\cms\form\widgets
 */
class ActiveFormModelPropertyValues extends ActiveFormAjaxSubmit
{
    /**
     * @var Model
     */
    public $modelWithProperties;

    public function __construct($config = [])
    {
        $this->validationUrl                = \skeeks\cms\helpers\UrlHelper::construct('cms/model-properties/validate')->toString();
        $this->action                       = \skeeks\cms\helpers\UrlHelper::construct('cms/model-properties/submit')->toString();

        $this->enableAjaxValidation         = true;

        parent::__construct($config);
    }

    public function init()
    {
        parent::init();

        echo \yii\helpers\Html::hiddenInput(Form::FROM_PARAM_ID_NAME, $this->modelWithProperties->id);
    }


    public function run()
    {
        parent::run();

        $this->view->registerJs(<<<JS

        $('#{$this->id}').on('beforeSubmit', function (event, attribute, message) {
            return false;
        });

        $('#{$this->id}').on('afterValidate', function (event, attribute, message) {

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

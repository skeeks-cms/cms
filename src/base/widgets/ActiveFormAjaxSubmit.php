<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.03.2015
 */

namespace skeeks\cms\base\widgets;

use skeeks\cms\traits\ActiveFormAjaxSubmitTrait;

/**
*
<? $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
    'clientCallback' => new \yii\web\JsExpression(<<<JS
    function (ActiveFormAjaxSubmit) {
        ActiveFormAjaxSubmit.on('success', function(e, response) {
            $("#sx-result").empty();

            if (response.data.html) {
                $("#sx-result").append(response.data.html);
            }
        });
    }
JS
)
]); ?>
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ActiveFormAjaxSubmit extends ActiveForm
{
    use ActiveFormAjaxSubmitTrait;

    public $validateOnChange = true;
    public $validateOnBlur = true;

    public function init()
    {
        //TODO: подумать над этим, но для мультиязычных сайтов это нужно
        if (!$this->action) {
            $this->action = \yii\helpers\Url::current();
        }
        parent::init();
    }


}

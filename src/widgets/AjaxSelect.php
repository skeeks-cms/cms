<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\widgets;

use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Application;
use yii\web\JsExpression;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AjaxSelect extends Select
{
    public static $autoIdPrefix = "AjaxSelect";

    /**
     * @var callable
     */
    public $dataCallback = null;

    /**
     * @var null
     */
    public $ajaxUrl = null;

    /**
     * @var null
     */
    public $valueCallback = null;

    public function init()
    {
        $ajaxOptions = [];

        if (isset($this->pluginOptions['ajax'])) {
            $ajaxOptions = $this->pluginOptions['ajax'];
        }

        $this->pluginOptions['ajax'] = ArrayHelper::merge([
            'url'      => $this->ajaxUrl ? $this->ajaxUrl : Url::current(['ajaxid' => $this->id]),
            //'url' => Url::to([\Yii::$app->controller->action->id, 'ajaxid' => $this->id]),
            'dataType' => 'json',
            'delay'    => 250,
            'cache'    => true,
            'data'     => new JsExpression('function(params) { return {q:params.term}; }'),
        ], $ajaxOptions);

        parent::init();



    }


    public function run() {

        $callback = $this->dataCallback;

        if ($callback && \Yii::$app->request->isAjax && \Yii::$app->request->get('ajaxid') == $this->id) {

            \Yii::$app->on(Application::EVENT_AFTER_ACTION, function () use ($callback) {
                ob_get_clean();
                \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                $data['results'] = call_user_func($callback, \Yii::$app->request->get('q'));
                \Yii::$app->response->data = $data;
                \Yii::$app->end();
            });
            //ob_get_clean();

        } elseif ($this->value && $this->valueCallback) {
            $callback = $this->valueCallback;
            $this->data = call_user_func($callback, $this->value);
        }



        parent::run();
    }

}
<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\helpers;

use yii\base\Model;
use yii\helpers\Json;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class RequestResponse extends Model
{
    /**
     *
     */
    const DYNAMIC_RELOAD_FIELD_ELEMENT = 'data-form-reload';

    /**
     *
     */
    const DYNAMIC_RELOAD_NOT_SUBMIT = 'sx-reload-form';
    /**
     * Параметр, который говорит что запрос пришел на валидацию формы
     */
    const VALIDATION_AJAX_FORM_SYSTEM_NAME = 'sx-validation';

    public $success = false;
    public $message = '';
    public $data = [];

    public $redirect;

    public function init()
    {
        parent::init();

        if (\Yii::$app->request->isAjax) {
            $this->setResponseFormatJson();
        }
    }

    /**
     * Ответ в формате JSON
     * @return $this
     */
    public function setResponseFormatJson()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $this;
    }

    /**
     * Запрос пришел на валидацию ajax формы?
     * @return bool
     */
    public function isRequestOnValidateAjaxForm()
    {
        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax && UrlHelper::getCurrent()->issetSystemParam(static::VALIDATION_AJAX_FORM_SYSTEM_NAME)) {
            return true;
        }

        return false;
    }


    /**
     * @return bool
     */
    public function isRequestAjaxPost()
    {
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isRequestPjaxPost()
    {
        if (\Yii::$app->request->isPjax && \Yii::$app->request->isPost) {
            return true;
        }

        return false;
    }

    /**
     * @param Model $model
     * @return array
     */
    public function ajaxValidateForm(Model $model)
    {
        $model->load(\Yii::$app->request->post());
        return ActiveForm::validate($model);
    }

    public function __toString()
    {
        return Json::encode($this->toArray());
    }

    /**
     * @param $model
     * @return $this
     */
    public function addModelErrors($model)
    {
        $models = [];
        if (is_array($model)) {
            $models = $model;
        } else {
            $models = [$model];
        }

        $result = [];

        foreach ($models as $m) {
            foreach ($m->getErrors() as $attribute => $errors) {
                $result[\yii\helpers\Html::getInputId($m, $attribute)] = $errors;
            }
        }

        $this->data['validation'] = $result;
        return $this;
    }
}
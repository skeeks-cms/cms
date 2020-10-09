<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

/* @var $this yii\web\View */

namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\relatedProperties\PropertyType;
use yii\web\Controller;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AjaxController extends Controller
{
    /**
     * @return array
     */
    public function actionAutocompleteEavOptions()
    {
        $result = [];

        $code = (string) \Yii::$app->request->get("code");
        if (!$code) {
            return $result;
        }

        /**
         * @var $property CmsContentProperty
         */
        if (!$property = CmsContentProperty::find()->where(['code' => $code])->one()) {
            return $result;
        }

        if ($property->property_type == PropertyType::CODE_LIST) {
            $query = CmsContentPropertyEnum::find()->andWhere(['property_id' => $property->id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'value', $q]);
            }

            $data = $query->limit(25)
                        ->all();

            $result = [];

            if ($data) {
                foreach ($data as $model) {
                    $result[] = [
                        'id'   => $model->id,
                        'text' => $model->value,
                    ];
                }
            }
        } elseif ($property->property_type == PropertyType::CODE_ELEMENT) {
            if (!isset($property->handler->content_id) || ! $property->handler->content_id) {
                return $result;
            }

            $query = CmsContentElement::find()->active()->andWhere(['content_id' => $property->handler->content_id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'name', $q]);
            }

            $data = $query->limit(25)
                        ->all();

            $result = [];

            if ($data) {
                foreach ($data as $model) {
                    $result[] = [
                        'id'   => $model->id,
                        'text' => $model->name,
                    ];
                }
            }
        }


        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return ['results' => $result];
    }

}

<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

/* @var $this yii\web\View */

namespace skeeks\cms\controllers;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\models\CmsCountry;
use skeeks\cms\models\CmsTreeProperty;
use skeeks\cms\models\CmsTreeTypeProperty;
use skeeks\cms\models\CmsTreeTypePropertyEnum;
use skeeks\cms\models\CmsUserUniversalProperty;
use skeeks\cms\models\CmsUserUniversalPropertyEnum;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\cms\shop\models\ShopBrand;
use skeeks\cms\shop\models\ShopCollection;
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
        $property_id = (int) \Yii::$app->request->get("property_id");
        if (!$property_id) {
            return $result;
        }

        $propertyClass = CmsContentProperty::class;
        $propertyEnumClass = CmsContentPropertyEnum::class;
        
        if (\Yii::$app->request->get("property_class")) {
            $propertyClass = (string) \Yii::$app->request->get("property_class");
        }
        
        if (\Yii::$app->request->get("property_enum_class")) {
            $propertyEnumClass = (string) \Yii::$app->request->get("property_enum_class");
        }
        
        $q = $propertyClass::find()->cmsSite()->andWhere(['id' => $property_id]);

        /**
         * @var $property CmsContentProperty
         */
        if (!$property = $q->one()) {
            return $result;
        }
        

        if ($property->property_type == PropertyType::CODE_LIST) {
            $query = $propertyEnumClass::find()->andWhere(['property_id' => $property->id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'value', $q]);
            }

            $data = $query->limit(50)
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

            $query = CmsContentElement::find()->cmsSite()->active()->andWhere(['content_id' => $property->handler->content_id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'name', $q]);
            }

            $data = $query->limit(50)
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
    /**
     * @return array
     */
    public function actionAutocompleteUserEavOptions()
    {
        $result = [];

        $property_id = (int) \Yii::$app->request->get("property_id");
        if (!$property_id) {
            return $result;
        }


        $propertyClass = CmsUserUniversalProperty::class;
        $propertyEnumClass = CmsUserUniversalPropertyEnum::class;

        if (\Yii::$app->request->get("property_class")) {
            $propertyClass = (string) \Yii::$app->request->get("property_class");
        }

        if (\Yii::$app->request->get("property_enum_class")) {
            $propertyEnumClass = (string) \Yii::$app->request->get("property_enum_class");
        }

        /**
         * @var $property CmsContentProperty
         */
        if (!$property = $propertyClass::find()->cmsSite()->andWhere(['id' => $property_id])->one()) {
            return $result;
        }

        if ($property->property_type == PropertyType::CODE_LIST) {
            $query = $propertyEnumClass::find()->andWhere(['property_id' => $property->id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'value', $q]);
            }

            $data = $query->limit(50)
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

            $query = CmsContentElement::find()->cmsSite()->active()->andWhere(['content_id' => $property->handler->content_id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'name', $q]);
            }

            $data = $query->limit(50)
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

    /**
     * @return array
     */
    public function actionAutocompleteTreeEavOptions()
    {
        $result = [];

        $property_id = (int) \Yii::$app->request->get("property_id");
        if (!$property_id) {
            return $result;
        }


        /**
         * @var $property CmsContentProperty
         */
        if (!$property = CmsTreeTypeProperty::find()->where(['property_id' => $property_id])->one()) {
            return $result;
        }

        if ($property->property_type == PropertyType::CODE_LIST) {
            $query = CmsTreeTypePropertyEnum::find()->andWhere(['property_id' => $property->id]);

            if ($q = \Yii::$app->request->get('q')) {
                $query->andWhere(['like', 'value', $q]);
            }

            $data = $query->limit(50)
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

            $data = $query->limit(50)
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


    /**
     * @return void
     */
    public function actionAdult()
    {
        $rr = new RequestResponse();

        if (\Yii::$app->request->post("is_allow")) {
            \Yii::$app->adult->isAllowAdult = true;
            $rr->success = true;
        }

        return $rr;
    }

    /**
     * @return array
     */
    public function actionAutocompleteCountries()
    {
        $result = [];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;


        $query = CmsCountry::find();

        if ($q = \Yii::$app->request->get('q')) {
            $query->search($q);
        }

        $data = $query->limit(100)
            ->all();

        if ($data) {

            /**
             * @var $model CmsCountry
             */
            foreach ($data as $model) {
                $result[] = [
                    'id'   => $model->alpha2,
                    'text' => $model->name,
                ];
            }
        }

        return ['results' => $result];
    }
    
    /**
     * @return array
     */
    public function actionAutocompleteCollections()
    {
        $result = [];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $query = ShopCollection::find();

        if ($q = \Yii::$app->request->get('q')) {
            $query->search($q);
        }
        if ($brand_id = \Yii::$app->request->get('brand_id')) {
            $query->andWhere(['shop_brand_id' => $brand_id]);
        }

        $data = $query->limit(100)
            ->all();

        if ($data) {

            /**
             * @var $model CmsContentElement
             */
            foreach ($data as $model) {
                $result[] = [
                    'id'   => $model->id,
                    'text' => $model->name,
                ];
            }
        }

        return ['results' => $result];
    }
    
    public function actionWebNotifiesNew() {
        $rr = new RequestResponse();
        $rr->success = true;

        if (\Yii::$app->user->isGuest) {
            $rr->data = [
                'total' => 0,
                'items' => [],
            ];

            return $rr;
        }

        $qNotifies = \Yii::$app->user->identity->getCmsWebNotifies()->notRead()->limit(3);

        if ($last_notify_id = (int) \Yii::$app->request->post("last_notify_id")) {
            $qNotifies->andWhere(['>', 'id', $last_notify_id]);
        }

        $qNotifiesNotPopups = $qNotifies->all();
        $qNotifiesNotPopupsArray = [];

        if ($qNotifiesNotPopups) {
            /**
             * @var \skeeks\cms\models\CmsWebNotify[] $qNotifiesNotPopups
             */
            foreach ($qNotifiesNotPopups as $qNotifiesNotPopup)
            {
                $qNotifiesNotPopupsArray[] = \yii\helpers\ArrayHelper::merge(['render' => $qNotifiesNotPopup->getHtml()], $qNotifiesNotPopup->toArray());
            }
        }

        $rr->data = [
            'total' => \Yii::$app->user->identity->getCmsWebNotifies()->notRead()->count(),
            'items' => $qNotifiesNotPopupsArray,
        ];

        return $rr;
    }
    public function actionWebNotifiesClear() {
        $rr = new RequestResponse();
        $rr->success = true;

        if (\Yii::$app->user->isGuest) {

            return $rr;
        }

        $qNotifies = \Yii::$app->user->identity->getCmsWebNotifies();

        if ($qNotifies->count()) {
            /**
             * @var \skeeks\cms\models\CmsWebNotify[] $qNotifiesNotPopups
             */
            foreach ($qNotifies->each(10) as $qNotifiesNotPopup)
            {
                $qNotifiesNotPopup->delete();
            }
        }

        return $rr;
    }

    public function actionWebNotifies()
    {
        $rr = new RequestResponse();
        $rr->success = true;

        if (\Yii::$app->user->isGuest) {
            $rr->data = [
                'total' => 0,
                'items' => [],
            ];

            return $rr;
        }


        $qNotifiesAll = \Yii::$app->user->identity->getCmsWebNotifies()->orderBy(['is_read' => SORT_ASC, 'created_at' => SORT_DESC])->limit(40);

        $qNotifiesNotPopupsArray = [];
        $qNotifiesNotPopups = $qNotifiesAll->all();

        if ($qNotifiesNotPopups) {
            /**
             * @var \skeeks\cms\models\CmsWebNotify[] $qNotifiesNotPopups
             */
            foreach ($qNotifiesNotPopups as $qNotifiesNotPopup)
            {
                $qNotifiesNotPopupsArray[] = \yii\helpers\ArrayHelper::merge(['render' => $qNotifiesNotPopup->getHtml()], $qNotifiesNotPopup->toArray());

                $qNotifiesNotPopup->is_read = 1;
                $qNotifiesNotPopup->update(false, ['is_read']);
            }
        }

        $qNotifies = \Yii::$app->user->identity->getCmsWebNotifies()->notRead();

        $rr->data = [
            'total' => $qNotifies->count(),
            'items' => $qNotifiesNotPopupsArray,
        ];

        return $rr;
    }

    /**
     * @return array
     */
    public function actionAutocompleteBrands()
    {
        $result = [];

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $query = ShopBrand::find();

        if ($q = \Yii::$app->request->get('q')) {
            $query->search($q);
        }

        $data = $query->limit(100)
            ->all();

        if ($data) {

            /**
             * @var $model CmsContentElement
             */
            foreach ($data as $model) {
                $result[] = [
                    'id'   => $model->id,
                    'text' => $model->name,
                ];
            }
        }

        return ['results' => $result];
    }

}

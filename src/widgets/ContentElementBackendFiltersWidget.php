<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets;

use skeeks\cms\backend\actions\BackendGridModelAction;
use skeeks\cms\backend\widgets\FiltersWidget;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorAction;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\yii2\config\ConfigBehavior;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use skeeks\yii2\queryfilter\QueryFilterWidget;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ContentElementBackendFiltersWidget extends FiltersWidget
{
    public $disableAutoFilters = [
        'updated_by',
        'published_at',
        'published_to',
        'content_id',
        'show_counter_start',
        'description_short_type',
        'description_full_type',
        'images',
        'imageIds',
        'files',
        'fileIds',
        'main_cce_id',
        'image_id',
        'image_full_id',
        'treeids',
    ];


    public function init()
    {
        $this->fieldConfigCallback = function($code) {
            if (strpos($code, "property") != -1) {

                $propertyId = (int) str_replace("property", "", $code);
                /**
                 * @var $property CmsContentProperty
                 */
                $property = CmsContentProperty::findOne($propertyId);

                $result = [
                    'class'    => TextField::class
                ];

                if (!$property) {
                    return $result;
                }

                if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_STRING) {
                    $result = [
                        'class'    => TextField::class,
                        'label'    => $property->name,
                        'on apply' => function (QueryFiltersEvent $e) use ($property) {
                            /**
                             * @var $query ActiveQuery
                             */
                            $query = $e->dataProvider->query;


                            if ($e->field->value) {
                                $query1 = CmsContentElementProperty::find()->select(['element_id as id'])
                                    ->where([
                                        "property_id" => $property->id,
                                    ])
                                    ->andWhere([
                                        'like',
                                        'value',
                                        $e->field->value,
                                    ]);

                                $query->andWhere([
                                    CmsContentElement::tableName().".id" => $query1,
                                ]);
                            }
                        },
                    ];
                } elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_BOOL) {

                    $result = [
                        'class'    => BoolField::class,
                        'label'    => $property->name,
                        'on apply' => function (QueryFiltersEvent $e) use ($property) {
                            /**
                             * @var $query ActiveQuery
                             */
                            $query = $e->dataProvider->query;


                            if ($e->field->value) {
                                $query1 = CmsContentElementProperty::find()->select(['element_id as id'])
                                    ->where([
                                        "value_bool"  => $e->field->value,
                                        "property_id" => $property->id,
                                    ]);

                                $query->andWhere([
                                    CmsContentElement::tableName().".id" => $query1,
                                ]);
                            }
                        },
                    ];

                } elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_NUMBER) {


                } elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_LIST) {

                    $find = CmsContentPropertyEnum::find()->where(['property_id' => $property->id]);
                    $result = [
                        'label'    => $property->name,
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelect::class,
                        'widgetConfig' => [
                            'multiple'      => true,
                            'ajaxUrl'       => Url::to(['/cms/ajax/autocomplete-eav-options', 'property_id' => $property->id, 'cms_site_id' => \Yii::$app->skeeks->site->id]),
                            'valueCallback' => function ($value) {
                                return \yii\helpers\ArrayHelper::map(CmsContentPropertyEnum::find()->where(['id' => $value])->all(), 'id', 'value');
                            },
                        ],
                        'on apply' => function (QueryFiltersEvent $e) use ($property) {
                            /**
                             * @var $query ActiveQuery
                             */
                            $query = $e->dataProvider->query;

                            if ($e->field->value) {
                                $query1 = CmsContentElementProperty::find()->select(['element_id as id'])
                                    ->where([
                                        "value_enum"  => $e->field->value,
                                        "property_id" => $property->id,
                                    ]);

                                $query->andWhere([
                                    CmsContentElement::tableName().".id" => $query1,
                                ]);
                            }
                        }
                    ];


                    /*$autoFilters["property{$property->id}"]['label'] = $property->name;
                    $autoFilters["property{$property->id}"]["on apply"] = function (QueryFiltersEvent $e) use ($property) {
                        /**
                         * @var $query ActiveQuery
                        $query = $e->dataProvider->query;

                        if ($e->field->value) {
                            $query1 = CmsContentElementProperty::find()->select(['element_id as id'])
                                ->where([
                                    "value_enum"  => $e->field->value,
                                    "property_id" => $property->id,
                                ]);

                            $query->andWhere([
                                CmsContentElement::tableName().".id" => $query1,
                            ]);
                        }
                    };*/


                } elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT) {

                    $propertyType = $property->handler;

                    $find = CmsContentElement::find()->where(['content_id' => $propertyType->content_id]);
                    $result = [
                        'label'    => $property->name,
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelect::class,
                        'widgetConfig' => [
                            'multiple'      => true,
                            'ajaxUrl'       => Url::to(['/cms/ajax/autocomplete-eav-options', 'property_id' => $property->id, 'cms_site_id' => \Yii::$app->skeeks->site->id]),
                            'valueCallback' => function ($value) {
                                return \yii\helpers\ArrayHelper::map(CmsContentElement::find()->where(['id' => $value])->all(), 'id', 'name');
                            },
                        ],

                        'on apply' => function (QueryFiltersEvent $e) use ($property) {
                            /**
                             * @var $query ActiveQuery
                             */
                            $query = $e->dataProvider->query;


                            if ($e->field->value) {
                                $query1 = CmsContentElementProperty::find()->select(['element_id as id'])
                                    ->where([
                                        "value_enum"  => $e->field->value,
                                        "property_id" => $property->id,
                                    ]);

                                $query->andWhere([
                                    CmsContentElement::tableName().".id" => $query1,
                                ]);
                            }
                        }

                    ];

                    /*$autoFilters["property{$property->id}"]["label"] = $property->name;
                    $autoFilters["property{$property->id}"]["on apply"] = function (QueryFiltersEvent $e) use ($property) {
                        /**
                         * @var $query ActiveQuery
                        $query = $e->dataProvider->query;


                        if ($e->field->value) {
                            $query1 = CmsContentElementProperty::find()->select(['element_id as id'])
                                ->where([
                                    "value_enum"  => $e->field->value,
                                    "property_id" => $property->id,
                                ]);

                            $query->andWhere([
                                CmsContentElement::tableName().".id" => $query1,
                            ]);
                        }
                    };*/


                } elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_TREE) {
                    $propertyType = $property->handler;
                    $result = [
                        'class'       => WidgetField::class,
                        'widgetClass' => \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class,
                        'label'       => $property->name,
                        'on apply'    => function (QueryFiltersEvent $e) use ($property) {
                            /**
                             * @var $query ActiveQuery
                             */
                            $query = $e->dataProvider->query;


                            if ($e->field->value) {
                                $query1 = CmsContentElementProperty::find()->select(['element_id as id'])
                                    ->where([
                                        "value_enum"  => $e->field->value,
                                        "property_id" => $property->id,
                                    ]);

                                $query->andWhere([
                                    CmsContentElement::tableName().".id" => $query1,
                                ]);
                            }
                        },
                    ];
                }

                return $result;
            }
        };

        //$this->initFiltersModel();

        parent::init();
    }

    /**
     * @param $callableData
     * @return array
     */
    public function getAvailableFields($callableData)
    {
        $result = parent::getAvailableFields($callableData);


        $content_id = ArrayHelper::getValue($callableData, 'callAttributes.contextData.content_id');
        $cmsContent = CmsContent::findOne($content_id);

        $properties = $cmsContent->getCmsContentProperties();
        $properties->andWhere([
            'or',
            [CmsContentProperty::tableName().'.cms_site_id' => \Yii::$app->skeeks->site->id],
            [CmsContentProperty::tableName().'.cms_site_id' => null],
        ]);
        $properties = $properties->all();

        /**
         * @var CmsContentProperty $property
         */
        foreach ($properties as $property) {
            $result["property{$property->id}"] = $property->name;
        }

        return $result;
    }
}
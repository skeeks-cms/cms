<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsLang;
use skeeks\cms\models\CmsSavedFilter;
use skeeks\cms\queryfilters\filters\FilterField;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget;
use skeeks\cms\widgets\formInputs\selectTree\DaterangeInputWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSavedFilterController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Сохраненные фильтры");
        $this->modelShowAttribute = "asText";
        $this->modelClassName = CmsSavedFilter::class;

        $this->generateAccessActions = false;
        $this->permissionName = "cms/admin-cms-saved-filter";

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index'  => [
                /*"filters" => [
                    'visibleFilters' => [
                        'short_name',
                    ],
                ],*/
                "filters" => [
                    'visibleFilters' => [
                        'q',
                        'cms_content_property_id',
                        'shop_brand_id',
                        'country_alpha2',
                    ],
                    'filtersModel'   => [
                        'rules'            => [
                            ['q', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                        ],

                        'fields' => [

                            'q' => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {

                                        $query->joinWith("valueContentElement as valueContentElement");
                                        $query->joinWith("valueContentPropertyEnum as valueContentPropertyEnum");
                                        $query->joinWith("brand as brand");
                                        $query->joinWith("country as country");

                                        $query->andWhere([
                                            'or',
                                            ['like', CmsSavedFilter::tableName() .'.short_name', $e->field->value],
                                            ['like', 'valueContentElement.name', $e->field->value],
                                            ['like', 'valueContentPropertyEnum.value', $e->field->value],
                                            ['like', 'brand.name', $e->field->value],
                                            ['like', 'country.name', $e->field->value],
                                        ]);

                                        $query->groupBy([CmsSavedFilter::tableName().'.id']);
                                    }
                                },
                            ],

                            /*'cms_content_property_id'     => [
                                'class' => FilterField::class,
                                'field'             => [
                                    'class'             => WidgetField::class,
                                    'widgetClass' => AjaxSelectModel::class,
                                    'widgetConfig'      => [
                                        'modelClass' => CmsContentProperty::class,
                                        'multiple' => true,
                                        'searchQuery' => function($word = '') {

                                            $query = CmsContentProperty::find()->cmsSite();

                                            if ($word) {
                                                $query->search($word);
                                            }

                                            return $query;
                                        },
                                    ],
                                ],
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                    $query = $e->dataProvider->query;
                                    if ($e->field->value) {
                                        $query->innerJoin(['auth_assignment_role' => 'auth_assignment'], 'auth_assignment_role.cms_user_id = cms_user.id');
                                        $query->andFilterWhere([
                                            'auth_assignment_role.item_name' => $e->field->value,
                                        ]);
                                    }

                                },
                            ],*/
                        ],
                    ],
                ],
                'grid'    => [
                    
                    'on init'       => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $dataProvider = $e->sender->dataProvider;
                        $query->cmsSite();
                    },
                    
                    'defaultOrder' => [
                        'priority' => SORT_ASC,
                        'id' => SORT_DESC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',

                        'custom',
                        //'code',
                        'cms_content_property_id',
                        'v',

                        'shop_brand_id',
                        'country_alpha2',

                        //'value_content_element_id',
                        //'value_content_property_enum_id',
                        'priority',
                        'view',
                    ],
                    'columns'        => [
                        'created_at'       =>  [
                            'class' => DateTimeColumnData::class
                        ],
                        'updated_at'       =>  [
                            'class' => DateTimeColumnData::class
                        ],
                        'shop_brand_id'       => [
                            'attribute' => 'shop_brand_id',
                            'format' => 'raw',
                            'value' => function (CmsSavedFilter $model) {
                                return $model->shop_brand_id ? $model->brand->name : "";
                            }
                        ],
                        'country_alpha2'       => [
                            'attribute' => 'country_alpha2',
                            'format' => 'raw',
                            'value' => function (CmsSavedFilter $model) {
                                return $model->country_alpha2 ? $model->country->name : "";
                            }
                        ],
                        'cms_content_property_id'       => [
                            'attribute' => 'cms_content_property_id',
                            'format' => 'raw',
                            'value' => function (CmsSavedFilter $model) {
                                return $model->cms_content_property_id ? $model->cmsContentProperty->name : "";
                            }
                        ],

                        'value_content_element_id'       => [
                            'attribute' => 'value_content_element_id',
                            'format' => 'raw',
                            'value' => function (CmsSavedFilter $model) {
                                return $model->value_content_element_id ? $model->valueContentElement->name : "";
                            }
                        ],

                        'value_content_property_enum_id'       => [
                            'attribute' => 'value_content_property_enum_id',
                            'format' => 'raw',
                            'value' => function (CmsSavedFilter $model) {
                                return $model->value_content_property_enum_id ? $model->valueContentPropertyEnum->value : "";
                            }
                        ],

                        'v'       => [
                            'label' => 'Значение',
                            'format' => 'raw',
                            'value' => function (CmsSavedFilter $model) {

                                if ($model->value_content_property_enum_id) {
                                    return $model->valueContentPropertyEnum->value;
                                }

                                if ($model->value_content_element_id) {
                                    return $model->valueContentElement->name;
                                }

                                return "";
                            }
                        ],

                        'custom'       => [
                            'attribute' => 'id',
                            'format' => 'raw',
                            'value' => function (CmsSavedFilter $model) {

                                $data = [];
                                $data[] = Html::a($model->seoName, "#", ['class' => 'sx-trigger-action']);


                                if ($model->cmsTree) {
                                    $data[] = '<i class="far fa-folder" style="color: gray;"></i> '.Html::a($model->cmsTree->name, $model->cmsTree->url, [
                                            'data-pjax' => '0',
                                            'target'    => '_blank',
                                            'title'     => $model->cmsTree->fullName,
                                            'style'     => 'color: #333; max-width: 200px; display: inline-block; color: gray; cursor: pointer; white-space: nowrap; border-bottom: none;',
                                        ]);
                                }
                                $info = implode("<br />", $data);

                                return "<div class='row no-gutters'>
                                                <div class='sx-trigger-action' style='width: 50px;'>
                                                <a href='#' style='text-decoration: none; border-bottom: 0;'>
                                                    <img src='". ($model->image ? $model->image->src : Image::getCapSrc()) ."' style='max-width: 50px; max-height: 50px; border-radius: 5px;' />
                                                </a>
                                                </div>
                                                <div style='margin-left: 5px;'>" . $info  . /*. "<br />(" . $model->code . ")*/
                                                    "</div></div>";

                                            ;
                            }
                        ],

                        'priority'     => [
                            'headerOptions'  => [
                                'style' => 'max-width: 100px; width: 100px;',
                            ],
                        ],

                        'view' => [
                            'value'          => function (CmsSavedFilter $model) {
                                return \yii\helpers\Html::a('<i class="fas fa-external-link-alt"></i>', $model->absoluteUrl,
                                    [
                                        'target'    => '_blank',
                                        'title'     => \Yii::t('skeeks/cms', 'Watch to site (opens new window)'),
                                        'data-pjax' => '0',
                                        'class'     => 'btn btn-sm',
                                    ]);
                            },
                            'format'         => 'raw',
                            /*'label'  => "Смотреть",*/
                            'headerOptions'  => [
                                'style' => 'max-width: 40px; width: 40px;',
                            ],
                        ],
                    ],
                ],
            ],
            "create" => new UnsetArrayValue(),
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],
        ]);
    }

    public function updateFields($action)
    {
        $rootTreeModels = \skeeks\cms\models\CmsTree::findRoots()->cmsSite()->joinWith('cmsSiteRelation')->orderBy([\skeeks\cms\models\CmsSite::tableName().".priority" => SORT_ASC])->all();

        return [

            /*'cms_tree_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => SelectTreeInputWidget::class,
                'widgetConfig' => [
                    'options'           => [
                        'data-form-reload' => 'true',
                    ],
                    'multiple'          => false,
                    'treeWidgetOptions' =>
                        [
                            'models' => $rootTreeModels,
                        ],
                ]
            ],*/

            /*'cms_content_property_id',

            'value_content_element_id',
            'value_content_property_enum_id',*/

            'short_name',

            'cms_image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],

            'code',
            'priority' => [
                'class' => NumberField::class
            ],

            'seo_h1',
            'meta_title' => [
                'class' => TextareaField::class
            ],
            'meta_description' => [
                'class' => TextareaField::class
            ],
            'meta_keywords' => [
                'class' => TextareaField::class
            ],

            'description_short' => [
                'class' => WidgetField::class,
                'widgetClass' => ComboTextInputWidget::class,
                'widgetConfig' => [
                    'modelAttributeSaveType' => 'description_short_type',
                ]
            ],
            'description_full' => [
                'class' => WidgetField::class,
                'widgetClass' => ComboTextInputWidget::class,
                'widgetConfig' => [
                    'modelAttributeSaveType' => 'description_full_type',
                ]
            ],
        ];
    }
}

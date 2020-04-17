<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\ActiveFormBackend;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\measure\models\CmsMeasure;
use skeeks\cms\models\CmsTreeTypeProperty;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\yii2\form\Element;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class AdminCmsTreeTypePropertyController
 * @package skeeks\cms\controllers
 */
class AdminCmsTreeTypePropertyController extends BackendModelStandartController
{
    public $notSubmitParam = 'sx-not-submit';

    public function init()
    {
        $this->name = "Управление свойствами раздела";
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsTreeTypeProperty::className();

        $this->generateAccessActions = false;

        $this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return \Yii::$app->user->can($this->uniqueId);
        };

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                "filters" => [
                    'visibleFilters' => [
                        'q',
                    ],

                    'filtersModel' => [
                        'rules' => [
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
                                        $query->andWhere([
                                            'or',
                                            ['like', CmsTreeTypeProperty::tableName().'.name', $e->field->value],
                                            ['like', CmsTreeTypeProperty::tableName().'.code', $e->field->value],
                                            ['like', CmsTreeTypeProperty::tableName().'.id', $e->field->value],
                                        ]);

                                        $query->groupBy([CmsTreeTypeProperty::tableName().'.id']);
                                    }
                                },
                            ],
                        ],
                    ],
                ],

                "grid" => [
                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $dataProvider = $e->sender->dataProvider;

                        $query->joinWith('elementProperties as elementProperties');
                        $query->groupBy(CmsTreeTypeProperty::tableName().".id");
                        $query->select([
                            CmsTreeTypeProperty::tableName().'.*',
                            'countElementProperties' => new Expression("count(*)"),
                        ]);
                    },

                    'sortAttributes' => [
                        'countElementProperties' => [
                            'asc'     => ['countElementProperties' => SORT_ASC],
                            'desc'    => ['countElementProperties' => SORT_DESC],
                            'label'   => \Yii::t('skeeks/cms', 'Number of partitions where the property is filled'),
                            'default' => SORT_ASC,
                        ],
                    ],
                    'defaultOrder'   => [
                        //'def' => SORT_DESC,
                        'priority' => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        //'id',
                        //'image_id',
                        'is_active',
                        'priority',
                        //'name',
                        'countElementProperties',
                        'sections',
                        //'domains',
                    ],
                    'columns'        => [
                        'custom' => [
                            'attribute' => "name",
                            'format'    => "raw",
                            'value'     => function (CmsTreeTypeProperty $model) {
                                return Html::a($model->asText, "#", [
                                        'class' => "sx-trigger-action",
                                    ])."<br />".Html::tag('small', $model->handler->name);
                            },
                        ],

                        'sections' => [
                            'attribute' => \Yii::t('skeeks/cms', 'Sections'),
                            'format'    => "raw",
                            'value'     => function (CmsTreeTypeProperty $model) {
                                $contents = \yii\helpers\ArrayHelper::map($model->cmsTreeTypes, 'id', 'name');
                                return implode(', ', $contents);
                            },
                        ],

                        'countElementProperties' => [
                            'attribute' => 'countElementProperties',
                            'format'    => 'raw',
                            'label'     => \Yii::t('skeeks/cms', 'Number of partitions where the property is filled'),
                            'value'     => function (CmsTreeTypeProperty $model) {
                                return Html::a($model->raw_row['countElementProperties'], [
                                    '/cms/admin-tree/list',
                                    'DynamicModel' => [
                                        'fill' => $model->id,
                                    ],
                                ], [
                                    'data-pjax' => '0',
                                    'target'    => '_blank',
                                ]);
                            },
                        ],

                        'is_active' => [
                            'class' => BooleanColumn::class,
                        ],

                    ],
                ],
            ],

            'create' => [
                'fields' => [$this, 'updateFields'],

                //'activeFormClassName' => ActiveFormBackend::class,

                'on beforeSave' => function (Event $e) {
                    $model = $e->sender->model;

                    $handler = $model->handler;
                    if ($handler) {
                        if ($post = \Yii::$app->request->post()) {
                            $handler->load($post);
                        }
                        $model->component_settings = $handler->toArray();
                    }
                },

            ],
            'update' => [
                'fields' => [$this, 'updateFields'],

                'on beforeSave' => function (Event $e) {
                    $model = $e->sender->model;


                    $handler = $model->handler;
                    if ($handler) {
                        if ($post = \Yii::$app->request->post()) {
                            $handler->load($post);
                        }
                        $model->component_settings = $handler->toArray();
                    }

                },
            ],

            'enums' => [
                'class'           => BackendGridModelRelatedAction::class,
                'accessCallback'  => true,
                'name'            => "Элементы списка",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-tree-type-property-enum",
                'relation'        => ['property_id' => 'id'],
                'priority'        => 150,

                'on gridInit'        => function($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'property_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },

                'accessCallback' => function (BackendModelAction $action) {

                    /**
                     * @var $model CmsContentProperty
                     */
                    $model = $action->model;

                    if (!$model) {
                        return false;
                    }

                    if ($model->property_type != PropertyType::CODE_LIST) {
                        return false;
                    }

                    return true;
                },
            ],

            /*'create' => [
                'callback' => [$this, 'create'],
            ],

            'update' => [
                'callback' => [$this, 'update'],
            ],*/
        ]);
    }


    public function updateFields($action)
    {
        /**
         * @var $model CmsTreeTypeProperty
         */
        $model = $action->model;
        //$model->load(\Yii::$app->request->get());

        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Basic settings'),
                'fields' => [
                    'is_required' => [
                        'class'      => BoolField::class,
                        'allowNull'  => false,
                    ],
                    'is_active'      => [
                        'class'      => BoolField::class,
                        'allowNull'  => false,
                    ],
                    'name',
                    'code',
                    'component'   => [
                        'class'          => SelectField::class,
                        'elementOptions' => [
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ],
                        'items'          => function () {
                            return array_merge(['' => ' — '], \Yii::$app->cms->relatedHandlersDataForSelect);
                        },
                    ],
                    'cms_measure_code'   => [
                        'class'          => SelectField::class,
                        /*'elementOptions' => [
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ],*/
                        'items'          => function () {
                            return ArrayHelper::map(
                                CmsMeasure::find()->all(),
                                'code',
                                'asShortText'
                            );
                        },
                    ],
                    [
                        'class'           => HtmlBlock::class,
                        'on beforeRender' => function (Event $e) use ($model) {
                            /**
                             * @var $formElement Element
                             */
                            $formElement = $e->sender;
                            $formElement->activeForm;

                            $handler = $model->handler;

                            if ($handler) {
                                if ($post = \Yii::$app->request->post()) {
                                    $handler->load($post);
                                }
                                if ($handler instanceof \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList) {
                                    $handler->enumRoute = 'cms/admin-cms-tree-type-property-enum';
                                }

                                $content = $handler->renderConfigFormFields($formElement->activeForm);

                                if ($content) {
                                    $formElement->content = \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => \Yii::t('skeeks/cms', 'Settings')]) . $content;
                                }

                            }
                        },
                    ],
                ],
            ],

            'captions' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Additionally'),
                'fields' => [
                    'hint',
                    'priority',
                    'cmsTreeTypes' => [
                        'class'    => SelectField::class,
                        'multiple' => true,
                        'items'    => function () {
                            return \yii\helpers\ArrayHelper::map(
                                \skeeks\cms\models\CmsTreeType::find()->all(), 'id', 'name'
                            );
                        },
                    ],
                ],
            ],
        ];
    }
}

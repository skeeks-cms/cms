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
use skeeks\cms\backend\widgets\SelectModelDialogTreeWidget;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\measure\models\CmsMeasure;
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTree;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\cms\widgets\GridView;
use skeeks\yii2\form\Element;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class AdminCmsContentPropertyController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentPropertyController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Property management');
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsContentProperty::class;

        $this->generateAccessActions = false;

        /*$this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return \Yii::$app->user->can($this->uniqueId);
        };*/

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
                        'component',
                        'content_ids',
                        'tree_ids',
                    ],

                    'filtersModel' => [
                        'rules' => [
                            ['q', 'safe'],
                            ['tree_ids', 'safe'],
                            ['content_ids', 'safe'],
                        ],

                        'attributeDefines' => [
                            'q',
                            'tree_ids',
                            'content_ids',
                        ],


                        'fields' => [
                            'component' => [
                                //'label'    => \Yii::t('skeeks/cms', 'Content'),
                                'field'             => [
                                    'class' => SelectField::class,
                                    'items' => function () {
                                        return \Yii::$app->cms->relatedHandlersDataForSelect;
                                    },
                                ],
                                'isAllowChangeMode' => false,
                                //'multiple' => true,

                                /*'on apply' => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->joinWith('cmsContentProperty2contents as contentMap')
                                            ->andWhere(['contentMap.cms_content_id' => $e->field->value]);

                                        $query->groupBy([CmsContentProperty::tableName().'.id']);
                                    }
                                },*/
                            ],

                            'content_ids' => [
                                'label'    => \Yii::t('skeeks/cms', 'Content'),
                                'class'    => SelectField::class,
                                'multiple' => true,
                                'items'    => function () {
                                    return \skeeks\cms\models\CmsContent::getDataForSelect();
                                },
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->joinWith('cmsContentProperty2contents as contentMap')
                                            ->andWhere(['contentMap.cms_content_id' => $e->field->value]);

                                        $query->groupBy([CmsContentProperty::tableName().'.id']);
                                    }
                                },
                            ],

                            'tree_ids' => [
                                'label'       => \Yii::t('skeeks/cms', 'Sections'),
                                'class'       => WidgetField::class,
                                'widgetClass' => \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class,
                                'on apply'    => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->joinWith('cmsContentProperty2trees as treeMap')
                                            ->andWhere(['treeMap.cms_tree_id' => $e->field->value]);

                                        $query->groupBy([CmsContentProperty::tableName().'.id']);
                                    }
                                },
                            ],

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
                                            ['like', CmsContentProperty::tableName().'.name', $e->field->value],
                                            ['like', CmsContentProperty::tableName().'.code', $e->field->value],
                                            ['like', CmsContentProperty::tableName().'.id', $e->field->value],
                                        ]);

                                        $query->groupBy([CmsContentProperty::tableName().'.id']);
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


                        if (!\Yii::$app->skeeks->site->is_default) {
                            $query->andWhere(['cms_site_id' => \Yii::$app->skeeks->site->id]);
                        } else {
                            $query->andWhere([
                                'or',
                                [CmsContentProperty::tableName() . '.cms_site_id' => \Yii::$app->skeeks->site->id],
                                [CmsContentProperty::tableName() . '.cms_site_id' => null]
                            ]);
                        }

                        $query->groupBy(CmsContentProperty::tableName().".id");
                        $query->select([
                            CmsContentProperty::tableName().'.*',
                        ]);
                    },

                    'sortAttributes' => [
                        'countElementProperties' => [
                            'asc'     => ['countElementProperties' => SORT_ASC],
                            'desc'    => ['countElementProperties' => SORT_DESC],
                            'label'   => \Yii::t('skeeks/cms', 'Number of partitions where the property is filled'),
                            'default' => SORT_ASC,
                        ],
                        'countEnums'             => [
                            'asc'     => ['countEnums' => SORT_ASC],
                            'desc'    => ['countEnums' => SORT_DESC],
                            'label'   => \Yii::t('skeeks/cms', 'Количество значений списка'),
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

                        //'name',
                        //'content',
                        'sections',
                        //'domains',

                        //'is_active',
                        'priority',
                        'countElementProperties',
                        /*'countEnums',*/
                        /*'countElementProperties',*/
                    ],
                    'columns'        => [
                        'custom'  => [
                            'attribute' => "name",
                            'format'    => "raw",
                            'value'     => function (CmsContentProperty $model) {
                                return Html::a($model->asText, "#", [
                                        'class' => "sx-trigger-action",
                                    ]).
                                    "<br />".Html::tag('small', $model->handler->name).
                                    "<br />".Html::tag('small', $model->code);
                            },
                        ],
                        'content' => [
                            'label'  => \Yii::t('skeeks/cms', 'Content'),
                            'format' => "raw",
                            'value'  => function (CmsContentProperty $cmsContentProperty) {
                                $contents = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsContents, 'id', 'name');
                                return implode(', ', $contents);
                            },
                        ],

                        'sections' => [
                            'label'  => \Yii::t('skeeks/cms', 'Где заполняется'),
                            'format' => "raw",
                            'value'  => function (CmsContentProperty $cmsContentProperty) {

                                if (!$cmsContentProperty->cmsContents) {
                                    return "Свойство не заполняется никогда! (видимо, еще не настроено)";
                                }

                                $contents = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsContents, 'id', 'name');
                                $contents = implode(', ', $contents);


                                if ($cmsContentProperty->cmsTrees) {
                                    $sections = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsTrees, 'id',
                                        function ($cmsTree) {
                                            $path = [];

                                            if ($cmsTree->parents) {
                                                foreach ($cmsTree->parents as $parent) {
                                                    if ($parent->isRoot()) {
                                                        $path[] = "[".$parent->site->name."] ".$parent->name;
                                                    } else {
                                                        $path[] = $parent->name;
                                                    }
                                                }
                                            }
                                            $path = implode(" / ", $path);
                                            return "<small><a href='{$cmsTree->url}' target='_blank' data-pjax='0'>{$path} / {$cmsTree->name}</a></small>";

                                        });


                                    return '<div>'.\Yii::t('skeeks/cms', 'Заполняется только для')." <b>".$contents.'</b> которые привязаны к разделам: </div>'.implode('<br />', $sections);
                                } else {
                                    return '<span>'.\Yii::t('skeeks/cms', 'Заполняется для: ')."<b>".$contents.'</b> любого раздела</span>';
                                }
                            },
                        ],

                        'countElementProperties' => [
                            'attribute'            => 'countElementProperties',
                            'format'               => 'raw',
                            'contentOptions'       => [
                                'style' => 'max-width: 100px;',
                            ],
                            'headerOptions'        => [
                                'style' => 'max-width: 100px;',
                            ],
                            'label'                => \Yii::t('skeeks/cms', 'Где заполнено свойство'),
                            'beforeCreateCallback' => function (GridView $grid) {
                                /**
                                 * @var $query ActiveQuery
                                 */
                                $query = $grid->dataProvider->query;

                                $subQuery = CmsContentElementProperty::find()->select([new Expression("count(1)")])->where([
                                    'property_id' => new Expression(CmsContentProperty::tableName().".id"),
                                ]);


                                $query->addSelect([
                                    'countElementProperties' => $subQuery,
                                ]);

                                $grid->sortAttributes["countElementProperties"] = [
                                    'asc'  => ['countElementProperties' => SORT_ASC],
                                    'desc' => ['countElementProperties' => SORT_DESC],
                                ];
                            },

                            'value' => function (CmsContentProperty $model) {
                                return $model->raw_row['countElementProperties'];
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

                        'countEnums' => [
                            'attribute' => 'countEnums',
                            'format'    => 'raw',
                            'label'     => \Yii::t('skeeks/cms', 'Количество значений списка'),
                            'value'     => function (CmsContentProperty $model) {
                                return $model->raw_row['countEnums'];
                            },

                            'beforeCreateCallback' => function (GridView $grid) {
                                /**
                                 * @var $query ActiveQuery
                                 */
                                $query = $grid->dataProvider->query;

                                $subQuery2 = CmsContentPropertyEnum::find()->select([new Expression("count(1)")])->where([
                                    'property_id' => new Expression(CmsContentProperty::tableName().".id"),
                                ]);


                                $query->addSelect([
                                    'countEnums' => $subQuery2,
                                ]);

                                $grid->sortAttributes["countEnums"] = [
                                    'asc'  => ['countEnums' => SORT_ASC],
                                    'desc' => ['countEnums' => SORT_DESC],
                                ];
                            },
                        ],

                        'cmsTreeFull' => [
                            'attribute' => 'cmsTreeFull',
                            'format'    => 'raw',
                            'label'     => \Yii::t('skeeks/cms', 'Разделы где заполнено свойство'),
                            'value'     => function (CmsContentProperty $model) {

                                $subquery = CmsContentElementProperty::find()
                                    ->joinWith("element as element")
                                    ->joinWith("element.cmsTree as cmsTree")
                                    ->select(["cmsTree.id as tree_id"])
                                    ->where([
                                        'property_id' => $model->id,
                                    ]);


                                $treesQ = CmsTree::find()->where(['in', 'id', $subquery]);
                                if ($trees = $treesQ->all()) {
                                    return implode("<br />", ArrayHelper::map($trees, 'id', 'fullName'));
                                }

                                return '';
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
                'controllerRoute' => "/cms/admin-cms-content-property-enum",
                'relation'        => ['property_id' => 'id'],
                'priority'        => 150,

                'on gridInit' => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'property_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                    $action->relatedIndexAction->grid['on init'] = function (Event $e) use ($action) {
                        /**
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;

                        $query->andWhere($action->getBindRelation($action->model));
                        AdminCmsContentPropertyEnumController::initGridQuery($query);
                    };
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
        ]);
    }


    public function updateFields($action)
    {
        /**
         * @var $model CmsTreeTypeProperty
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $result = [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Basic settings'),
                'fields' => [


                    'name',

                    'cmsContents' => [
                        'class'    => SelectField::class,
                        'multiple' => true,
                        'items'    => function () {
                            return \yii\helpers\ArrayHelper::map(
                                \skeeks\cms\models\CmsContent::find()->all(), 'id', 'name'
                            );
                        },
                    ],
                    'cmsTrees'    => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => SelectModelDialogTreeWidget::class,
                        'widgetConfig' => [
                            'multiple' => true,
                        ],
                    ],


                    'component'        => [
                        'class'          => SelectField::class,
                        'elementOptions' => [
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ],
                        /*'options' => [
                            'class' => 'teat'
                        ],*/
                        'items'          => function () {
                            return array_merge(['' => ' — '], \Yii::$app->cms->relatedHandlersDataForSelect);
                        },
                    ],
                    'cms_measure_code' => [
                        'class' => SelectField::class,
                        /*'elementOptions' => [
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ],*/
                        'items' => function () {
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
                                    $handler->enumRoute = 'cms/admin-cms-content-property-enum';
                                }

                                $content = $handler->renderConfigFormFields($formElement->activeForm);

                                if ($content) {
                                    $formElement->content = \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => \Yii::t('skeeks/cms', 'Settings')]).$content;
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

                    'is_active'        => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],

                    'is_required'      => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'code',
                    'hint',
                    'priority'    => [
                        'class' => NumberField::class,
                    ],

                ],
            ],
        ];
        
        if (\Yii::$app->skeeks->site->is_default && CmsSite::find()->count() > 1) {
            $result['site'] = [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Показ на сайтах'),
                'fields' => [
                    'cms_site_id' => [
                        'class' => SelectField::class,
                        'items' => ArrayHelper::map(
                            CmsSite::find()->all(),
                            'id',
                            'asText'
                        )
                    ],
                ]
            ];
                
        }
        
        return $result;
    }
}

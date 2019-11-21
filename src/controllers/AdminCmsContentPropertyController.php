<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\SelectModelDialogTreeWidget;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\yii2\form\Element;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
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
    public $notSubmitParam = 'sx-not-submit';

    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Property management');
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsContentProperty::class;

        $this->generateAccessActions = false;

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

                        //$query->joinWith('elementProperties as elementProperties');
                        $subQuery = CmsContentElementProperty::find()->select([new Expression("count(1)")])->where([
                            'property_id' => new Expression(CmsContentProperty::tableName().".id")
                        ]);
                            
                        $query->groupBy(CmsContentProperty::tableName().".id");
                        $query->select([
                            CmsContentProperty::tableName().'.*',
                            //'countElementProperties' => new Expression("count(*)"),
                            'countElementProperties' => $subQuery,
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

                        //'name',
                        'content',
                        'sections',
                        //'domains',

                        'active',
                        'priority',
                        /*'countElementProperties',*/
                    ],
                    'columns'        => [
                        'custom'  => [
                            'attribute' => "name",
                            'format'    => "raw",
                            'value'     => function (CmsContentProperty $model) {
                                return Html::a($model->asText, "#", [
                                        'class' => "sx-trigger-action",
                                    ])."<br />".Html::tag('small', $model->handler->name);
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
                            'label'  => \Yii::t('skeeks/cms', 'Sections'),
                            'format' => "raw",
                            'value'  => function (CmsContentProperty $cmsContentProperty) {
                                if ($cmsContentProperty->cmsTrees) {
                                    $contents = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsTrees, 'id',
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


                                    return '<b>'.\Yii::t('skeeks/cms',
                                            'Only shown in sections').':</b><br />'.implode('<br />', $contents);
                                } else {
                                    return '<b>'.\Yii::t('skeeks/cms', 'Always shown').'</b>';
                                }
                            },
                        ],

                        'countElementProperties' => [
                            'attribute' => 'countElementProperties',
                            'format'    => 'raw',
                            'label'     => \Yii::t('skeeks/cms', 'Number of partitions where the property is filled'),
                            'value'     => function (CmsContentProperty $model) {
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

                        'active' => [
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
                        'trueValue'  => "Y",
                        'falseValue' => "N",
                    ],
                    'active'      => [
                        'class'      => BoolField::class,
                        'allowNull'  => false,
                        'trueValue'  => "Y",
                        'falseValue' => "N",
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
                        /*'options' => [
                            'class' => 'teat'
                        ],*/
                        'items'          => function () {
                            return array_merge(['' => ' — '], \Yii::$app->cms->relatedHandlersDataForSelect);
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

                                echo \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => \Yii::t('skeeks/cms', 'Settings')]);
                                $formElement->content = $handler->renderConfigForm($formElement->activeForm);
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
                ],
            ],
        ];
    }
}

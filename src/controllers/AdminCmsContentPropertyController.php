<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\queryfilters\QueryFiltersEvent;
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
                                'field'    => [
                                    'class' => SelectField::class,
                                    'items'    => function () {
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

                        $query->joinWith('elementProperties as elementProperties');
                        $query->groupBy(CmsContentProperty::tableName().".id");
                        $query->select([
                            CmsContentProperty::tableName().'.*',
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

                        //'name',
                        'content',
                        'sections',
                        //'domains',

                        'active',
                        'priority',
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

                        /*'countElementProperties' => [
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
                        ],*/

                        'active' => [
                            'class' => BooleanColumn::class,
                        ],

                    ],
                ],
            ],

            'create' => [
                'callback' => [$this, 'create'],
            ],

            'update' => [
                'callback' => [$this, 'update'],
            ],
        ]);
    }


    public function create()
    {
        $rr = new RequestResponse();

        $modelClass = $this->modelClassName;
        /**
         * @var CmsContentProperty $model
         */
        $model = new $modelClass();
        $model->loadDefaultValues();

        if ($post = \Yii::$app->request->post()) {
            $model->load($post);
        }

        $handler = $model->handler;

        if ($handler) {
            if ($post = \Yii::$app->request->post()) {
                $handler->load($post);
            }
        }

        if ($rr->isRequestPjaxPost()) {
            if (!\Yii::$app->request->post($this->notSubmitParam)) {
                $model->component_settings = $handler->toArray();
                $handler->load(\Yii::$app->request->post());

                if ($model->load(\Yii::$app->request->post())
                    && $model->validate() && $handler->validate()) {
                    $model->save();

                    \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Saved'));

                    return $this->redirect(
                        UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute($this->modelDefaultAction)->normalizeCurrentRoute()
                            ->addData([$this->requestPkParamName => $model->{$this->modelPkAttribute}])
                            ->toString()
                    );
                } else {
                    \Yii::$app->getSession()->setFlash('error', \Yii::t('skeeks/cms', 'Could not save'));
                }
            }
        }

        return $this->render('_form', [
            'model'   => $model,
            'handler' => $handler,
        ]);
    }


    public function update()
    {
        $rr = new RequestResponse();

        $model = $this->model;

        if ($post = \Yii::$app->request->post()) {
            $model->load($post);
        }

        $handler = $model->handler;
        if ($handler) {
            if ($post = \Yii::$app->request->post()) {
                $handler->load($post);
            }
        }

        if ($rr->isRequestPjaxPost()) {
            if (!\Yii::$app->request->post($this->notSubmitParam)) {
                if ($rr->isRequestPjaxPost()) {
                    $model->component_settings = $handler->toArray();
                    $handler->load(\Yii::$app->request->post());

                    if ($model->load(\Yii::$app->request->post())
                        && $model->validate() && $handler->validate()) {
                        $model->save();

                        \Yii::$app->getSession()->setFlash('success', \Yii::t('app', 'Saved'));

                        if (\Yii::$app->request->post('submit-btn') == 'apply') {

                        } else {
                            return $this->redirect(
                                $this->url
                            );
                        }

                        $model->refresh();

                    }
                }
            }
        }

        return $this->render('_form', [
            'model'   => $model,
            'handler' => $handler,
        ]);
    }
}

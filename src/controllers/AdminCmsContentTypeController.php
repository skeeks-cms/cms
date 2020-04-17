<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\DefaultActionColumn;
use skeeks\cms\models\CmsContentType;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentTypeController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Content management');
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsContentType::class;

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
            'index'  => [
                "filters" => [
                    'visibleFilters' => [
                        'q',
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
                                        $query->andWhere([
                                            'or',
                                            ['like', CmsContentType::tableName().'.name', $e->field->value],
                                            ['like', CmsContentType::tableName().'.id', $e->field->value],
                                            ['like', CmsContentType::tableName().'.code', $e->field->value],
                                        ]);

                                        $query->groupBy([CmsContentType::tableName().'.id']);
                                    }
                                },
                            ],
                        ],
                    ],
                ],
                'grid'    => [
                    'defaultOrder'   => [
                        'priority'  => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        'content',
                        'priority',
                    ],
                    'columns'        => [

                        'custom'        => [
                            'attribute' => "name",
                            'viewAttribute' => "asText",
                            'class' => DefaultActionColumn::class
                        ],
                        
                        'content'        => [
                            'label' => "Контент",
                            'format'    => "raw",
                            'value'     => function (CmsContentType $model) {
                                $contents = \yii\helpers\ArrayHelper::map($model->cmsContents, 'id', 'name');
                                return implode(', ', $contents);
                            },
                        ],
                    ],
                ],
            ],
            "create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],



            "content" => [
                'class' => BackendGridModelRelatedAction::class,
                'accessCallback' => true,
                'name'            => "Контент",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-content",
                'relation'        => ['content_type' => 'code'],
                'priority'        => 600,
                'on gridInit'        => function($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    //ArrayHelper::removeValue($visibleColumns, 'cms_site_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],

        ]);
    }

    public function updateFields($action)
    {
        return [
            'name',
            'code',
            'priority',
        ];
    }
}

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
use skeeks\cms\backend\widgets\ControllerActionsWidget;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Content management');
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsContent::class;

        $this->generateAccessActions = false;

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
                                            ['like', CmsContent::tableName().'.name', $e->field->value],
                                            ['like', CmsContent::tableName().'.id', $e->field->value],
                                            ['like', CmsContent::tableName().'.code', $e->field->value],
                                        ]);

                                        $query->groupBy([CmsContent::tableName().'.id']);
                                    }
                                },
                            ],
                        ],
                    ],
                ],
                'grid'    => [
                    'defaultOrder'   => [
                        'priority' => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        'priority',
                    ],
                    'columns'        => [

                        'custom' => [
                            'attribute' => "name",
                            'format'    => "raw",
                            'value'     => function (CmsContent $model) {
                                return Html::a($model->asText, "#", [
                                    'class' => "sx-trigger-action",
                                ]);
                            },
                        ],

                    ],
                ],
            ],
            /*"create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],*/


            "content" => [
                'class'           => BackendGridModelRelatedAction::class,
                'accessCallback'  => true,
                'name'            => "Свойства",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-content-property",
                //'relation'        => ['content_type' => 'code'],
                'priority'        => 600,
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;

                    //$action->relatedIndexAction->controller->content = $this->model;
                    $action->relatedIndexAction->grid['on init'] = function (Event $e) {
                        /**
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $query->joinWith("cmsContents as cmsContents");
                        $query->andWhere(['cmsContents.id' => $this->model->id]);
                        
                        $query->groupBy(CmsContentProperty::tableName().".id");
                        $query->select([
                            CmsContentProperty::tableName().'.*',
                        ]);
                    };
                    
                    $controller = $action->relatedController;

                    $action->relatedIndexAction->on('beforeRender', function (Event $event) use ($controller) {

                        if ($createAction = ArrayHelper::getValue($controller->actions, 'create')) {

                            /**
                             * @var $controller BackendModelController
                             * @var $createAction BackendModelCreateAction
                             */
                            $r = new \ReflectionClass($controller->modelClassName);

                                $createAction->url = ArrayHelper::merge($createAction->urlData, [
                                    $r->getShortName() => [
                                        'cmsContents' => [$this->model->id]
                                    ],
                                ]);

                            //$createAction->name = "Добавить платеж";

                            $event->content = ControllerActionsWidget::widget([
                                    'actions'         => [$createAction],
                                    'isOpenNewWindow' => true,
                                    'minViewCount'    => 1,
                                    'itemTag'         => 'button',
                                    'itemOptions'     => ['class' => 'btn btn-primary'],
                                    /*'button'          => [
                                        'class' => 'btn btn-primary',
                                        //'style' => 'font-size: 11px; cursor: pointer;',
                                        'tag'   => 'a',
                                        'label' => 'Зарегистрировать номер',
                                    ],*/
                                ])."<br>";
                        }


                    });

                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    //ArrayHelper::removeValue($visibleColumns, 'cms_site_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $contentTypePk = null;

        if ($this->model) {
            if ($contentType = $this->model->contentType) {
                $contentTypePk = $contentType->id;
            }
        }

        return UrlHelper::construct([
            "cms/admin-cms-content-type/update",
            'pk' => $contentTypePk,
        ])->enableAdmin()->toString();
    }
}

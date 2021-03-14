<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\actions\BackendGridModelAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelMultiDialogEditAction;
use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\backend\widgets\SelectModelDialogTreeWidget;
use skeeks\cms\backend\widgets\SelectModelDialogUserWidget;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\grid\UserColumnData;
use skeeks\cms\helpers\Image;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\IHasUrl;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\models\CmsUser;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorAction;
use skeeks\cms\modules\admin\widgets\GridViewStandart;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\filters\NumberFilterField;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\widgets\AjaxSelect;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\ContentElementBackendFiltersWidget;
use skeeks\cms\widgets\ContentElementBackendGridView;
use skeeks\cms\widgets\GridView;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use skeeks\yii2\queryfilter\QueryFilterWidget;
use yii\base\DynamicModel;
use yii\base\Event;
use yii\base\Exception;
use yii\bootstrap\Alert;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;
use yii\helpers\Url;
use yii\web\Application;

/**
 * @property CmsContent|static $content
 *
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentElementController extends BackendModelStandartController
{

    public $editForm = '_form';

    public $modelClassName = CmsContentElement::class;
    public $modelShowAttribute = "asText";
    /**
     * @var CmsContent
     */
    protected $_content = null;

    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Elements');

        if ($this->content) {
            if ($this->permissionName === null) {
                $this->permissionName = $this->uniqueId."__".$this->content->id;
            }
        }

        $this->modelHeader = function () {
            /**
             * @var $model CmsContentElement
             */
            $model = $this->model;
            $result = $model->asText;

            if ($model->cmsContent->is_have_page) {
                $result .= Html::a('<i class="fas fa-external-link-alt"></i>', $model->url, [
                    'target' => "_blank",
                    'class'  => "g-ml-20",
                    'title'  => \Yii::t('skeeks/cms', 'Watch to site (opens new window)'),
                ]);
            }

            $result = Html::tag('h1', $result);
            return $result;
        };

        parent::init();


    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $content = $this->content;
        
        $result = ArrayHelper::merge(parent::actions(), [
            'index' => [
                'configKey'      => $this->uniqueId."-".($this->content ? $this->content->id : ""),
                'on afterRender' => [$this, 'contentEdit'],
                //'url' => [$this->uniqueId, 'content_id' => $this->content->id],
                'on init'        => function ($e) {
                    $action = $e->sender;
                    /**
                     * @var $action BackendGridModelAction
                     */
                    if ($this->content) {
                        $action->url = ["/".$action->uniqueId, 'content_id' => $this->content->id];
                        $this->initGridData($action, $this->content);
                    }

                },

                "filters" => [
                    'class' => ContentElementBackendFiltersWidget::class,
                    'contextData' => [
                        'content_id' => $this->content->id
                    ],
                    'visibleFilters'     => [
                        'q',
                        //'id',
                        //'name',
                        //'active',
                    ],
                    'filtersModel'       => [

                        'fields' => [

                            'active' => [
                                'field'             => [
                                    'class'      => BoolField::class,
                                    'trueValue'  => 'Y',
                                    'falseValue' => 'N',
                                ],
                                'defaultMode'       => FilterModeEq::ID,
                                'isAllowChangeMode' => false,
                            ],

                            'created_by' => [
                                'field'             => [
                                    'class'             => WidgetField::class,
                                    'widgetConfig'      => [
                                        'multiple' => true,
                                        'searchQuery' => function($word = '') use ($content) {
                                            $userIds = CmsContentElement::find()
                                                    ->cmsSite()
                                                    ->andWhere(['content_id' => $content->id])
                                                    ->groupBy("created_by")
                                                    ->select('created_by')
                                                ->asArray()
                                                ->indexBy("created_by")
                                                ->all()
                                            ;

                                            $query = null;
                                            if ($userIds) {
                                                $userIds = array_keys($userIds);
                                                $query = CmsUser::find()->where(['id' => $userIds]);
                                                /*return ArrayHelper::map($q->all(), 'id', function(CmsUser $model) {
                                                    return $model->shortDisplayName . ($model->email ? " ($model->email)" : "");
                                                });*/
                                            } else {
                                                $query = CmsUser::find()->where(['id' => null]);
                                            }


                                            if ($word) {
                                                $query->andWhere([
                                                    'or',
                                                    ['like', 'first_name', $word],
                                                    ['like', 'last_name', $word],
                                                    ['like', 'email', $word],
                                                    ['like', 'phone', $word],
                                                ]);
                                            }
                                            
                                            return $query;
                                        },
                                    ],
                                ],
                            ],


                            'tree_id' => [
                                /*'class' => WidgetField::class,
                                'widgetClass' => SelectModelDialogUserWidget::class,*/
                                'isAllowChangeMode' => false,
                                'field'             => [
                                    'class'       => WidgetField::class,
                                    'widgetClass' => SelectModelDialogTreeWidget::class,
                                    //'items'       => new UnsetArrayValue(),
                                    //'multiple'    => new UnsetArrayValue(),
                                ],
                            ],

                            'q' => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск (название, описание)',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        //$query->joinWith("childrenContentElements as child");
                                        //$query->joinWith("childrenContentElements.parentContentElement as parent");

                                        $q = CmsContentElement::find()
                                            ->select(['parent_id' => 'parent_content_element_id'])
                                            ->where([
                                                'or',
                                                ['like', CmsContentElement::tableName().'.id', $e->field->value],
                                                ['like', CmsContentElement::tableName().'.name', $e->field->value],
                                                ['like', CmsContentElement::tableName().'.description_short', $e->field->value],
                                                ['like', CmsContentElement::tableName().'.description_full', $e->field->value],
                                                ['like', CmsContentElement::tableName().'.external_id', $e->field->value],
                                            ]);

                                        $query->leftJoin(['p' => $q], ['p.parent_id' => new Expression(CmsContentElement::tableName().".id")]);

                                        $query->andWhere([
                                            'or',
                                            ['like', CmsContentElement::tableName().'.id', $e->field->value],
                                            ['like', CmsContentElement::tableName().'.name', $e->field->value],
                                            ['like', CmsContentElement::tableName().'.description_short', $e->field->value],
                                            ['like', CmsContentElement::tableName().'.description_full', $e->field->value],
                                            ['like', CmsContentElement::tableName().'.external_id', $e->field->value],
                                            ['is not', 'p.parent_id', null],
                                        ]);
                                    }
                                },
                            ],

                            'has_image' => [
                                'class'      => BoolField::class,
                                'falseValue' => 'n',
                                'label'      => 'Наличие изображения',
                                'on apply'   => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        if ($e->field->value == '1') {
                                            $query->andWhere(
                                                ['IS NOT', CmsContentElement::tableName().'.image_id', null]
                                            );
                                        } else if ($e->field->value == 'n') {
                                            $query->andWhere(
                                                [CmsContentElement::tableName().'.image_id' => null]
                                            );
                                        }
                                    }
                                },
                            ],

                            'has_full_image' => [
                                'class'      => BoolField::class,
                                'falseValue' => 'n',
                                'label'      => 'Наличие подробного изображения',
                                'on apply'   => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        if ($e->field->value == '1') {
                                            $query->andWhere(
                                                ['IS NOT', CmsContentElement::tableName().'.image_full_id', null]
                                            );
                                        } else if ($e->field->value == 'n') {
                                            $query->andWhere(
                                                [CmsContentElement::tableName().'.image_full_id' => null]
                                            );
                                        }
                                    }
                                },
                            ],
                        ],
                    ],

                    /*'on init' => function(Event $event) {
                        $this->initFiltersModel($event->sender, $this->content);
                    }*/
                ],
                'grid'    => [

                    'class' => ContentElementBackendGridView::class,

                    'contextData' => [
                        'content_id' => $this->content->id
                    ],

                    'on beforeInit'        => function (Event $event) {
                        /**
                         * @var $query ActiveQuery
                         * @var $grid GridView
                         */
                        $grid = $event->sender;

                        $query = $event->sender->dataProvider->query;
                        $query->andWhere(['cms_site_id' => \Yii::$app->skeeks->site->id]);
                        if ($this->content) {
                            $query->andWhere([CmsContentElement::tableName().'.content_id' => $this->content->id]);
                        }

                        $this->initGridColumns($grid, $this->content);
                    },

                    /*'columnConfigCallback' => function($columnName, GridView $grid) {

                        if (strpos($columnName, "property") != -1) {

                            $propertyId = (int) str_replace("property", "", $columnName);
                            /**
                             * @var $property CmsContentProperty
                            $property = CmsContentProperty::findOne($propertyId);

                            return [
                                'headerOptions' => [
                                    'style' => 'width: 150px;'
                                ],
                                'contentOptions' => [
                                    'style' => 'width: 150px;'
                                ],

                                'label'  => $property ? $property->name : "Свойство удалено",
                                'format' => 'raw',
                                'value'  => function ($model, $key, $index) use ($property) {
                                    if (!$property) {
                                        return '';
                                    }
                                    /**
                                     * @var $model \skeeks\cms\models\CmsContentElement
                                    return $model->relatedPropertiesModel->getAttributeAsHtml($property->code);
                                },
                            ];
                        }

                    },*/

                    'defaultOrder'   => [
                        'active'   => SORT_DESC,
                        'priority' => SORT_ASC,
                        'id'       => SORT_DESC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',

                        //'image_id',
                        //'name',

                        //'tree_id',
                        //'additionalSections',
                        //'published_at',
                        'priority',

                        //'created_by',

                        'active',

                        'view',
                    ],
                    'columns'        => [
                        'created_by'   => [
                            'class' => UserColumnData::class,
                        ],
                        'updated_by'   => [
                            'class' => UserColumnData::class,
                        ],
                        'active'       => [
                            //'class' => BooleanColumn::class,
                            'format'         => 'raw',
                            'headerOptions'  => [
                                'style' => 'max-width: 100px; width: 100px;',
                            ],
                            'contentOptions' => [
                                'style' => 'max-width: 100px; width: 100px;',
                            ],
                            'value'          => function (\skeeks\cms\models\CmsContentElement $model) {
                                if ($model->active == "Y") {
                                    $time = \Yii::$app->formatter->asRelativeTime($model->published_at);
                                    $dateTime = \Yii::$app->formatter->asDatetime($model->published_at);
                                    return <<<HTML
<span class="fa fa-check text-success" title=""></span> <small title="{$dateTime}">{$time}</small>
HTML;

                                } else {
                                    return <<<HTML
<span class="fa fa-times text-danger" title=""></span>
HTML;
                                }
                            },
                        ],
                        'custom'       => [
                            'attribute' => 'id',
                            'format'    => 'raw',
                            'value'     => function (\skeeks\cms\models\CmsContentElement $model) {

                                $data = [];
                                /*$data[] = "<div class='sx-trigger-action' style='width: 50px; float: left;'>".Html::a(
                                        Html::img($model->image ? $model->image->src : Image::getCapSrc(), [
                                            'style' => 'max-width: 50px; max-height: 50px; border-radius: 5px;',
                                        ])
                                        , "#", ['class' => 'sx-trigger-action', 'style' => 'width: 50px;'])."</div>";*/

                                $data[] = "<span style='max-width: 300px;'>".Html::a($model->asText, "#", [
                                        'class' => 'sx-trigger-action',
                                        'title' => $model->asText,
                                        'style' => 'border-bottom: none;'
                                    ])."</span>";

                                if ($model->tree_id) {
                                    $data[] = '<i class="far fa-folder" style="color: gray;"></i> '.Html::a($model->cmsTree->name, $model->cmsTree->url, [
                                            'data-pjax' => '0',
                                            'target'    => '_blank',
                                            'title'     => $model->cmsTree->fullName,
                                            'style'     => 'color: #333; max-width: 200px; display: inline-block; color: gray; cursor: pointer; white-space: nowrap; border-bottom: none;',
                                        ]);
                                }

                                if ($model->cmsTrees) {
                                    foreach ($model->cmsTrees as $cmsTree) {
                                        $data[] = '<i class="far fa-folder" style="color: gray;"></i> ' . Html::a($cmsTree->name, $cmsTree->url, [
                                            'data-pjax' => '0',
                                            'target'    => '_blank',
                                            'title'     => $cmsTree->fullName,
                                            'style'     => 'color: #333; max-width: 200px; display: inline-block; color: gray; cursor: pointer; white-space: nowrap;  border-bottom: none;',
                                        ]);
                                    }
                                }

                                $info = implode("<br />", $data);

                                return "<div class='d-flex no-gutters'>
                                                <div class='sx-trigger-action my-auto' style='width: 50px; margin-right: 10px; float: left;'>
                                                    <a href='#' style='text-decoration: none; border-bottom: 0;'>
                                                        <img src='".($model->image ? $model->image->src : Image::getCapSrc())."' style='max-width: 40px; max-height: 40px; border-radius: 5px;' />
                                                    </a>
                                                </div>
                                                <div style='line-height: 1.1;'>".$info."</div></div>";;
                            },
                        ],
                        'image_id'     => [
                            'class' => ImageColumn2::class,
                        ],
                        'image.src'    => [
                            'label'  => 'Ссылка на главное изображение',
                            'value'  => function (\skeeks\cms\models\CmsContentElement $model) {
                                if ($model->image) {
                                    return $model->image->absoluteSrc;
                                } else {
                                    return '';
                                }

                            },
                            'format' => 'raw',
                        ],
                        'published_at' => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'published_to' => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'created_at'   => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'updated_at'   => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'priority'     => [
                            'headerOptions'  => [
                                'style' => 'max-width: 100px; width: 100px;',
                            ],
                            'contentOptions' => [
                                'style' => 'max-width: 100px; width: 100px;',
                            ],
                        ],

                        'tree_id' => [
                            'value'  => function (\skeeks\cms\models\CmsContentElement $model) {
                                if (!$model->cmsTree) {
                                    return null;
                                }

                                $path = [];

                                if ($model->cmsTree->parents) {
                                    foreach ($model->cmsTree->parents as $parent) {
                                        if ($parent->isRoot()) {
                                            $path[] = "[".$parent->site->name."] ".$parent->name;
                                        } else {
                                            $path[] = $parent->name;
                                        }
                                    }
                                }
                                $path = implode(" / ", $path);
                                return "<small><a href='{$model->cmsTree->url}' target='_blank' data-pjax='0'>{$path} / {$model->cmsTree->name}</a></small>";
                            },
                            'format' => 'raw',
                        ],

                        'view' => [
                            'value'          => function (\skeeks\cms\models\CmsContentElement $model) {
                                if ($model->cmsContent->is_have_page) {
                                    return \yii\helpers\Html::a('<i class="fas fa-external-link-alt"></i>', $model->absoluteUrl,
                                        [
                                            'target'    => '_blank',
                                            'title'     => \Yii::t('skeeks/cms', 'Watch to site (opens new window)'),
                                            'data-pjax' => '0',
                                            'class'     => 'btn btn-sm',
                                        ]);
                                } else {
                                    return '';
                                }

                            },
                            'format'         => 'raw',
                            /*'label'  => "Смотреть",*/
                            'headerOptions'  => [
                                'style' => 'max-width: 40px; width: 40px;',
                            ],
                            'contentOptions' => [
                                'style' => 'max-width: 40px; width: 40px;',
                            ],
                        ],

                        'additionalSections' => [
                            'value'   => function (\skeeks\cms\models\CmsContentElement $model) {
                                $result = [];

                                if ($model->cmsContentElementTrees) {
                                    foreach ($model->cmsContentElementTrees as $contentElementTree) {

                                        $site = $contentElementTree->tree->root->site;
                                        $result[] = "<small><a href='{$contentElementTree->tree->url}' target='_blank' data-pjax='0'>[{$site->name}]/.../{$contentElementTree->tree->name}</a></small>";

                                    }
                                }

                                return implode('<br />', $result);

                            },
                            'format'  => 'raw',
                            'label'   => \Yii::t('skeeks/cms', 'Additional sections'),
                            'visible' => false,
                        ],


                    ],
                ],
            ],

            "delete" => [
                'generateAccess' => true,
            ],
            "create" => [
                'generateAccess' => true,
                "callback" => [$this, 'create'],
            ],

            "update" => [
                'generateAccess' => true,
                "callback" => [$this, 'update'],
            ],

            'stat' => [
                'generateAccess' => true,
                'class'          => ViewBackendAction::class,
                'name'           => 'Статистика',
                'icon'           => 'fas fa-info-circle',
                'priority'       => 500,
            ],

            "activate-multi"   => [
                'class'   => BackendModelMultiActivateAction::class,
                'value' => 'Y',
                'attribute' => 'active',
                'on init' => function ($e) {
                    $action = $e->sender;
                    /**
                     * @var BackendGridModelAction $action
                     */
                    if ($this->content) {
                        $action->url = ["/".$action->uniqueId, 'content_id' => $this->content->id];
                    }
                },

                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can($this->permissionName."/update", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return \Yii::$app->user->can($this->permissionName."/update");
                },

            ],
            "delete-multi" => [
                'on init' => function ($e) {
                    $action = $e->sender;
                    /**
                     * @var BackendGridModelAction $action
                     */
                    if ($this->content) {
                        $action->url = ["/".$action->uniqueId, 'content_id' => $this->content->id];
                    }
                },
            ],
            "deactivate-multi" => [
                'class'   => BackendModelMultiDeactivateAction::class,
                'value' => 'N',
                'attribute' => 'active',
                'on init' => function ($e) {
                    $action = $e->sender;
                    /**
                     * @var BackendGridModelAction $action
                     */
                    if ($this->content) {
                        $action->url = ["/".$action->uniqueId, 'content_id' => $this->content->id];
                    }
                },

                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can($this->permissionName."/update", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return \Yii::$app->user->can($this->permissionName."/update");
                },
            ],


            "copy" => [
                'priority'       => 200,
                'class'          => BackendModelUpdateAction::class,
                "name"           => \Yii::t('skeeks/cms', 'Copy'),
                "icon"           => "fas fa-copy",
                "beforeContent"  => "Механизм создания копии текущего элемента. Укажите параметры копирования и нажмите применить.",
                "successMessage" => "Элемент успешно скопирован",

                "accessCallback"     => function () {
                    return \Yii::$app->user->can($this->permissionName."/create");
                },

                'on initFormModels' => function (Event $e) {
                    $model = $e->sender->model;
                    $dm = new DynamicModel(['is_copy_images', 'is_copy_files']);
                    $dm->addRule(['is_copy_images', 'is_copy_files'], 'boolean');

                    $dm->is_copy_images = true;
                    $dm->is_copy_files = true;

                    $e->sender->formModels['dm'] = $dm;
                },

                'on beforeSave' => function (Event $e) {
                    /**
                     * @var $action BackendModelUpdateAction;
                     */
                    $action = $e->sender;
                    $action->isSaveFormModels = false;
                    $dm = ArrayHelper::getValue($action->formModels, 'dm');

                    $newModel = $action->model->copy();

                    if ($newModel) {
                        $action->afterSaveUrl = Url::to(['update', 'pk' => $newModel->id, 'content_id' => $newModel->content_id]);
                    } else {
                        throw new Exception(print_r($newModel->errors, true));
                    }

                },

                'fields' => function () {
                    return [
                        'dm.is_copy_images' => [
                            'class' => BoolField::class,
                            'label' => ['skeeks/cms', 'Copy images?'],
                        ],
                        'dm.is_copy_files'  => [
                            'class' => BoolField::class,
                            'label' => ['skeeks/cms', 'Copy files?'],
                        ],
                    ];
                },
            ],


            "change-tree-multi" => [
                'class'              => BackendModelMultiDialogEditAction::class,
                "name"               => \Yii::t('skeeks/cms', 'The main section'),
                "viewDialog"         => "@skeeks/cms/views/admin-cms-content-element/change-tree-form",
                "eachCallback"       => [$this, 'eachMultiChangeTree'],
                'on init'            => function ($e) {
                    /**
                     * @var BackendGridModelAction $action
                     */
                    $action = $e->sender;
                    if ($this->content) {
                        $action->url = ["/".$action->uniqueId, 'content_id' => $this->content->id];
                    }
                },
                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can($this->permissionName."/update", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return \Yii::$app->user->can($this->permissionName."/update");
                },
            ],

            "change-trees-multi" => [
                'class'        => BackendModelMultiDialogEditAction::class,
                "name"         => \Yii::t('skeeks/cms', 'Related topics'),
                "viewDialog"   => "@skeeks/cms/views/admin-cms-content-element/change-trees-form",
                "eachCallback" => [$this, 'eachMultiChangeTrees'],
                'on init'      => function ($e) {
                    $action = $e->sender;
                    /**
                     * @var BackendGridModelAction $action
                     */
                    if ($this->content) {
                        $action->url = ["/".$action->uniqueId, 'content_id' => $this->content->id];
                    }
                },

                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can($this->permissionName."/update", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return \Yii::$app->user->can($this->permissionName."/update");
                },
            ],

            /*"rp" => [
                'class'        => BackendModelMultiDialogEditAction::class,
                "name"         => \Yii::t('skeeks/cms', 'Properties'),
                "viewDialog"   => "@skeeks/cms/views/admin-cms-content-element/multi-rp",
                "eachCallback" => [$this, 'eachRelatedProperties'],
                'on init'      => function ($e) {
                    $action = $e->sender;
                    if ($this->content) {
                        $action->url = ["/".$action->uniqueId, 'content_id' => $this->content->id];
                    }
                },

                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can($this->permissionName."/update", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return \Yii::$app->user->can($this->permissionName."/update");
                },
            ],*/

        ]);

        //Дополнительные свойства
        return $result;
    }


    public function initGridColumns($grid, $content)
    {
        return [];
        $model = null;
        $autoColumns = [];

        if ($content) {
            $model = new CmsContentElement([
                'content_id' => $content->id,
            ]);
        }

        if ($model && $content && $content->getCmsContentProperties()->count()) {
            $relatedPropertiesModel = $model->relatedPropertiesModel;

            $properties = $content->getCmsContentProperties();
            $properties->andWhere([
                'or',
                [CmsContentProperty::tableName().'.cms_site_id' => \Yii::$app->skeeks->site->id],
                [CmsContentProperty::tableName().'.cms_site_id' => null],
            ]);
            $properties = $properties->all();

            foreach ($properties as $property) {
                $name = $property->code;
                //$property = $relatedPropertiesModel->getRelatedProperty($name);
                $filter = '';

                $autoColumns["property{$property->id}"] = [
                    //'attribute' => $name,
                    'headerOptions' => [
                        'style' => 'width: 150px;'
                    ],
                    'contentOptions' => [
                        'style' => 'width: 150px;'
                    ],

                    'label'  => $property->name,
                    'format' => 'raw',
                    'value'  => function ($model, $key, $index) use ($name, $relatedPropertiesModel) {
                        /**
                         * @var $model \skeeks\cms\models\CmsContentElement
                         */
                        return $model->relatedPropertiesModel->getAttributeAsHtml($name);
                    },
                ];
            }
        }

        if ($autoColumns) {
            $grid->columns = ArrayHelper::merge($grid->columns, $autoColumns);
        }

        return $this;
    }


    public function initGridData($action, $content) {
        return $this;
    }

    public function contentEdit(Event $e)
    {
        if (!$this->content) {
            return;
        }

        $url = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
            "/cms/admin-cms-content/update",
            "pk" => $this->content->id,
        ])->enableEmptyLayout()->enableNoActions()->url;

        $actionData = \yii\helpers\Json::encode([
            "isOpenNewWindow" => true,
            "url"             => $url,
        ]);

        $href = \yii\helpers\Html::a('Настройках контента', $url, [
            'onclick' => "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;",
        ]);

        $e->content = Alert::widget([
            'options'     => [
                'class' => 'sx-bg-gray-light',
            ],
            'closeButton' => false,

            'body' => <<<HTML
    Изменить свойства и права доступа к информационному блоку вы можете в {$href}
HTML
            ,
        ]);
    }

    public function create($adminAction)
    {
        $is_saved = false;
        $redirect = "";

        $modelClassName = $this->modelClassName;
        $model = new $modelClassName;

        $model->loadDefaultValues();
        $model->content_id = $this->content->id;
        $model->cms_site_id = \Yii::$app->skeeks->site->id;

        $relatedModel = $model->relatedPropertiesModel;
        $relatedModel->loadDefaultValues();

        $rr = new RequestResponse();

        /*if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());

            return \yii\widgets\ActiveForm::validateMultiple([
                $model,
                $relatedModel,
            ]);
        }*/

        if ($post = \Yii::$app->request->post()) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
        }

        if ($rr->isRequestPjaxPost()) {
            if (!\Yii::$app->request->post(RequestResponse::DYNAMIC_RELOAD_NOT_SUBMIT)) {
                $model->load(\Yii::$app->request->post());
                $relatedModel->load(\Yii::$app->request->post());

                if ($model->save() && $relatedModel->save()) {
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Saved'));

                    $is_saved = true;

                    if (\Yii::$app->request->post('submit-btn') == 'apply') {
                        $url = '';
                        $this->model = $model;

                        if ($this->modelActions) {
                            if ($action = ArrayHelper::getValue($this->modelActions, $this->modelDefaultAction)) {
                                $url = $action->url;
                            }
                        }

                        if (!$url) {
                            $url = $this->url;
                        }

                        $redirect = $url;
                    } else {
                        $redirect = $this->url;
                    }
                }
            }

        }

        return $this->render($this->editForm, [
            'model'        => $model,
            'relatedModel' => $relatedModel,

            'is_saved'  => $is_saved,
            'submitBtn' => \Yii::$app->request->post('submit-btn'),
            'redirect'  => $redirect,
        ]);
    }
    public function update($adminAction)
    {
        $is_saved = false;
        $redirect = "";

        /**
         * @var $model CmsContentElement
         */
        $model = $this->model;
        $relatedModel = $model->relatedPropertiesModel;

        $rr = new RequestResponse();

        /*if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            return \yii\widgets\ActiveForm::validateMultiple([
                $model,
                $relatedModel,
            ]);
        }*/

        if ($post = \Yii::$app->request->post()) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
        }

        if ($rr->isRequestPjaxPost()) {
            if (!\Yii::$app->request->post(RequestResponse::DYNAMIC_RELOAD_NOT_SUBMIT)) {
                $model->load(\Yii::$app->request->post());
                $relatedModel->load(\Yii::$app->request->post());

                if ($model->save() && $relatedModel->save()) {
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Saved'));

                    $is_saved = true;

                    if (\Yii::$app->request->post('submit-btn') == 'apply') {
                    } else {
                        $redirect = $this->url;
                    }

                    $model->refresh();
                    $relatedModel = $model->relatedPropertiesModel;
                }
            }

        }

        return $this->render($this->editForm, [
            'model'        => $model,
            'relatedModel' => $relatedModel,
            'is_saved'     => $is_saved,
            'submitBtn'    => \Yii::$app->request->post('submit-btn'),
            'redirect'     => $redirect,
        ]);
    }
    /**
     * @param CmsContentElement $model
     * @param                   $action
     * @return bool
     */
    public function eachMultiChangeTree($model, $action)
    {
        //try {
        $formData = [];
        parse_str(\Yii::$app->request->post('formData'), $formData);
        $tmpModel = new CmsContentElement();
        $tmpModel->load($formData);
        if ($tmpModel->tree_id && $tmpModel->tree_id != $model->tree_id) {
            $model->tree_id = $tmpModel->tree_id;
            if (!$model->save(false)) {
                throw new Exception("Не сохранилось: ".print_r($model->errors, true));
            }
        } else {
            throw new Exception('Раздел не изменился');
        }

        return true;
        //} catch (\Exception $e) {
        //    return false;
        //}
    }
    public function eachRelatedProperties($model, $action)
    {
        try {
            $formData = [];
            parse_str(\Yii::$app->request->post('formData'), $formData);

            if (!$formData) {
                return false;
            }

            if (!$content_id = ArrayHelper::getValue($formData, 'content_id')) {
                return false;
            }

            if (!$fields = ArrayHelper::getValue($formData, 'fields')) {
                return false;
            }


            /**
             * @var CmsContent $content
             */
            $content = CmsContent::findOne($content_id);
            if (!$content) {
                return false;
            }


            $element = $content->createElement();
            $relatedProperties = $element->relatedPropertiesModel;
            $relatedProperties->load($formData);
            /**
             * @var $model CmsContentElement
             */
            $rpForSave = $model->relatedPropertiesModel;

            foreach ((array)ArrayHelper::getValue($formData, 'fields') as $code) {
                if ($rpForSave->hasAttribute($code)) {
                    $rpForSave->setAttribute($code,
                        ArrayHelper::getValue($formData, 'RelatedPropertiesModel.'.$code));
                }
            }

            return $rpForSave->save(false);
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * @param CmsContentElement $model
     * @param                   $action
     * @return bool
     */
    public function eachMultiChangeTrees($model, $action)
    {
        try {
            $formData = [];
            parse_str(\Yii::$app->request->post('formData'), $formData);
            $tmpModel = new CmsContentElement();
            $tmpModel->load($formData);

            if (ArrayHelper::getValue($formData, 'removeCurrent')) {
                $model->treeIds = [];
            }

            if ($tmpModel->treeIds) {
                $model->treeIds = array_merge($model->treeIds, $tmpModel->treeIds);
                $model->treeIds = array_unique($model->treeIds);
            }

            return $model->save(false);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return CmsContent|static
     */
    public function getContent()
    {
        if ($this->_content === null) {
            if ($this->model) {
                $this->_content = $this->model->cmsContent;
                return $this->_content;
            }

            if (\Yii::$app instanceof Application && \Yii::$app->request->get('content_id')) {
                $content_id = \Yii::$app->request->get('content_id');

                $dependency = new TagDependency([
                    'tags' =>
                        [
                            (new CmsContent())->getTableCacheTag(),
                        ],
                ]);

                $this->_content = CmsContent::getDb()->cache(function ($db) use ($content_id) {
                    return CmsContent::find()->where([
                        "id" => $content_id,
                    ])->one();
                }, null, $dependency);

                return $this->_content;
            }
        }

        return $this->_content;
    }
    /**
     * @param $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->permissionName = $this->uniqueId . "__" . $content->id;
        $this->_content = $content;
        return $this;
    }
    public function getModelActions()
    {
        /**
         * @var AdminAction $action
         */
        $actions = parent::getModelActions();
        if ($actions) {
            foreach ($actions as $action) {
                $action->url = ArrayHelper::merge($action->urlData,
                    ['content_id' => $this->content ? $this->content->id : ""]);
            }
        }

        return $actions;
    }

    public function beforeAction($action)
    {
        if ($this->content) {
            if ($this->content->name_meny) {
                $this->name = $this->content->name_meny;
            } else {
                $this->name = $this->content->name;
            }
        }

        return parent::beforeAction($action);
    }
    /**
     * @return string
     */
    public function getUrl()
    {
        $actions = $this->getActions();
        $index = ArrayHelper::getValue($actions, 'index');
        if ($index && $index instanceof IHasUrl) {
            return $index->url;
        }

        return '';
    }
    public function getActions()
    {
        /**
         * @var AdminAction $action
         */
        $actions = parent::getActions();
        if ($actions) {
            foreach ($actions as $action) {
                if ($this->content) {
                    $action->url = ArrayHelper::merge($action->urlData, ['content_id' => $this->content->id]);
                }
            }
        }

        return $actions;
    }


    /**
     * @param CmsContent $model
     * @return array
     */
    public static function getColumns($cmsContent = null, $dataProvider = null)
    {
        return \yii\helpers\ArrayHelper::merge(
            static::getDefaultColumns($cmsContent),
            static::getColumnsByContent($cmsContent, $dataProvider)
        );
    }
    /**
     * @param CmsContent $cmsContent
     * @return array
     */
    public static function getDefaultColumns($cmsContent = null)
    {
        $columns = [
            [
                'class' => \skeeks\cms\grid\ImageColumn2::class,
            ],
            'name',
            [
                'attribute' => "created_at",
                'class'     => DateTimeColumnData::class,
            ],
            [
                'class'     => DateTimeColumnData::class,
                'attribute' => 'updated_at',
                'visible'   => false,
            ],
            [
                'attribute' => "published_at",
                'class'     => DateTimeColumnData::class,
                'visible'   => false,
            ],
            [
                'class'     => \skeeks\cms\grid\DateTimeColumnData::class,
                'attribute' => "published_to",
                'visible'   => false,
            ],
            ['class' => \skeeks\cms\grid\CreatedByColumn::class],
            //['class' => \skeeks\cms\grid\UpdatedByColumn::class],
            [
                'class'     => \yii\grid\DataColumn::class,
                'value'     => function (\skeeks\cms\models\CmsContentElement $model) {
                    if (!$model->cmsTree) {
                        return null;
                    }
                    $path = [];
                    if ($model->cmsTree->parents) {
                        foreach ($model->cmsTree->parents as $parent) {
                            if ($parent->isRoot()) {
                                $path[] = "[".$parent->site->name."] ".$parent->name;
                            } else {
                                $path[] = $parent->name;
                            }
                        }
                    }
                    $path = implode(" / ", $path);
                    return "<small><a href='{$model->cmsTree->url}' target='_blank' data-pjax='0'>{$path} / {$model->cmsTree->name}</a></small>";
                },
                'format'    => 'raw',
                'filter'    => false,
                //'filter' => \skeeks\cms\helpers\TreeOptions::getAllMultiOptions(),
                'attribute' => 'tree_id',
            ],
            'additionalSections' => [
                'class'   => \yii\grid\DataColumn::class,
                'value'   => function (\skeeks\cms\models\CmsContentElement $model) {
                    $result = [];
                    if ($model->cmsContentElementTrees) {
                        foreach ($model->cmsContentElementTrees as $contentElementTree) {
                            $site = $contentElementTree->tree->root->site;
                            $result[] = "<small><a href='{$contentElementTree->tree->url}' target='_blank' data-pjax='0'>[{$site->name}]/.../{$contentElementTree->tree->name}</a></small>";
                        }
                    }
                    return implode('<br />', $result);
                },
                'format'  => 'raw',
                'label'   => \Yii::t('skeeks/cms', 'Additional sections'),
                'visible' => false,
            ],
            [
                'attribute' => 'active',
                'class'     => \skeeks\cms\grid\BooleanColumn::class,
            ],
            [
                'class'  => \yii\grid\DataColumn::class,
                'label'  => "Смотреть",
                'value'  => function (\skeeks\cms\models\CmsContentElement $model) {
                    return \yii\helpers\Html::a('<i class="glyphicon glyphicon-arrow-right"></i>', $model->absoluteUrl,
                        [
                            'target'    => '_blank',
                            'title'     => \Yii::t('skeeks/cms', 'Watch to site (opens new window)'),
                            'data-pjax' => '0',
                            'class'     => 'btn btn-default btn-sm',
                        ]);
                },
                'format' => 'raw',
            ],
        ];
        return $columns;
    }
    /**
     * @param CmsContent $cmsContent
     * @return array
     */
    public static function getColumnsByContent($cmsContent = null, $dataProvider = null)
    {
        $autoColumns = [];
        if (!$cmsContent) {
            return [];
        }
        $model = null;
        //$model = CmsContentElement::find()->where(['content_id' => $cmsContent->id])->one();
        if (!$model) {
            $model = new CmsContentElement([
                'content_id' => $cmsContent->id,
            ]);
        }
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                $autoColumns[] = [
                    'attribute' => $name,
                    'visible'   => false,
                    'format'    => 'raw',
                    'class'     => \yii\grid\DataColumn::class,
                    'value'     => function ($model, $key, $index) use ($name) {
                        if (is_array($model->{$name})) {
                            return implode(",", $model->{$name});
                        } else {
                            return $model->{$name};
                        }
                    },
                ];
            }
            $searchRelatedPropertiesModel = new \skeeks\cms\models\searchs\SearchRelatedPropertiesModel();
            $searchRelatedPropertiesModel->initProperties($cmsContent->cmsContentProperties);
            $searchRelatedPropertiesModel->load(\Yii::$app->request->get());
            if ($dataProvider) {
                $searchRelatedPropertiesModel->search($dataProvider);
            }
            /**
             * @var $model \skeeks\cms\models\CmsContentElement
             */
            if ($model->relatedPropertiesModel) {
                $autoColumns = ArrayHelper::merge($autoColumns,
                    GridViewStandart::getColumnsByRelatedPropertiesModel($model->relatedPropertiesModel,
                        $searchRelatedPropertiesModel));
            }
        }
        return $autoColumns;
    }


    /**
     * @return Model|ActiveRecord
     */
    public function getModel()
    {
        $model = parent::getModel();
        if (!$model) {
            return $model;
        }

        if ($model->cms_site_id != \Yii::$app->skeeks->site->id) {
            return null;
        }


        return $model;
    }
}

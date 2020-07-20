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
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteDomain;
use skeeks\cms\queryfilters\filters\modes\FilterModeEmpty;
use skeeks\cms\queryfilters\filters\modes\FilterModeNotEmpty;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HiddenField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Site management");
        $this->modelShowAttribute = "name";
        $this->modelClassName = \Yii::$app->skeeks->siteClass;

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
        $bool = [
            'isAllowChangeMode' => false,
            'field'             => [
                'class' => SelectField::class,
                'items' => [
                    'Y' => \Yii::t('yii', 'Yes'),
                    'N' => \Yii::t('yii', 'No'),
                ],
            ],
        ];


        $actions = ArrayHelper::merge(parent::actions(), [

            'index' => [
                "filters" => [
                    'visibleFilters' => [
                        'q',
                    ],

                    'filtersModel' => [
                        'rules' => [
                            ['q', 'safe'],
                            ['has_image', 'safe'],
                        ],

                        'attributeDefines' => [
                            'q',
                            'has_image',
                        ],


                        'fields' => [
                            'name'     => [
                                'isAllowChangeMode' => false,
                            ],
                         
                            'is_active'   => $bool,
                            'is_default'      => $bool,
                            'image_id' => [
                                'isAllowChangeMode' => true,
                                'modes'             => [
                                    FilterModeNotEmpty::class,
                                    FilterModeEmpty::class,
                                ],
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
                                            ['like', CmsSite::tableName().'.name', $e->field->value],
                                            ['like', CmsSite::tableName().'.id', $e->field->value],
                                            ['like', 'cmsSiteDomains.domain', $e->field->value],
                                        ]);

                                        $query->groupBy([CmsSite::tableName().'.id']);
                                    }
                                },
                            ],
                        ],
                    ],
                ],

                "grid" => [
                    'on init'       => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $dataProvider = $e->sender->dataProvider;

                        $query->joinWith('cmsSiteDomains as cmsSiteDomains');

                        $qCountDomains = CmsSiteDomain::find()->select(["total" => "count(*)"])->where(['cms_site_id' => new Expression(CmsSite::tableName() . ".id")]);

                        $query->groupBy(CmsSite::tableName() . ".id");
                        $query->select([
                            CmsSite::tableName() . '.*',
                            'countDomains' => $qCountDomains
                        ]);
                    },

                    'sortAttributes' => [
                        'countDomains' => [
                            'asc' => ['countDomains' => SORT_ASC],
                            'desc' => ['countDomains' => SORT_DESC],
                            'label' => 'Количество доменов',
                            'default' => SORT_ASC
                        ]
                    ],
                    'defaultOrder' => [
                        //'def' => SORT_DESC,
                        'priority' => SORT_ASC
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
                        'countDomains',
                        //'domains',
                    ],
                    'columns'        => [
                        'custom'       => [
                            'attribute' => 'name',
                            'format' => 'raw',
                            'value' => function (CmsSite $model) {

                                $data = [];
                                $data[] = ($model->is_default ? '<span class="fa fa-check text-success" title="Сайт по умолчанию"></span> ' : '') . Html::a($model->asText, "#", ['class' => 'sx-trigger-action']);

                                if ($model->cmsSiteDomains) {
                                    foreach ($model->cmsSiteDomains as $cmsSiteDomain)
                                    {
                                        $data[] = Html::a($cmsSiteDomain->domain, $cmsSiteDomain->url, [
                                            'data-pjax' => '0',
                                            'target' => '_blank',
                                            'style' => 'color: #333; max-width: 200px;'
                                        ]);
                                    }

                                }

                                $info = implode("<br />", $data);

                                return "<div class='row no-gutters'>
                                                <div class='sx-trigger-action' style='width: 50px;'>
                                                <a href='#' style='text-decoration: none; border-bottom: 0;'>
                                                    <img src='". ($model->image ? $model->image->src : Image::getCapSrc()) ."' style='max-width: 50px; max-height: 50px; border-radius: 5px;' />
                                                </a>
                                                </div>
                                                <div style='margin-left: 5px;'>" . $info . "</div></div>";

                                            ;
                            }
                        ],

                        'is_active'   => [
                            'class' => BooleanColumn::class,
                            'trueValue' => 1,
                            'falseValue' => 0,
                        ],
                        'is_default'      => [
                            'class' => BooleanColumn::class,
                            'trueValue' => 1,
                            'falseValue' => 0,
                        ],
                        'image_id' => [
                            'class' => ImageColumn2::class,
                        ],
                        'countDomains' => [
                            'value' => function(CmsSite $cmsSite) {
                                return $cmsSite->raw_row['countDomains'];
                            },
                            'attribute' => 'countDomains',
                            'label' => 'Количество доменов'
                        ],
                        'domains' => [
                            'value' => function(CmsSite $cmsSite) {
                                $result = ArrayHelper::map($cmsSite->cmsSiteDomains, "id", function($domain) {
                                    return Html::a($domain->domain, $domain->url, [
                                        'target' => '_blank',
                                        'data-pjax' => 0
                                    ]);
                                });

                                return implode("<br />", $result);
                            },
                            'attribute' => 'countDomains',
                            'format' => 'raw',
                            'label' => 'Домены'
                        ],
                    ],
                ],
            ],

            "create" => [
                'fields' => [$this, 'updateFields'],
            ],

            "domains" => [
                'class' => BackendGridModelRelatedAction::class,
                'accessCallback' => true,
                'name'            => "Домены",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-site-domain",
                'relation'        => ['cms_site_id' => 'id'],
                'priority'        => 600,
                'on gridInit'        => function($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_site_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],

            "emails" => [
                'class' => BackendGridModelRelatedAction::class,
                'accessCallback' => true,
                'name'            => "Email-ы",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-site-email",
                'relation'        => ['cms_site_id' => 'id'],
                'priority'        => 600,
                'on gridInit'        => function($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_site_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],

            "phones" => [
                'class' => BackendGridModelRelatedAction::class,
                'accessCallback' => true,
                'name'            => "Телефоны",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-site-phone",
                'relation'        => ['cms_site_id' => 'id'],
                'priority'        => 600,
                'on gridInit'        => function($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_site_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],


            "socials" => [
                'class' => BackendGridModelRelatedAction::class,
                'accessCallback' => true,
                'name'            => "Социальные сети",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/cms/admin-cms-site-social",
                'relation'        => ['cms_site_id' => 'id'],
                'priority'        => 600,
                'on gridInit'        => function($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_site_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],


            "update" => [
                'fields' => [$this, 'updateFields'],
            ],

            "activate-multi" => [
                'class' => BackendModelMultiActivateAction::class,
                'accessCallback' => true,
            ],

            "deactivate-multi" => [
                'class' => BackendModelMultiDeactivateAction::class,
                'accessCallback' => true,
            ],
        ]);

        return $actions;
    }

    public function updateFields($action)
    {
        $active = [
            'class'       => BoolField::class,
            'formElement' => BoolField::ELEMENT_RADIO_LIST,
            'allowNull'   => false,
        ];
        $def = [
            'class'       => BoolField::class,
            'formElement' => BoolField::ELEMENT_RADIO_LIST,
            'allowNull'   => false,
        ];

        if ($action->model->is_default) {
            $active = [
                'class'     => HiddenField::class,
                'hint'      => \Yii::t('skeeks/cms', 'Site selected by default always active')
            ];
            $def = [
                'class'     => HiddenField::class,
                'hint'      => \Yii::t('skeeks/cms', 'This site is the site selected by default. If you want to change it, you need to choose a different site, the default site.')
            ];
        }

        $result = [
            'image_id'    => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            'name',
            'is_active'      => $active,
            'is_default'         => $def,
            'description' => [
                'class' => TextareaField::class,
            ],
            'priority',

        ];

        /*if (!$action->model->isNewRecord) {
            $result['domains'] = [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', "Domains"),
                'fields' => [
                    'domains' => [
                        'class' => HtmlBlock::class,
                        'content' => \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
                            'label' => "",
                            'hint' => "",
                            'parentModel' => $action->model,
                            'relation' => [
                                'cms_site_id' => 'id'
                            ],

                            'controllerRoute' => '/cms/admin-cms-site-domain',
                            'gridViewOptions' => [
                                'columns' => [
                                    //['class' => 'yii\grid\SerialColumn'],
                                    'domain',
                                    'is_main' => [
                                        'class' => BooleanColumn::class,
                                        'attribute' => 'is_main',
                                        'trueValue' => 1,
                                        'falseValue' => 0,
                                    ],
                                    'is_https' => [
                                        'class' => BooleanColumn::class,
                                        'attribute' => 'is_https',
                                        'trueValue' => 1,
                                        'falseValue' => 0,
                                    ],
                                ],
                            ],
                        ])
                    ]
                ],
            ];
        }*/

        return $result;
    }

}

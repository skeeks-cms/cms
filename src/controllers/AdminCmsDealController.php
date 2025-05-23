<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use kartik\datecontrol\DateControl;
use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\actions\BackendGridModelAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelMultiDialogEditAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use skeeks\cms\backend\widgets\ContextMenuControllerActionsWidget;
use skeeks\cms\backend\widgets\ControllerActionsWidget;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDealType;
use skeeks\cms\models\CmsUser;
use skeeks\cms\money\models\MoneyCurrency;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\ForbiddenHttpException;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsDealController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Сделки");
        $this->modelShowAttribute = "asText";
        $this->modelClassName = CmsDeal::class;

        $this->permissionName = 'cms/admin-company';

        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {

        $actions = ArrayHelper::merge(parent::actions(), [

            'view' => [
                'class'    => BackendModelAction::class,
                'name'     => "Просмотр",
                'icon'     => 'fa fa-eye',
                'callback' => [$this, 'view'],
                'priority' => 50,
            ],

            'create'   => [
                'fields' => [$this, 'updateFields'],
            ],
            'update'   => [
                'fields' => [$this, 'updateFields'],
            ],
            'payments' => [
                'class'    => BackendModelAction::class,
                'name'     => 'Платежи',
                'priority' => 400,
                'callback' => [$this, 'payments'],
                'icon'     => 'fas fa-credit-card',
            ],
            'bills'    => [
                'class'    => BackendModelAction::class,
                'name'     => 'Счета',
                'priority' => 450,
                'callback' => [$this, 'bills'],
                'icon'     => 'fas fa-credit-card',
            ],
            /*'acts'     => [
                'class'    => BackendModelAction::class,
                'name'     => 'Акты',
                'priority' => 500,
                'callback' => [$this, 'acts'],
                'icon'     => 'fas fa-file-signature',
            ],*/

            /*'attachments' => [
                'class'    => BackendModelAction::class,
                'name'     => 'Приложения и ДС',
                'priority' => 600,
                'callback' => [$this, 'attachments'],
                'icon'     => 'fas fa-paperclip',
            ],*/

            'index' => [
                'filters' => [
                    'visibleFilters' => [
                        'id',
                        'name',
                        'cms_deal_type_id',
                        'is_active',
                    ],

                    'filtersModel' => [
                        'fields' => [
                            'is_active' => [
                                'field' => [
                                    'class' => BoolField::class,
                                ],
                            ],
                        ],
                    ],
                ],
                'grid'    => [
                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;

                        $query->select([
                            CmsDeal::tableName().'.*',
                            'end_at_sort' => new Expression("IF (end_at IS NULL, '0', '1')"),
                        ]);

                        $query->forManager();

                    },

                    'sortAttributes' => [
                        'end_at_sort' => [
                            'asc'  => ['end_at_sort' => SORT_ASC],
                            'desc' => ['end_at_sort' => SORT_DESC],
                            'name' => 'Количество платежей',
                        ],
                    ],
                    'defaultOrder'   => [
                        'is_active'   => SORT_DESC,
                        'end_at_sort' => SORT_DESC,
                        'end_at'      => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        //'crm_service_id',

                        //'id',
                        //'name',

                        'customName',

                        //'serial_number',

                        //'start_at',
                        //'end_at',

                        'amount',

                        'cms_company_id',
                        'cms_user_id',

                        //'is_periodic',
                        //'is_active',
                        //'is_auto',

                        //'period',
                        //'description',
                    ],
                    'columns'        => [


                        'cms_company_id' => [
                            'headerOptions'  => [
                                'style' => 'max-width: 150px;',
                            ],
                            'contentOptions' => [
                                'style' => 'max-width: 150px;',
                            ],

                            'value' => function (CmsDeal $cmsDeal) {

                                if ($cmsDeal->company) {
                                    return AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-company',
                                        'modelId'      => $cmsDeal->company->id,
                                        'content'      => '<i class="far fa-user"></i> '.$cmsDeal->company->asText,
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                } else {
                                    return '';
                                }


                            },
                        ],
                        'cms_user_id'    => [
                            'headerOptions'  => [
                                'style' => 'max-width: 150px;',
                            ],
                            'contentOptions' => [
                                'style' => 'max-width: 150px;',
                            ],

                            'value' => function (CmsDeal $cmsDeal) {

                                if ($cmsDeal->user) {
                                    return AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-user',
                                        'modelId'      => $cmsDeal->user->id,
                                        'content'      => '<i class="far fa-user"></i> '.$cmsDeal->user->asText,
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                } else {
                                    return '';
                                }
                            },
                        ],

                        'end_at_sort' => [
                            'format'    => 'raw',
                            'label'     => 'Количество платежей',
                            'attribute' => 'end_at_sort',
                            'value'     => function (CmsDeal $cmsDeal) {
                                return \Yii::$app->formatter->asInteger($cmsDeal->raw_row['end_at_sort']);
                            },
                        ],

                        'is_active' => [
                            'class' => BooleanColumn::class,
                        ],

                        'is_auto' => [
                            'class' => BooleanColumn::class,
                        ],

                        'start_at' => [
                            'class' => DateColumn::class,
                        ],
                        'end_at'   => [
                            //'class' => DateColumn::class,
                            'value' => function (CmsDeal $cmsDeal, $key, $index) {

                                $reuslt = "<div>";
                                if ($cmsDeal->end_at < time() && $cmsDeal->end_at && $cmsDeal->is_active) {
                                    \Yii::$app->view->registerJs(<<<JS
$('tr[data-key={$key}]').addClass('sx-tr-red');
JS
                                    );

                                    \Yii::$app->view->registerCss(<<<CSS
tr.sx-tr-red, tr.sx-tr-red:nth-of-type(odd), tr.sx-tr-red td
{
background: #ffecec40 !important;
}
CSS

                                    );


                                    $reuslt = "<div style='color: red;'>";
                                } elseif (!$cmsDeal->is_active) {
                                    \Yii::$app->view->registerJs(<<<JS
$('tr[data-key={$key}]').addClass('sx-tr-gray');
JS
                                    );

                                    \Yii::$app->view->registerCss(<<<CSS
tr.sx-tr-gray, tr.sx-tr-gray:nth-of-type(odd), tr.sx-tr-gray td
{
background: #ececec !important;
opacity: 0.9;
}
CSS
                                    );


                                }

                                $reuslt .= \Yii::$app->formatter->asDate($cmsDeal->end_at)."<br /><small>".\Yii::$app->formatter->asRelativeTime($cmsDeal->end_at)."</small>";
                                $reuslt .= "</div>";
                                return $reuslt;
                            },
                        ],

                        'customName' => [
                            'format' => 'raw',
                            'label'  => 'Договор',
                            'value'  => function (CmsDeal $cmsDeal, $key, $index) {
                                $reuslt = "<div>";
                                $dateName = "Закончится";
                                if ($cmsDeal->end_at < time() && $cmsDeal->end_at && $cmsDeal->is_active) {
                                    $dateName = "Закончилась";
                                    \Yii::$app->view->registerJs(<<<JS
$('tr[data-key={$key}]').addClass('sx-tr-red');
JS
                                    );

                                    \Yii::$app->view->registerCss(<<<CSS
tr.sx-tr-red, tr.sx-tr-red:nth-of-type(odd), tr.sx-tr-red td
{
background: #ffecec40 !important;
}
CSS
                                    );
                                    $reuslt = "<div style='color: red;'>";
                                } elseif (!$cmsDeal->is_active) {
                                    $dateName = "Закончилась";
                                    \Yii::$app->view->registerJs(<<<JS
$('tr[data-key={$key}]').addClass('sx-tr-gray');
JS
                                    );

                                    \Yii::$app->view->registerCss(<<<CSS
tr.sx-tr-gray, tr.sx-tr-gray:nth-of-type(odd), tr.sx-tr-gray td
{
background: #ececec !important;
opacity: 0.5;
}
CSS
                                    );
                                }
                                $reuslt .= "<small style='color: gray;'>{$cmsDeal->dealType->name}</small><br />";
                                $reuslt .= Html::a($cmsDeal->asShortText, ['/crm/crm-deal/view', 'pk' => $cmsDeal->id], ['data-pjax' => 0, 'class' => 'sx-trigger-action',]);
                                $reuslt .= "<br />";

                                if ($cmsDeal->end_at) {
                                    $reuslt .= "<div title='".\Yii::$app->formatter->asRelativeTime($cmsDeal->end_at)."'>{$dateName}: ".\Yii::$app->formatter->asDate($cmsDeal->end_at)."</div>";
                                }

                                if ($cmsDeal->description) {
                                    $reuslt .= "<small style='color: gray;'>{$cmsDeal->description}</small><br />";
                                }


                                $reuslt .= "</div>";

                                return $reuslt;
                            },
                        ],

                        'amount' => [
                            'headerOptions'  => [
                                'style' => 'max-width: 80px;',
                            ],
                            'contentOptions' => [
                                'style' => 'max-width: 80px;',
                            ],

                            'value' => function (CmsDeal $cmsDeal) {

                                if ($cmsDeal->money->amount > 0) {
                                    $money = (string)$cmsDeal->money;
                                    if ($cmsDeal->period) {
                                        $money .= "/".StringHelper::strtolower($cmsDeal->periodAsText);

                                        if ($cmsDeal->is_auto) {
                                            $money .= '&nbsp;<i class="fas fa-check" style="color: green;" title="Включено автопродление/автоотключение услуги"></i>';
                                        }
                                    }
                                    return Html::tag('b', $money);
                                } else {
                                    return ' — ';
                                }

                            },
                        ],

                        'is_periodic' => [
                            'class' => BooleanColumn::class,
                        ],
                        'period'      => [
                            'value' => function (CmsDeal $cmsDeal) {
                                return $cmsDeal->periodAsText;
                            },
                        ],
                    ],
                ],
            ],

            /*"properties" => [
                'class'        => BackendModelMultiDialogEditAction::class,
                "name"         => "Свойства сделки",
                "viewDialog"   => "@skeeks/crm/views/crm-deal/_pact-properties",
                "eachCallback" => [$this, 'eachPactProperty'],
                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can($this->permissionName."/update", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return (\Yii::$app->user->can($this->permissionName."/update"));
                },
            ],*/

            'activate-multi'   => [
                'class'     => BackendModelMultiActivateAction::class,
                'attribute' => "is_active",
                'value'     => true,
            ],
            'deactivate-multi' => [
                'class'     => BackendModelMultiDeactivateAction::class,
                'attribute' => "is_active",
                'value'     => false,
            ],

            'delete' => [
                'accessCallback' => function (BackendModelAction $action) {

                    /**
                     * @var $model CmsDeal
                     */
                    $model = $action->model;

                    if (!$model) {
                        return false;
                    }

                    if ($model->bills || $model->payments
                        /*|| $model->external_id*/
                    ) {
                        return false;
                    }

                    return true;
                },
            ]
        ]);

        //ArrayHelper::remove($actions, 'delete');
        ArrayHelper::remove($actions, 'delete-multi');


        return $actions;
    }


    public function view()
    {
        return $this->render($this->action->id);
    }

    public function updateFields($action)
    {

        /**
         * @var $model CmsDeal
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        if (!$model->name && $model->dealType && \Yii::$app->request->post(RequestResponse::DYNAMIC_RELOAD_NOT_SUBMIT)) {
            $model->name = $model->dealType->name;
            $model->is_periodic = $model->dealType->is_periodic;
            $model->period = $model->dealType->period;
        }


        $result = [

            'doc' => [
                'class'  => FieldSet::class,
                'name'   => 'Сделка',
                'fields' => [


                    'cms_deal_type_id' => [
                        'class'          => SelectField::class,
                        'items'          => ArrayHelper::map(
                            CmsDealType::find()->all(),
                            'id',
                            'asText'
                        ),
                        'elementOptions' => [
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ],
                    ],

                    'name',
                    'description'      => [
                        'class'          => TextareaField::class,
                        'elementOptions' => [
                            'placeholder' => 'Данные из этого поля видят клиенты',
                        ],
                    ],


                ],
            ],

            'client' => [
                'class'  => FieldSet::class,
                'name'   => 'Компания или клиент (заполнить хотя бы одно)',
                'fields' => [


                    'cms_company_id' => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'modelClass'  => CmsCompany::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsCompany::find()->forManager();
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                        ],
                    ],
                    'cms_user_id'    => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'modelClass'  => CmsUser::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsUser::find()->forManager();
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                        ],
                    ],


                ],
            ],

        ];


        $result['is_periodic'] = [
            'class'          => BoolField::class,
            'elementOptions' => [
                'data' => [
                    'form-reload' => 'true',
                ],
            ],
        ];

        if ($model->is_periodic) {
            $result['period'] = [
                'class' => SelectField::class,
                'items' => CmsDealType::optionsForPeriod(),
            ];
        }

        $result['amount'] = [
            'class' => NumberField::class,
            'step'  => 0.01,
        ];
        $result['currency_code'] = [
            'class' => SelectField::class,
            'items' => ArrayHelper::map(
                MoneyCurrency::find()->where(['is_active' => 1])->all(),
                'code',
                'asText'
            ),
        ];


        $result[] = [
            'class'   => HtmlBlock::class,
            'content' => '<div class="row"><div class="col-6">',
        ];
        $result['start_at'] = [
            'class'        => WidgetField::class,
            'widgetClass'  => DateControl::class,
            'widgetConfig' => [
                'type' => DateControl::FORMAT_DATETIME,
            ],
        ];
        $result[] = [
            'class'   => HtmlBlock::class,
            'content' => '</div><div class="col-6">',
        ];
        $result['end_at'] = [
            'class'        => WidgetField::class,
            'widgetClass'  => DateControl::class,
            'widgetConfig' => [

                'type' => DateControl::FORMAT_DATETIME,
                //                    'displayFormat' => 'php: d/m/Y',

                //'type' => DateControl::FORMAT_DATE,
            ],
        ];
        $result[] = [
            'class'   => HtmlBlock::class,
            'content' => '</div></div>',
        ];


        $result['is_active'] = [
            'class' => BoolField::class,
        ];

        $result['is_auto'] = [
            'class' => BoolField::class,
        ];

        if ($model->isNewRecord) {
            $result['isCreateNotify'] = [
                'class' => BoolField::class,
            ];
        }


        return $result;
    }


    public function bills()
    {
        if ($controller = \Yii::$app->createController('/cms/admin-cms-bill')) {
            /**
             * @var $controller BackendController
             * @var $indexAction BackendGridModelAction
             */
            $controller = $controller[0];
            $controller->actionsMap = [
                'index' => [
                    'configKey'         => $this->action->uniqueId,
                    'backendShowingKey' => $this->action->uniqueId,
                    'url'               => $this->action->urlData,
                ],
            ];

            if ($indexAction = ArrayHelper::getValue($controller->actions, 'index')) {
                $indexAction->url = $this->action->urlData;
                //$indexAction->filters = false;
                $visibleColumns = $indexAction->grid['visibleColumns'];

                //ArrayHelper::removeValue($visibleColumns, 'sender_crm_contractor_id');
                //ArrayHelper::removeValue($visibleColumns, 'receiver_crm_contractor_id');

                $indexAction->backendShowings = false;
                $indexAction->grid['visibleColumns'] = $visibleColumns;
                $indexAction->grid['columns']['actions']['isOpenNewWindow'] = true;
                $indexAction->grid['on init'] = function (Event $e) {

                    $dataProvider = $e->sender->dataProvider;

                    //$dataProvider->query->joinWith("crmBill2pacts as crmBill2pacts");
                    $dataProvider->query->forManager();
                    $dataProvider->query->joinWith("deals as deals");

                    $dataProvider->query->andWhere([
                        'or',
                        ['deals.id' => $this->model->id],
                    ]);
                };


                $indexAction->on('beforeRender', function (Event $event) use ($controller) {
                    if ($createAction = ArrayHelper::getValue($controller->actions, 'create')) {

                        $actions = [];
                        $createAction->isVisible = true;

                        /**
                         * @var $model CmsDeal
                         */
                        $model = $this->model;
                        /**
                         * @var $createAction BackendModelCreateAction
                         */
                        $createAction->url = ArrayHelper::merge($createAction->urlData, [
                            "ShopBill" => [
                                "deals"          => [$this->model->id],
                                "cms_company_id" => $model->cms_company_id ? $model->cms_company_id : "",
                                "cms_user_id"    => $model->cms_user_id ? $model->cms_user_id : "",
                            ],
                        ]);
                        $createAction->name = "Выставить счёт";

                        /**
                         * @var $contractor CrmContractor
                         */

                        $actions[] = $createAction;


                        $event->content = ControllerActionsWidget::widget([
                                'actions'         => $actions,
                                'isOpenNewWindow' => true,
                                /*'button'          => [
                                    'class' => 'btn btn-primary',
                                    //'style' => 'font-size: 11px; cursor: pointer;',
                                    'tag'   => 'button',
                                    'label' => 'Добавить реквизиты',
                                ],*/
                                'minViewCount'    => 1,
                                'itemTag'         => 'button',
                                'itemOptions'     => ['class' => 'btn btn-primary'],
                            ])."<br>";

                        /*$event->content = ContextMenuControllerActionsWidget::widget([
                                'actions'         => $actions,
                                'isOpenNewWindow' => true,
                                'button'          => [
                                    'class' => 'btn btn-primary',
                                    //'style' => 'font-size: 11px; cursor: pointer;',
                                    'tag'   => 'button',
                                    'label' => 'Выставить счет',
                                ],
                            ])."<br><br>";*/

                    }

                });


                return $indexAction->run();
            }
        }

        throw new ForbiddenHttpException("Нет доступа к разделу");
    }


    public function payments()
    {
        if ($controller = \Yii::$app->createController('/shop/admin-payment')) {
            /**
             * @var $controller BackendController
             * @var $indexAction BackendGridModelAction
             */
            $controller = $controller[0];
            $controller->actionsMap = [
                'index' => [
                    'configKey' => $this->action->uniqueId,
                ],
            ];

            if ($indexAction = ArrayHelper::getValue($controller->actions, 'index')) {
                $indexAction->url = $this->action->urlData;
                //$indexAction->filters = false;
                $indexAction->backendShowings = false;

                $visibleColumns = $indexAction->grid['visibleColumns'];

                ArrayHelper::removeValue($visibleColumns, 'client');
                /*ArrayHelper::removeValue($visibleColumns, 'sender_crm_contractor_id');*/
                /*ArrayHelper::removeValue($visibleColumns, 'receiver_crm_contractor_id');*/

                $indexAction->grid['visibleColumns'] = $visibleColumns;
                $indexAction->grid['columns']['actions']['isOpenNewWindow'] = true;
                $indexAction->grid['on init'] = function (Event $e) {

                    $dataProvider = $e->sender->dataProvider;
                    $dataProvider->query->forManager();
                    $dataProvider->query->joinWith("deals as deals");

                    $dataProvider->query->andWhere([
                        'or',
                        ['deals.id' => $this->model->id],
                    ]);
                };


                $indexAction->on('beforeRender', function (Event $event) use ($controller) {
                    if ($createAction = ArrayHelper::getValue($controller->actions, 'create')) {


                        $model = $this->model;
                        $actions = [];
                        $createAction->isVisible = true;
                        $createAction2 = clone $createAction;

                        /**
                         * @var $createAction BackendModelCreateAction
                         */
                        $createAction->url = ArrayHelper::merge($createAction->urlData, [
                            "ShopPayment" => [
                                "deals"          => [$this->model->id],
                                "cms_company_id" => $model->cms_company_id ? $model->cms_company_id : "",
                                "cms_user_id"    => $model->cms_user_id ? $model->cms_user_id : "",
                                'is_debit'       => 1,
                            ],
                        ]);


                        $createAction->name = "Заплатили нам";


                        $createAction2->url = ArrayHelper::merge($createAction2->urlData, [
                            "ShopPayment" => [
                                "deals"          => [$this->model->id],
                                "cms_company_id" => $model->cms_company_id ? $model->cms_company_id : "",
                                "cms_user_id"    => $model->cms_user_id ? $model->cms_user_id : "",
                                'is_debit'       => 0,
                            ],
                        ]);
                        $createAction2->name = "Мы заплатили";


                        $actions[] = $createAction;
                        $actions[] = $createAction2;

                        $event->content = ContextMenuControllerActionsWidget::widget([
                                'actions'         => $actions,
                                'isOpenNewWindow' => true,
                                'button'          => [
                                    'class' => 'btn btn-primary',
                                    //'style' => 'font-size: 11px; cursor: pointer;',
                                    'tag'   => 'button',
                                    'label' => 'Добавить платеж',
                                ],
                            ])."<br><br>";
                    }

                });


                return $indexAction->run();
            }
        }

        throw new ForbiddenHttpException("Нет доступа к разделу");
    }
}

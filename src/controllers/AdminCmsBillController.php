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
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\actions\BackendModelMultiAction;
use skeeks\cms\backend\actions\BackendModelMultiDialogEditAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use skeeks\cms\backend\widgets\ContextMenuControllerActionsWidget;
use skeeks\cms\base\InputWidget;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\measure\models\CmsMeasure;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDealType;
use skeeks\cms\models\CmsUser;
use skeeks\cms\money\Money;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\shop\models\queries\ShopBillQuery;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopDocument;
use skeeks\cms\shop\models\ShopDocument2bill;
use skeeks\cms\shop\models\ShopDocumentItem;
use skeeks\cms\shop\models\ShopPaySystem;
use skeeks\cms\shop\paysystem\BankTransferPaysystemHandler;
use skeeks\cms\widgets\AjaxSelect;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\base\WidgetEvent;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsBillController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Счета");
        $this->modelShowAttribute = "asText";
        $this->modelClassName = ShopBill::class;

        $this->permissionName = 'cms/admin-company';

        $this->generateAccessActions = false;

        $this->modelHeader = function () {
            return $this->renderPartial("@skeeks/cms/views/admin-cms-bill/_model_header", [
                'model' => $this->model,
            ]);
        };

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            'create' => [
                /*'isVisible' => false,*/
                'fields'    => [$this, 'updateFields'],
            ],
            'view' => [
                'class'       => BackendModelAction::class,
                'name'        => 'Счет',
                'icon'        => 'fa fa-file',
                'priority'    => 1,
                'defaultView' => '@skeeks/cms/views/admin-cms-bill/view',
            ],
            'update' => [
                'fields'         => [$this, 'updateFields'],
                'priority'       => 10,
                'accessCallback' => function ($action) {
                    /*$model = $action->model;
                    if ($model) {
                        if ($model->paid_at) {
                            return false;
                        }
                    }*/


                    return true;
                },
            ],
            'payments' => [
                'class'    => BackendModelAction::class,
                'name'     => 'Платежи',
                'priority' => 20,
                'callback' => [$this, 'payments'],
                'icon'     => 'fa fa-credit-card',
            ],
            'documents' => [
                'class'    => BackendModelAction::class,
                'name'     => 'Документы',
                'priority' => 30,
                'callback' => [$this, 'documents'],
                'icon'     => 'fa fa-file-signature',
            ],
            'close' => [
                'class'          => BackendModelAction::class,
                'name'           => 'Отменить счет',
                'icon'           => 'fa fa-ban',
                'isVisible'      => false,
                'callback'       => [$this, 'closeBillAction'],
                'accessCallback' => function (BackendModelAction $action) {
                    /**
                     * @var $model ShopBill
                     */
                    $model = $action->model;

                    if (!$model) {
                        return false;
                    }

                    return !$model->paid_at && !$model->closed_at;
                },
            ],
            'index'  => [
                'filters' => [
                    'visibleFilters' => [
                        'q',
                        'id',
                        'sender_crm_contractor_id',
                        //'paid_at',
                        'paid',
                        'crated',
                        'paidat',
                        'documents',
                    ],

                    'filtersModel' => [

                        'rules'            => [
                            ['q', 'safe'],
                            ['ready', 'safe'],
                            ['crated', 'safe'],
                            ['paid', 'safe'],
                            ['documents', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                            'paid',
                            'documents',
                            'crated',
                            'paidat',
                        ],

                        'fields' => [

                            'id' => [
                                'isAllowChangeMode' => false
                            ],

                            'crated' => [
                                'class' => WidgetField::class,
                                'widgetClass'  => DaterangeInputWidget::class,
                                'widgetConfig' => [
                                    'options' => [
                                        'placeholder' => 'Диапазон дат'
                                    ],
                                ],
                                'label'          => 'Когда выставлен',
                                /*'elementOptions' => [
                                    'placeholder' => 'Поиск',
                                ],*/
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ShopBillQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        if ($range = DaterangeInputWidget::parseRange($e->field->value)) {
                                            list($start, $end) = $range;

                                            $query->andWhere(['>=', "created_at", $start]);
                                            $query->andWhere(['<=', "created_at", $end]);
                                        }
                                    }
                                },
                            ],

                            'paidat' => [
                                'class' => WidgetField::class,
                                'widgetClass'  => DaterangeInputWidget::class,
                                'widgetConfig' => [
                                    'options' => [
                                        'placeholder' => 'Диапазон дат'
                                    ],
                                ],
                                'label'          => 'Когда оплачен',
                                /*'elementOptions' => [
                                    'placeholder' => 'Поиск',
                                ],*/
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ShopBillQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        if ($range = DaterangeInputWidget::parseRange($e->field->value)) {
                                            list($start, $end) = $range;

                                            $query->andWhere(['>=', "paid_at", $start]);
                                            $query->andWhere(['<=', "paid_at", $end]);
                                        }
                                    }
                                },
                            ],

                            'paid' => [
                                'class'    => SelectField::class,
                                'multiple' => false,
                                'label' => "Оплата",
                                'items' => [
                                    'is_paid' => 'Оплачен',
                                    'not_paid' => 'Не оплачен',
                                    'closed' => 'Отменен'
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value == 'is_paid') {
                                        $query->andWhere([
                                            "is not", ShopBill::tableName().'.paid_at', null,
                                        ]);
                                    } elseif ($e->field->value == 'not_paid') {
                                        $query->andWhere([
                                            "and",
                                            [ShopBill::tableName().'.paid_at' => null],
                                            [ShopBill::tableName().'.closed_at' => null],
                                        ]);
                                    } elseif ($e->field->value == 'closed') {
                                        $query->andWhere([
                                            "is not", ShopBill::tableName().'.closed_at', null,
                                        ]);
                                    }
                                },
                            ],

                            'documents' => [
                                'class'    => SelectField::class,
                                'multiple' => false,
                                'label'    => 'Закрывающие документы',
                                'items'    => [
                                    'no_documents' => 'Без актов/УПД',
                                    'not_closed'   => 'Не закрыты документами',
                                    'closed'       => 'Закрыты документами',
                                    'has_documents'=> 'Есть документы',
                                ],
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /** @var ActiveQuery $query */
                                    $query = $e->dataProvider->query;
                                    if (!$e->field->value) {
                                        return;
                                    }

                                    $amountSql = $this->closingDocumentsAmountSql();
                                    if ($e->field->value == 'no_documents') {
                                        $query->andWhere(new Expression("{$amountSql} <= 0"));
                                    } elseif ($e->field->value == 'not_closed') {
                                        $query->andWhere(new Expression("{$amountSql} < ".ShopBill::tableName().".amount"));
                                    } elseif ($e->field->value == 'closed') {
                                        $query->andWhere(new Expression("{$amountSql} >= ".ShopBill::tableName().".amount"));
                                    } elseif ($e->field->value == 'has_documents') {
                                        $query->andWhere(new Expression("{$amountSql} > 0"));
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
                                     * @var $query ShopBillQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->search($e->field->value);
                                        $query->joinWith('company as company');
                                        $query->joinWith('senderContractor as senderContractor');
                                        $query->orWhere([
                                            'LIKE', 'company.name', $e->field->value
                                        ]);
                                        $query->orWhere([
                                            'LIKE', 'senderContractor.name', $e->field->value
                                        ]);
                                    }
                                },
                            ],

                            'type'       => [
                                'field' => [
                                    'class' => SelectField::class,
                                    'items' => function ($e) {
                                        return ShopBill::optionsForType();
                                    },
                                ],
                            ],
                            'paid_at'    => [
                                'field' => [
                                    'class'        => WidgetField::class,
                                    'widgetClass'  => DateControl::class,
                                    'widgetConfig' => [
                                        'type' => DateControl::FORMAT_DATETIME,
                                    ],
                                ],
                            ],
                            'closed_at'  => [
                                'field' => [
                                    'class'        => WidgetField::class,
                                    'widgetClass'  => DateControl::class,
                                    'widgetConfig' => [
                                        'type' => DateControl::FORMAT_DATETIME,
                                    ],
                                ],
                            ],
                            'created_at' => [
                                'field' => [
                                    'class'        => WidgetField::class,
                                    'widgetClass'  => DateControl::class,
                                    'widgetConfig' => [
                                        'type' => DateControl::FORMAT_DATETIME,
                                    ],
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


                        $query->forManager();

                    },
                    
                    
                    'defaultOrder'   => [
                        'closed_at'  => SORT_ASC,
                        'created_at' => SORT_DESC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        //'id',

                        'created_at',

                        //'paid_at',
                        //'closed_at',
                        //'extend_pact_to',

                        //'type',

                        'amount',

                        'documents',

                        'client',

                        //'receiver_crm_contractor_id',

                        'description',

                        'deals',

                        //'code',
                    ],

                    'columns' => [
                        'amount' => [
                            'value' => function (ShopBill $ShopBill) {
                                return (string)$ShopBill->money;
                            },
                        ],

                        'documents' => [
                            'label' => 'Документы',
                            'format' => 'raw',
                            'value' => function (ShopBill $ShopBill) {
                                $result = [];

                                if ($ShopBill->isClosedByDocuments && $ShopBill->amount > 0) {
                                    $result[] = '<small style="display:block;margin-bottom:4px;color:#087a2f;font-weight:600;"><i class="fa fa-check"></i> Закрыт</small>';
                                } elseif ($ShopBill->documentedAmount > 0) {
                                    $result[] = '<small style="display:block;margin-bottom:4px;color:#a66a00;">Осталось закрыть: '.Html::encode((string)$ShopBill->documentBalanceMoney).'</small>';
                                }

                                foreach ($ShopBill->closingDocuments as $document) {
                                    $result[] = AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-document',
                                        'modelId'      => $document->id,
                                        'content'      => '<i class="far fa-file"></i> '.Html::encode($document->asText()),
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                }

                                if (!$result) {
                                    $result[] = '<span class="text-muted">Нет</span>';
                                }

                                return implode('', $result);
                            },
                        ],

                        'id' => [
                            'value' => function (ShopBill $ShopBill) {
                                return Html::a($ShopBill->id, Url::to(['view', 'pk' => $ShopBill->id]), [
                                    'data-pjax' => 0,
                                ]);
                            },
                        ],

                        'code' => [
                            'value' => function (ShopBill $ShopBill) {
                                return Html::a($ShopBill->code, Url::to(['view', 'pk' => $ShopBill->id]), [
                                    'data-pjax' => 0,
                                ]);
                            },
                        ],

                        'created_at'     => [
                            'headerOptions' => [
                                'style' => 'width: 200px;',
                            ],
                            'label' => "Счёт",
                            'value'         => function (ShopBill $ShopBill, $key, $index) {

                                $last = '';
                                if ($ShopBill->paid_at) {

                                    \Yii::$app->view->registerJs(<<<JS
                                        $('tr[data-key={$key}]').addClass('sx-tr-green');
JS
                                    );

                                    \Yii::$app->view->registerCss(<<<CSS
                                        tr.sx-tr-green, tr.sx-tr-green:nth-of-type(odd), tr.sx-tr-green td
                                        {
                                        background: #a1e8a136 !important;
                                        }
CSS
                                    );

                                    $last = "<br /><small style='color: green;' title='Оплачен'><i class=\"fa fa-check\"></i> </small>";
                                    if ($ShopBill->payments) {
                                        $last .= "<small>".\Yii::$app->formatter->asDate($ShopBill->paid_at)."</small><br />";
                                        foreach ($ShopBill->payments as $payment)
                                        {
                                            $last .= "<small>{$payment->asText}</small>";
                                        }

                                    } else {
                                        $last .= "<small style='color: red;'>Платежа нет!</small>";
                                    }

                                }
                                if ($ShopBill->closed_at) {

                                    \Yii::$app->view->registerJs(<<<JS
                                        $('tr[data-key={$key}]').addClass('sx-tr-red');
JS
                                    );

                                    \Yii::$app->view->registerCss(<<<CSS
                                        tr.sx-tr-red, tr.sx-tr-red:nth-of-type(odd), tr.sx-tr-red td
                                        {
                                            background: #f9f9f9 !important;
                                            opacity: 0.3;
                                        }
                                        tr.sx-tr-red:hover, tr.sx-tr-red:nth-of-type(odd), tr.sx-tr-red:hover td
                                        {
                                            opacity: 1;
                                        }
CSS
                                    );

                                    $last = "<br /><small style='color: red;' title='Отменен'><i class=\"fa fa-times-circle\"></i> ".\Yii::$app->formatter->asDate($ShopBill->closed_at)."</small>";
                                }

                            $title = "Счет №".$ShopBill->id." от ".\Yii::$app->formatter->asDate($ShopBill->created_at);
                                $titleAction = AjaxControllerActionsWidget::widget([
                                    'controllerId'            => '/cms/admin-cms-bill',
                                    'modelId'                 => $ShopBill->id,
                                    'isRunFirstActionOnClick' => true,
                                    'tag'                     => 'span',
                                    'content'                 => $title,
                                    'options'                 => [
                                        'class' => 'sx-bill-grid-title-action',
                                    'style' => 'cursor: pointer; color: #1d70b8; display: inline-block; font-size: 15px; white-space: nowrap;',
                                    ],
                                ]);

                            return $titleAction
                                .Html::tag('small', $ShopBill->shopPaySystem->name)
                                .$last;
                            },
                        ],
                        'paid_at'        => [
                            //'class' => DateColumn::class,
                            'value' => function (ShopBill $ShopBill, $key, $index) {


                                if ($ShopBill->paid_at) {

                                    \Yii::$app->view->registerJs(<<<JS
                    $('tr[data-key={$key}]').addClass('sx-tr-green');
JS
                                    );

                                    \Yii::$app->view->registerCss(<<<CSS
                    tr.sx-tr-green, tr.sx-tr-green:nth-of-type(odd), tr.sx-tr-green td
                    {
                    background: #a1e8a136 !important;
                    }
CSS
                                    );

                                    return \Yii::$app->formatter->asDatetime($ShopBill->paid_at)."<br />{$ShopBill->crmPayment->asText}";
                                }
                            },
                        ],
                        'extend_pact_to' => [
                            'class' => DateColumn::class,
                        ],
                        'closed_at'      => [
                            'class' => DateColumn::class,
                        ],

                        'type' => [
                            'value' => function (ShopBill $ShopBill) {
                                $result[] = $ShopBill->typeAsText;
                                /*if ($crmPayment->external_id) {
                                    $result[] = "<small>{$crmPayment->external_name}: $crmPayment->external_id</small>";
                                }*/
                                return (string)implode("<br>", $result);
                            },
                        ],


                        'client' => [
                            'label'  => 'Клиент / плательщик',
                            'format' => 'raw',
                            'value'  => function (ShopBill $shopBill) {
                                $result = [];

                                if ($shopBill->cms_company_id && $shopBill->company) {
                                    $result[] = AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-company',
                                        'modelId'      => $shopBill->company->id,
                                        'content'      => '<i class="fas fa-users"></i> '.Html::encode($shopBill->company->asText),
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                } elseif ($shopBill->cms_user_id && $shopBill->cmsUser) {
                                    $result[] = AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-user',
                                        'modelId'      => $shopBill->cmsUser->id,
                                        'content'      => '<i class="far fa-user"></i> '.Html::encode($shopBill->cmsUser->asText),
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                }

                                if ($shopBill->sender_contractor_id && $shopBill->senderContractor) {
                                    $result[] = '<small class="text-muted" style="display:block;margin-top:5px;">Юр. лицо плательщика</small>';
                                    $result[] = AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-contractor',
                                        'modelId'      => $shopBill->senderContractor->id,
                                        'content'      => '<i class="fas fa-briefcase"></i> '.Html::encode($shopBill->senderContractor->asText),
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                }

                                return $result ? implode('', $result) : '<span class="text-muted">Не указан</span>';
                            },
                        ],

                        'deals' => [
                            'label'  => 'Сделки',
                            'format' => 'raw',
                            'value'  => function (ShopBill $ShopBill) {
                                if ($ShopBill->deals) {
                                    $result = [];
                                    foreach ($ShopBill->deals as $crmDeal) {
                                        $result[] = AjaxControllerActionsWidget::widget([
                                            'controllerId' => '/cms/admin-cms-deal',
                                            'modelId'      => $crmDeal->id,
                                            'content'      => '<i class="far fa-file"></i> '.$crmDeal->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);
                                    }

                                    return (string)implode("", $result);
                                }
                            },
                        ],
                    ],

                    'on afterRun' => function (WidgetEvent $event) {

                        /**
                         * @var $grid GridView
                         * @var $query ActiveQuery
                         */
                        $grid = $event->sender;
                        $query = clone $grid->dataProvider->query;

                        $tableName = ShopBill::tableName();
                        $result = $query->select([$tableName.".id", 'sum' => new Expression("SUM({$tableName}.amount)")])
                            //->createCommand()->rawSql;
                            ->asArray()->one();

                        $sumAmount = ArrayHelper::getValue($result, 'sum');

                        $money = new Money($sumAmount, 'RUB');

                        $event->result = Alert::widget([
                            'options'     => [
                                'class' => 'alert alert-default',
                            ],
                            'closeButton' => false,
                            'body'        => <<<HTML
<div class="g-font-weight-300">
<span class="g-font-size-40">Всего: <span title="" style="">{$money}</span></span>
</div>
HTML
                            ,
                        ]);
                    },
                ],
            ],

            "close-multi" => [
                'class' => BackendModelMultiAction::class,
                'name'  => 'Отменить',
                'icon'  => 'fa fa-close',

                'eachCallback' => [$this, 'closeBill'],

            ],
            
            'delete' => [
                'accessCallback' => function (BackendModelAction $action) {

                    /**
                     * @var $model ShopBill
                     */
                    $model = $action->model;
    
                    if (!$model) {
                        return false;
                    }
    
                    if ($model->payments || $model->external_id) {
                        return false;
                    }
    
                    return true;
                },
            ],

            "log" => [
                'class'    => BackendModelLogAction::class,
            ],
        ]);

        //ArrayHelper::remove($actions, 'delete');
        ArrayHelper::remove($actions, 'delete-multi');


        return $actions;
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
                $indexAction->backendShowings = false;

                $visibleColumns = $indexAction->grid['visibleColumns'];
                ArrayHelper::removeValue($visibleColumns, 'client');
                $indexAction->grid['visibleColumns'] = $visibleColumns;
                $indexAction->grid['columns']['actions']['isOpenNewWindow'] = true;

                $indexAction->grid['on init'] = function (Event $e) {
                    $dataProvider = $e->sender->dataProvider;
                    $dataProvider->query->forManager();
                    $dataProvider->query->joinWith("bills as bills");
                    $dataProvider->query->andWhere(['bills.id' => $this->model->id]);
                };

                $indexAction->on('beforeRender', function (Event $event) use ($controller) {
                    if ($createAction = ArrayHelper::getValue($controller->actions, 'create')) {
                        $model = $this->model;
                        $actions = [];
                        $createAction->isVisible = true;
                        $createAction2 = clone $createAction;

                        $paymentData = [
                            'bills'          => [$model->id],
                            'cms_company_id' => $model->cms_company_id ? $model->cms_company_id : "",
                            'cms_user_id'    => $model->cms_user_id ? $model->cms_user_id : "",
                        ];

                        $createAction->url = ArrayHelper::merge($createAction->urlData, [
                            "ShopPayment" => ArrayHelper::merge($paymentData, [
                                'is_debit' => 1,
                            ]),
                        ]);
                        $createAction->name = "Заплатили нам";

                        $createAction2->url = ArrayHelper::merge($createAction2->urlData, [
                            "ShopPayment" => ArrayHelper::merge($paymentData, [
                                'is_debit' => 0,
                            ]),
                        ]);
                        $createAction2->name = "Мы заплатили";

                        $actions[] = $createAction;
                        $actions[] = $createAction2;

                        $event->content = ContextMenuControllerActionsWidget::widget([
                                'actions'         => $actions,
                                'isOpenNewWindow' => true,
                                'button'          => [
                                    'class' => 'btn btn-primary',
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

    public function documents()
    {
        if ($controller = \Yii::$app->createController('/cms/admin-cms-document')) {
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
                $indexAction->backendShowings = false;

                $visibleColumns = $indexAction->grid['visibleColumns'];
                ArrayHelper::removeValue($visibleColumns, 'cms_company_id');
                $indexAction->grid['visibleColumns'] = $visibleColumns;
                $indexAction->grid['columns']['actions']['isOpenNewWindow'] = true;

                $indexAction->grid['on init'] = function (Event $e) {
                    $dataProvider = $e->sender->dataProvider;
                    $dataProvider->query->forManager();
                    $dataProvider->query->joinWith('bills as bills');
                    $dataProvider->query->andWhere(['bills.id' => $this->model->id]);
                    $dataProvider->query->groupBy(ShopDocument::tableName().'.id');
                };

                $indexAction->on('beforeRender', function (Event $event) use ($controller) {
                    if ($createAction = ArrayHelper::getValue($controller->actions, 'create')) {
                        /** @var ShopBill $model */
                        $model = $this->model;
                        $documentTypes = [
                            ShopDocument::TYPE_ACT => [
                                'name' => 'Акт',
                                'icon' => 'fa fa-file-signature',
                            ],
                            ShopDocument::TYPE_UPD => [
                                'name' => 'УПД',
                                'icon' => 'fa fa-file-invoice',
                            ],
                            ShopDocument::TYPE_WAYBILL => [
                                'name' => 'Накладная',
                                'icon' => 'fa fa-truck',
                            ],
                            ShopDocument::TYPE_INVOICE_FACTURE => [
                                'name' => 'Счет-фактура',
                                'icon' => 'fa fa-file-text-o',
                            ],
                        ];

                        $actions = [];
                        foreach ($documentTypes as $type => $data) {
                            $action = clone $createAction;
                            $action->isVisible = true;
                            $action->name = $data['name'];
                            $action->icon = $data['icon'];
                            $action->url = ArrayHelper::merge($action->urlData, [
                                'bill_id' => $model->id,
                                'type' => $type,
                                '_sxb' => [
                                    'el'  => 1,
                                    'noa' => 1,
                                ],
                            ]);
                            $actions[] = $action;
                        }

                        $event->content = ContextMenuControllerActionsWidget::widget([
                                'actions'         => $actions,
                                'isOpenNewWindow' => true,
                                'button'          => [
                                    'class' => 'btn btn-primary',
                                    'tag'   => 'button',
                                    'label' => 'Добавить документ',
                                ],
                            ])."<br><br>";
                    }
                });

                return $indexAction->run();
            }
        }

        throw new ForbiddenHttpException("Нет доступа к разделу");
    }

    protected function closingDocumentsAmountSql()
    {
        $db = \Yii::$app->db;
        $billTable = ShopBill::tableName();
        $documentItemTable = ShopDocumentItem::tableName();
        $types = implode(',', array_map(function ($type) use ($db) {
            return $db->quoteValue($type);
        }, ShopDocument::closingTypes()));
        $canceledStatus = $db->quoteValue(ShopDocument::STATUS_CANCELED);

        return "(SELECT COALESCE(SUM(
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM {$documentItemTable} sdi_any
                        WHERE sdi_any.shop_document_id = sd.id
                            AND sdi_any.source_shop_bill_id IS NOT NULL
                    ) THEN (
                        SELECT COALESCE(SUM(sdi.amount), 0)
                        FROM {$documentItemTable} sdi
                        WHERE sdi.shop_document_id = sd.id
                            AND sdi.source_shop_bill_id = {$billTable}.id
                    )
                    ELSE sd.amount
                END
            ), 0)
            FROM ".ShopDocument::tableName()." sd
            INNER JOIN ".ShopDocument2bill::tableName()." sdb ON sdb.shop_document_id = sd.id
            WHERE sdb.shop_bill_id = {$billTable}.id
                AND sd.type IN ({$types})
                AND sd.status <> {$canceledStatus})";
    }

    public function closeBillAction(BackendModelAction $action)
    {
        /**
         * @var $model ShopBill
         */
        $model = $action->model;

        if (!$model) {
            return $this->redirect($this->url);
        }

        if (\Yii::$app->request->isPost && !$model->paid_at && !$model->closed_at) {
            $model->closed_at = time();
            $model->update(false, ['closed_at']);
        }

        return $this->redirect(Url::to(['view', $this->requestPkParamName => $model->id]));
    }

    public function beforeAction($action)
    {
        if ($action->id == 'update' && \Yii::$app->request->isPost) {
            $pk = \Yii::$app->request->get($this->requestPkParamName);
            if ($pk && ($bill = ShopBill::findOne($pk)) && $bill->paid_at) {
                $message = 'Оплаченный счет нельзя редактировать.';

                if (\Yii::$app->request->isAjax) {
                    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    \Yii::$app->response->data = [
                        'success' => false,
                        'message' => $message,
                    ];
                    \Yii::$app->end();
                }

                \Yii::$app->session->setFlash('error', $message);
                \Yii::$app->response->redirect(Url::to(['update', $this->requestPkParamName => $bill->id]))->send();
                \Yii::$app->end();
            }
        }

        if (\Yii::$app->request->get('sx-bill-product-search')) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            \Yii::$app->response->data = $this->actionBillProductSearch((string)\Yii::$app->request->get('q', ''));
            \Yii::$app->end();
        }

        return parent::beforeAction($action);
    }

    public function actionBillProductSearch($q = '')
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $q = trim((string)$q);
        $query = \skeeks\cms\shop\models\ShopProduct::find()
            ->alias('shopProduct')
            ->joinWith([
                'cmsContentElement as cmsContentElement',
                'brand as shopBrand',
            ])
            ->with([
                'cmsContentElement.cmsTree',
                'brand',
                'measure',
                'baseProductPrice',
            ])
            ->limit(12);

        if ($q !== '') {
            $query->andWhere([
                'or',
                ['like', 'cmsContentElement.name', $q],
                ['like', 'cmsContentElement.code', $q],
                ['like', 'shopBrand.name', $q],
                ['like', 'shopProduct.brand_sku', $q],
            ]);
        }

        $items = [];
        foreach ($query->orderBy(['cmsContentElement.name' => SORT_ASC])->all() as $product) {
            $element = $product->cmsContentElement;
            $price = $product->baseProductPrice ? (float)$product->baseProductPrice->price : 0;
            $imageSrc = '';
            if ($element && $element->mainProductImage) {
                $imageSrc = \Yii::$app->imaging->thumbnailUrlOnRequest(
                    $element->mainProductImage->src,
                    new \skeeks\cms\components\imaging\filters\Thumbnail()
                );
            }
            $currencyCode = $product->baseProductPrice && $product->baseProductPrice->currency_code
                ? $product->baseProductPrice->currency_code
                : \Yii::$app->money->currencyCode;

            $items[] = [
                'id'        => $product->id,
                'name'      => $element ? $element->productName : "#".$product->id,
                'price'     => $price,
                'priceText' => $this->billFormatMoney($price).' '.$this->billCurrencySymbol($currencyCode),
                'measure'   => $product->measure ? $product->measure->asShortText : 'шт',
                'brand'     => $product->brand ? $product->brand->name : '',
                'category'  => $element && $element->cmsTree ? $element->cmsTree->name : '',
                'image'     => $imageSrc,
            ];
        }

        return [
            'items' => $items,
        ];
    }

    public function renderBillItemsField(ShopBill $model)
    {
        $rows = [];
        if ($model->billItemsData !== null) {
            $rows = $model->billItemsData;
        } elseif (!$model->isNewRecord && $model->billItems) {
            foreach ($model->billItems as $item) {
                $rows[] = $item->asArray();
            }
        } elseif ($model->amount || $model->description) {
            $rows[] = [
                'shop_product_id' => null,
                'name'            => $model->description,
                'quantity'        => 1,
                'measure_name'    => 'шт',
                'price'           => (float)$model->amount,
                'discount_amount' => 0,
                'discount_value'  => null,
                'vat_name'        => 'Без НДС',
            ];
        }

        if (!$rows) {
            $rows[] = [
                'shop_product_id' => null,
                'name'            => '',
                'quantity'        => 1,
                'measure_name'    => 'шт',
                'price'           => 0,
                'discount_amount' => 0,
                'discount_value'  => null,
                'vat_name'        => 'Без НДС',
            ];
        }

        $measureOptions = $this->billMeasureOptions();
        $productSearchUrl = Json::htmlEncode(Url::current([
            'sx-bill-product-search' => 1,
            'q' => null,
        ]));
        $formName = $model->formName();
        $currencyCode = $model->currency_code ?: \Yii::$app->money->currencyCode;
        $model->currency_code = $currencyCode;
        $currencyCodeHtml = Html::encode($currencyCode);
        $currencySymbolHtml = Html::encode($this->billCurrencySymbol($currencyCode));
        $discountRawValue = (string)$model->discount_value;
        if ($discountRawValue === '' && (float)$model->discount_amount > 0) {
            $discountRawValue = (string)(float)$model->discount_amount;
        }
        $discountValue = Html::encode($discountRawValue);
        $discountAmount = Html::encode((float)$model->discount_amount);

        $rowsHtml = '';
        foreach ($rows as $index => $row) {
            $rowsHtml .= $this->renderBillItemRow($formName, $index, $row, $measureOptions);
        }

        $emptyRow = Json::htmlEncode($this->renderBillItemRow($formName, '__index__', [
            'shop_product_id' => null,
            'name'            => '',
            'quantity'        => 1,
            'measure_name'    => 'шт',
            'price'           => 0,
            'discount_amount' => 0,
            'discount_value'  => null,
            'vat_name'        => 'Без НДС',
        ], $measureOptions));

        $this->view->registerCss(<<<CSS
.sx-bill-items table {
    width: calc(100% - 28px);
    table-layout: fixed;
    border-collapse: collapse;
}
.sx-bill-items col.sx-bill-item-name-col {
    width: auto;
}
.sx-bill-items col.sx-bill-item-small-col {
    width: 10%;
}
.sx-bill-items th,
.sx-bill-items td {
    border: 1px solid #dee2e6;
    vertical-align: middle;
}
.sx-bill-items tbody tr {
    position: relative;
}
.sx-bill-items td:focus-within {
    box-shadow: inset 0 0 0 2px #80bdff;
}
.sx-bill-items tbody tr.sx-bill-item-name-editing > td {
    visibility: hidden;
}
.sx-bill-items tbody tr.sx-bill-item-name-editing > td:first-child {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    z-index: 20;
    display: block;
    visibility: visible;
    background: #fff;
    border: 2px solid #8f99a3;
    box-shadow: 0 0 0 1px rgba(143, 153, 163, 0.15);
}
.sx-bill-items tbody tr.sx-bill-item-name-editing > td:first-child:focus-within {
    box-shadow: 0 0 0 1px rgba(143, 153, 163, 0.15);
}
.sx-bill-items tbody tr.sx-bill-item-name-editing .sx-bill-item-name-input {
    height: 100%;
}
.sx-bill-items .sx-bill-item-add-cell:focus-within {
    box-shadow: none;
}
.sx-bill-items th {
    background: #f8f9fa;
    color: #666;
    font-weight: 600;
    padding: 10px 12px;
}
.sx-bill-items .sx-bill-item-name {
    width: 42%;
}
.sx-bill-items .sx-bill-item-small {
    width: 9%;
}
.sx-bill-items .sx-bill-item-discount-col {
    width: 8%;
}
.sx-bill-items input,
.sx-bill-items select {
    width: 100%;
    min-width: 0;
    height: 44px;
    border: 0;
    border-radius: 0;
    box-shadow: none;
    background: transparent;
    padding: 0 10px;
}
.sx-bill-items input:focus,
.sx-bill-items select:focus {
    box-shadow: none;
    background: #fff;
    outline: 0;
}
.sx-bill-items .sx-bill-item-amount {
    position: relative;
    padding: 0 10px;
}
.sx-bill-items .sx-bill-item-amount:focus-within {
    box-shadow: none;
}
.sx-bill-items .sx-bill-item-remove {
    position: absolute;
    right: -28px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 24px;
    padding: 0;
    border: 0;
    background: transparent;
    color: #adb5bd;
    line-height: 24px;
}
.sx-bill-items .sx-bill-item-remove:hover {
    color: #212529;
    background: transparent;
}
.sx-bill-items .sx-bill-item-remove:focus,
.sx-bill-items .sx-bill-item-remove:active {
    color: #adb5bd;
    background: transparent;
    box-shadow: none;
    outline: 0;
}
.sx-bill-items .sx-bill-item-add-cell {
    padding: 10px 12px;
}
.sx-bill-items .sx-bill-item-add {
    border: 0;
    background: transparent;
    color: #1a73e8;
    padding: 0;
    box-shadow: none;
    text-decoration: none;
    cursor: pointer;
}
.sx-bill-items .sx-bill-item-add:hover,
.sx-bill-items .sx-bill-item-add:focus,
.sx-bill-items .sx-bill-item-add:active {
    color: #1a73e8;
    background: transparent;
    box-shadow: none;
    outline: 0;
    text-decoration: none;
}
.sx-bill-items .sx-bill-summary {
    width: 320px;
    max-width: 100%;
    margin: 20px 0 24px auto;
}
.sx-bill-items .sx-bill-summary-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: baseline;
    gap: 20px;
    margin-bottom: 10px;
}
.sx-bill-items .sx-bill-summary-label {
    margin: 0;
    color: #343a40;
    font-weight: 400;
}
.sx-bill-items .sx-bill-discount {
    position: relative;
}
.sx-bill-items .sx-bill-discount > div {
    position: relative;
}
.sx-bill-items .sx-bill-discount-value {
    display: none;
}
.sx-bill-items .sx-bill-discount-open {
    border: 0;
    border-bottom: 1px dashed #777;
    background: transparent;
    color: #343a40;
    padding: 0 0 2px;
    box-shadow: none;
    text-align: right;
    font-weight: 600;
}
.sx-bill-items .sx-bill-discount-open:hover,
.sx-bill-items .sx-bill-discount-open:focus,
.sx-bill-items .sx-bill-discount-open:active {
    border-bottom-color: #212529;
    background: transparent;
    box-shadow: none;
    outline: 0;
}
.sx-bill-items .sx-bill-discount-open.is-empty {
    border-bottom-color: transparent;
    color: #adb5bd;
    font-weight: 400;
}
.sx-bill-items .sx-bill-total {
    margin-top: 18px;
    text-align: right;
    font-size: 20px;
    font-weight: 600;
}
.sx-bill-discount-popover {
    position: absolute;
    right: 0;
    top: auto;
    bottom: 36px;
    z-index: 3000;
    display: none;
    width: 220px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    box-shadow: 0 12px 35px rgba(0, 0, 0, .18);
    overflow: hidden;
    text-align: left;
}
.sx-bill-discount-popover-title {
    padding: 10px 12px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}
.sx-bill-discount-popover-body {
    padding: 12px;
}
.sx-bill-discount-mode {
    display: grid;
    grid-template-columns: 1fr 1fr;
    margin-bottom: 12px;
}
.sx-bill-discount-mode button {
    height: 34px;
    border: 0;
    background: #dee2e6;
    color: #495057;
    cursor: pointer;
    outline: 0;
    box-shadow: none;
}
.sx-bill-discount-mode button.is-active {
    background: #2b8bdc;
    color: #fff;
}
.sx-bill-discount-mode button:hover,
.sx-bill-discount-mode button:focus,
.sx-bill-discount-mode button:active {
    outline: 0;
    box-shadow: inset 0 0 0 1px rgba(43, 139, 220, .35);
}
.sx-bill-discount-popover-input {
    width: 100% !important;
    display: block !important;
    height: 38px;
    margin-bottom: 6px;
    padding: 7px 10px;
    background: #fff;
    border: 1px solid #ced4da !important;
    border-radius: 4px;
    color: #495057;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .04);
    box-sizing: border-box;
}
.sx-bill-discount-popover-input:focus {
    border-color: #80bdff !important;
    outline: 0;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, .12);
}
.sx-bill-discount-popover-hint {
    min-height: 18px;
    color: #868e96;
    font-size: 13px;
}
.sx-bill-discount-popover-apply {
    margin-top: 12px;
}
.sx-bill-items .sx-bill-item-discount-open {
    width: 100%;
    height: 44px;
    border: 0;
    border-radius: 0;
    background: transparent;
    color: #495057;
    text-align: left;
    padding: 0 10px;
    box-shadow: none;
}
.sx-bill-items .sx-bill-item-discount-open.is-empty {
    color: #adb5bd;
}
.sx-bill-items .sx-bill-item-discount-open:hover,
.sx-bill-items .sx-bill-item-discount-open:focus,
.sx-bill-items .sx-bill-item-discount-open:active {
    background: #fff;
    color: #212529;
    box-shadow: none;
    outline: 0;
}
.sx-bill-discount-modal-backdrop {
    position: fixed;
    z-index: 4000;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(0, 0, 0, .45);
}
.sx-bill-discount-modal {
    width: 520px;
    max-width: 100%;
    background: #fff;
    border-radius: 10px;
    padding: 24px;
    box-shadow: 0 18px 60px rgba(0, 0, 0, .25);
}
.sx-bill-discount-modal-title {
    font-size: 20px;
    font-weight: 600;
    line-height: 1.25;
    margin-bottom: 6px;
}
.sx-bill-discount-modal-meta {
    color: #868e96;
    margin-bottom: 24px;
}
.sx-bill-discount-modal-field {
    margin-bottom: 16px;
}
.sx-bill-discount-modal-field label {
    display: block;
    margin-bottom: 7px;
    font-weight: 600;
}
.sx-bill-discount-modal-control {
    display: flex;
}
.sx-bill-discount-modal input {
    width: 100%;
    height: 46px;
    border: 1px solid #d8dee4;
    border-radius: 4px;
    background: #fff;
    padding: 0 12px;
    box-shadow: none;
}
.sx-bill-discount-modal-control input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}
.sx-bill-discount-modal-addon {
    min-width: 46px;
    height: 46px;
    border: 1px solid #d8dee4;
    border-left: 0;
    border-radius: 0 4px 4px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #495057;
    background: #f8f9fa;
}
.sx-bill-discount-modal-qty {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr) 46px;
    gap: 10px;
}
.sx-bill-discount-modal-qty button {
    height: 46px;
    border-radius: 4px;
    background: #fff;
    font-size: 22px;
    font-weight: 600;
    cursor: pointer;
    outline: 0;
    box-shadow: none;
}
.sx-bill-discount-modal-qty-minus {
    border: 1px solid #ff3b30;
    color: #ff3b30;
}
.sx-bill-discount-modal-qty-plus {
    border: 1px solid #22c55e;
    color: #22c55e;
}
.sx-bill-discount-modal-qty button:hover,
.sx-bill-discount-modal-qty button:focus,
.sx-bill-discount-modal-qty button:active {
    outline: 0;
    box-shadow: 0 0 0 2px rgba(43, 139, 220, .12);
}
.sx-bill-discount-modal-discounts {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 18px;
}
.sx-bill-discount-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-top: 24px;
}
.sx-bill-discount-modal-total {
    color: #868e96;
    font-size: 22px;
    font-weight: 600;
}
.sx-bill-discount-modal-total strong {
    color: #495057;
}
.sx-bill-discount-modal-old-total {
    margin-left: 8px;
    color: #adb5bd;
    text-decoration: line-through;
}
.sx-bill-discount-modal-close {
    min-width: 120px;
    height: 46px;
}
.sx-bill-product-suggestions {
    position: absolute;
    z-index: 3000;
    display: none;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
}
.sx-bill-product-suggestion {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
}
.sx-bill-product-suggestion-main {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.sx-bill-product-suggestion-image {
    flex: 0 0 36px;
    width: 36px;
    height: 36px;
    border-radius: 4px;
    background: #f1f3f5;
    overflow: hidden;
    color: #adb5bd;
    text-align: center;
    line-height: 36px;
}
.sx-bill-product-suggestion-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.sx-bill-product-suggestion-text {
    min-width: 0;
}
.sx-bill-product-suggestion:last-child {
    border-bottom: 0;
}
.sx-bill-product-suggestion:hover,
.sx-bill-product-suggestion.is-active {
    background: #f8f9fa;
}
.sx-bill-product-suggestion-name {
    color: #343a40;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sx-bill-product-suggestion-meta {
    margin-top: 3px;
    color: #8a8f98;
    font-size: 12px;
}
.sx-bill-product-suggestion-price {
    flex: 0 0 auto;
    color: #343a40;
    font-weight: 600;
    white-space: nowrap;
}
.sx-bill-product-suggestion-empty {
    padding: 12px;
    color: #8a8f98;
}
.sx-bill-items-label {
    display: block;
    margin-bottom: 6px;
    color: #555;
    font-weight: 600;
}
CSS
        );

        $this->view->registerJs(<<<JS
(function() {
    var productSearchUrl = {$productSearchUrl};
    var emptyRow = {$emptyRow};
    var nextIndex = $(".sx-bill-items tbody tr").length;

    function numberValue(value) {
        value = (value || "").toString().replace(",", ".");
        var result = parseFloat(value);
        return isNaN(result) ? 0 : result;
    }

    function formatMoney(value) {
        var fixed = numberValue(value).toFixed(2);
        if (fixed.slice(-3) === ".00") {
            fixed = fixed.slice(0, -3);
        }
        var parts = fixed.split(".");
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        return parts.join(",");
    }

    function formatNumberInput(value) {
        return numberValue(value).toFixed(6).replace(/\.?0+$/, "");
    }

    function parseDiscount(raw, baseAmount) {
        raw = (raw || "").toString().replace(/\s+/g, "").replace(",", ".");
        baseAmount = Math.max(numberValue(baseAmount), 0);
        if (!raw) {
            return 0;
        }
        var amount;
        if (raw.indexOf("%") !== -1) {
            amount = baseAmount * numberValue(raw.replace("%", "")) / 100;
        } else {
            amount = numberValue(raw);
        }
        amount = Math.max(amount, 0);
        return Math.min(amount, baseAmount);
    }

    function parseItemDiscount(raw, unitPrice, quantity) {
        raw = (raw || "").toString().replace(/\s+/g, "").replace(",", ".");
        unitPrice = Math.max(numberValue(unitPrice), 0);
        quantity = Math.max(numberValue(quantity), 0);
        var baseAmount = unitPrice * quantity;
        if (!raw || baseAmount <= 0) {
            return 0;
        }

        var amount = raw.indexOf("%") !== -1
            ? baseAmount * numberValue(raw.replace("%", "")) / 100
            : numberValue(raw) * quantity;

        amount = Math.max(amount, 0);
        return Math.min(amount, baseAmount);
    }

    function discountPercent(amount, baseAmount) {
        baseAmount = Math.max(numberValue(baseAmount), 0);
        if (!baseAmount) {
            return 0;
        }
        return Math.min(Math.max(numberValue(amount) * 100 / baseAmount, 0), 100);
    }

    function formatPercent(value) {
        var fixed = numberValue(value).toFixed(2).replace(".", ",");
        return fixed.replace(/,00$/, "").replace(/(\,\d)0$/, "$1");
    }

    function discountLabel(raw, amount, baseAmount) {
        raw = (raw || "").toString().trim();
        amount = numberValue(amount);
        if (amount <= 0) {
            return "0";
        }
        if (raw.indexOf("%") !== -1) {
            return formatPercent(raw.replace("%", "")) + "%";
        }
        return formatMoney(amount) + " {$currencySymbolHtml}";
    }

    function setRowDiscount(row, rawValue) {
        var discountAmount = parseItemDiscount(rawValue, row.find(".sx-bill-item-price").val(), row.find(".sx-bill-item-quantity").val());
        row.find(".sx-bill-item-discount-value").val(rawValue || "");
        row.find(".sx-bill-item-discount-amount").val(discountAmount.toFixed(4));
        updateRowDiscountButton(row, discountAmount);
        return discountAmount;
    }

    function updateRowDiscountButton(row, discountAmount, displayRaw, displayAmount) {
        var baseAmount = numberValue(row.find(".sx-bill-item-quantity").val()) * numberValue(row.find(".sx-bill-item-price").val());
        var raw = displayRaw !== undefined ? displayRaw : row.find(".sx-bill-item-discount-value").val();
        var amount = displayAmount !== undefined ? displayAmount : discountAmount;
        var label = discountLabel(raw, amount, baseAmount);
        row.find(".sx-bill-item-discount-open")
            .text(label)
            .toggleClass("is-empty", numberValue(amount) <= 0);
    }

    function updateBillDiscountButton(grossSubtotal, totalDiscount) {
        var percent = discountPercent(totalDiscount, grossSubtotal);
        var label = totalDiscount > 0
            ? "(" + formatPercent(percent) + "%) " + formatMoney(totalDiscount) + " {$currencySymbolHtml}"
            : "0 {$currencySymbolHtml}";
        $(".sx-bill-discount-open")
            .text(label)
            .toggleClass("is-empty", numberValue(totalDiscount) <= 0);
    }

    function aggregateDiscountRaw(rows, totalDiscount, grossSubtotal) {
        totalDiscount = numberValue(totalDiscount);
        grossSubtotal = numberValue(grossSubtotal);
        if (totalDiscount <= 0 || grossSubtotal <= 0) {
            return "";
        }

        var commonPercent = null;
        var isCommonPercent = true;
        rows.forEach(function(item) {
            if (item.baseAmount <= 0) {
                return;
            }

            var itemPercent = discountPercent(item.discountAmount, item.baseAmount);
            if (commonPercent === null) {
                commonPercent = itemPercent;
            } else if (Math.abs(commonPercent - itemPercent) > 0.01) {
                isCommonPercent = false;
            }
        });

        if (isCommonPercent && commonPercent > 0) {
            return formatPercent(commonPercent) + "%";
        }

        return totalDiscount.toFixed(2).replace(".", ",");
    }

    function billTotals() {
        var grossSubtotal = 0;
        var itemsDiscount = 0;
        $(".sx-bill-items tbody tr").each(function() {
            var row = $(this);
            var qty = numberValue(row.find(".sx-bill-item-quantity").val());
            var price = numberValue(row.find(".sx-bill-item-price").val());
            var baseAmount = qty * price;
            grossSubtotal += baseAmount;
            itemsDiscount += parseItemDiscount(row.find(".sx-bill-item-discount-value").val(), price, qty);
        });

        return {
            grossSubtotal: grossSubtotal,
            itemsDiscount: itemsDiscount,
            netSubtotal: grossSubtotal - itemsDiscount
        };
    }

    function recalcBillItems() {
        var grossSubtotal = 0;
        var totalDiscount = 0;
        var rows = [];
        $(".sx-bill-items tbody tr").each(function() {
            var row = $(this);
            var qty = numberValue(row.find(".sx-bill-item-quantity").val());
            var price = numberValue(row.find(".sx-bill-item-price").val());
            var baseAmount = qty * price;
            var discountAmount = parseItemDiscount(row.find(".sx-bill-item-discount-value").val(), price, qty);
            var amount = baseAmount - discountAmount;
            grossSubtotal += baseAmount;
            totalDiscount += discountAmount;
            rows.push({
                row: row,
                baseAmount: baseAmount,
                discountAmount: discountAmount,
                amount: amount
            });
        });

        var total = grossSubtotal - totalDiscount;
        var aggregateRaw = aggregateDiscountRaw(rows, totalDiscount, grossSubtotal);

        rows.forEach(function(item) {
            var row = item.row;
            row.find(".sx-bill-item-discount-amount").val(item.discountAmount.toFixed(4));
            updateRowDiscountButton(row, item.discountAmount);
            row.find(".sx-bill-item-amount-value").text(formatMoney(item.amount));
        });

        $("#shopbill-discount_amount").val(totalDiscount.toFixed(4));
        $(".sx-bill-discount-value").val(aggregateRaw);
        $(".sx-bill-subtotal-value").text(formatMoney(grossSubtotal) + " {$currencySymbolHtml}");
        updateBillDiscountButton(grossSubtotal, totalDiscount);
        $(".sx-bill-discount-result").text("");
        $(".sx-bill-total-value").text(formatMoney(total));
        $("#shopbill-amount").val(total.toFixed(4)).prop("readonly", true);
    }

    function htmlEncode(value) {
        return $("<div/>").text(value || "").html();
    }

    function suggestions() {
        var dropdown = $(".sx-bill-product-suggestions");
        if (!dropdown.length) {
            dropdown = $('<div class="sx-bill-product-suggestions"></div>').appendTo("body");
        }
        return dropdown;
    }

    function hideSuggestions() {
        suggestions().hide().empty().removeData("input");
        $(".sx-bill-item-name-editing").removeClass("sx-bill-item-name-editing");
    }

    function positionSuggestions(input) {
        var table = input.closest(".sx-bill-items").find("table");
        var inputOffset = input.offset();
        var tableOffset = table.offset();
        suggestions().css({
            left: tableOffset.left,
            top: inputOffset.top + input.outerHeight(),
            width: table.outerWidth()
        });
    }

    function setNameEditing(input, isEditing) {
        input.closest("tr").toggleClass("sx-bill-item-name-editing", isEditing);
    }

    function renderProductSuggestions(input, items) {
        var dropdown = suggestions();
        dropdown.empty().data("input", input);
        positionSuggestions(input);

        if (!items.length) {
            dropdown.append('<div class="sx-bill-product-suggestion-empty">Ничего не найдено</div>').show();
            return;
        }

        items.forEach(function(item) {
            var meta = [];
            if (item.category) {
                meta.push(item.category);
            }
            if (item.brand) {
                meta.push(item.brand);
            }

            $('<div class="sx-bill-product-suggestion"></div>')
                .data("product", item)
                .append(
                    '<div class="sx-bill-product-suggestion-main">' +
                        '<div class="sx-bill-product-suggestion-image">' + (item.image ? '<img src="' + htmlEncode(item.image) + '" alt="" />' : '<i class="fa fa-image"></i>') + '</div>' +
                        '<div class="sx-bill-product-suggestion-text">' +
                            '<div class="sx-bill-product-suggestion-name">' + htmlEncode(item.name) + '</div>' +
                            (meta.length ? '<div class="sx-bill-product-suggestion-meta">' + htmlEncode(meta.join(" · ")) + '</div>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="sx-bill-product-suggestion-price">' + htmlEncode(item.priceText) + '</div>'
                )
                .appendTo(dropdown);
        });

        dropdown.show();
    }

    function selectProductSuggestion(input, product) {
        var row = input.closest("tr");
        input.val(product.name);
        row.find(".sx-bill-item-product-id").val(product.id);
        row.find(".sx-bill-item-price").val(product.price);
        row.find(".sx-bill-item-measure").val(product.measure);
        row.find(".sx-bill-item-discount-value").val("");
        row.find(".sx-bill-item-discount-amount").val("0");
        hideSuggestions();
        recalcBillItems();
    }

    function itemDiscountModal() {
        return $(".sx-bill-discount-modal-backdrop");
    }

    function activeDiscountRow() {
        return itemDiscountModal().data("row");
    }

    function syncItemDiscountModalFromRow(row) {
        var modal = itemDiscountModal();
        var name = row.find(".sx-bill-item-name-input").val() || "Позиция счета";
        var price = numberValue(row.find(".sx-bill-item-price").val());
        var qty = numberValue(row.find(".sx-bill-item-quantity").val()) || 1;
        var baseAmount = price * qty;
        var raw = row.find(".sx-bill-item-discount-value").val();
        var discountAmount = parseItemDiscount(raw, price, qty);
        var percent = discountPercent(discountAmount, baseAmount);
        var total = baseAmount - discountAmount;
        var unitDiscount = raw.indexOf("%") === -1 ? numberValue(raw) : (qty > 0 ? discountAmount / qty : 0);

        modal.find(".sx-bill-discount-modal-title").text(name);
        modal.find(".sx-bill-modal-price").val(price);
        modal.find(".sx-bill-modal-quantity").val(qty);
        modal.find(".sx-bill-modal-discount-percent").val(percent ? formatPercent(percent) : "");
        modal.find(".sx-bill-modal-discount-money").val(unitDiscount ? formatMoney(unitDiscount).replace(/\s/g, "") : "");
        modal.find(".sx-bill-discount-modal-total strong").text(formatMoney(total));
        modal.find(".sx-bill-discount-modal-total-currency").text(" {$currencySymbolHtml}");
        modal.find(".sx-bill-discount-modal-old-total").text(discountAmount > 0 ? formatMoney(baseAmount) + " {$currencySymbolHtml}" : "");
    }

    function openItemDiscountModal(row) {
        var modal = itemDiscountModal();
        modal.data("row", row).css("display", "flex");
        syncItemDiscountModalFromRow(row);
        modal.find(".sx-bill-modal-discount-percent").focus().select();
    }

    function closeItemDiscountModal() {
        itemDiscountModal().hide().removeData("row");
    }

    function applyItemDiscountFromModal(mode) {
        var row = activeDiscountRow();
        if (!row || !row.length) {
            return;
        }

        var modal = itemDiscountModal();
        var price = numberValue(modal.find(".sx-bill-modal-price").val());
        var qty = numberValue(modal.find(".sx-bill-modal-quantity").val()) || 1;
        row.find(".sx-bill-item-price").val(price);
        row.find(".sx-bill-item-quantity").val(qty);

        var baseAmount = price * qty;
        var rawValue;
        if (mode === "money") {
            var unitMoney = Math.min(Math.max(numberValue(modal.find(".sx-bill-modal-discount-money").val()), 0), price);
            var money = unitMoney * qty;
            rawValue = unitMoney ? unitMoney.toFixed(2).replace(".", ",") : "";
            modal.find(".sx-bill-modal-discount-percent").val(money ? formatPercent(discountPercent(money, baseAmount)) : "");
        } else {
            var percent = numberValue(modal.find(".sx-bill-modal-discount-percent").val());
            var amount = parseDiscount(percent ? percent + "%" : "", baseAmount);
            rawValue = percent ? formatPercent(percent) + "%" : "";
            modal.find(".sx-bill-modal-discount-money").val(amount && qty ? formatMoney(amount / qty).replace(/\s/g, "") : "");
        }

        setRowDiscount(row, rawValue);
        recalcBillItems();
        syncItemDiscountModalFromRow(row);
    }

    function billDiscountPopover() {
        return $(".sx-bill-discount-popover");
    }

    function openBillDiscountPopover() {
        var popover = billDiscountPopover();
        var totals = billTotals();
        var subtotal = totals.grossSubtotal;
        var raw = $(".sx-bill-discount-value").val();
        var amount = parseDiscount(raw, subtotal);
        var isPercent = raw.indexOf("%") !== -1;
        popover.data("mode", isPercent ? "percent" : "money");
        popover.find(".sx-bill-discount-mode button").removeClass("is-active");
        popover.find('[data-mode="' + (isPercent ? "percent" : "money") + '"]').addClass("is-active");
        popover.find(".sx-bill-discount-popover-input").val(isPercent ? formatPercent(raw.replace("%", "")) : (amount ? formatMoney(amount).replace(/\s/g, "") : ""));
        popover.find(".sx-bill-discount-popover-hint").text(amount ? formatPercent(discountPercent(amount, subtotal)) + "% / " + formatMoney(amount) + " {$currencySymbolHtml}" : "");
        popover.show().find(".sx-bill-discount-popover-input").focus().select();
    }

    function applyBillDiscountPopover() {
        var popover = billDiscountPopover();
        var mode = popover.data("mode") || "money";
        var value = numberValue(popover.find(".sx-bill-discount-popover-input").val());
        var rawValue = value ? (mode === "percent" ? formatPercent(value) + "%" : value.toFixed(2).replace(".", ",")) : "";
        var totals = billTotals();
        var amount = parseDiscount(rawValue, totals.grossSubtotal);
        var rows = [];
        $(".sx-bill-items tbody tr").each(function() {
            var row = $(this);
            var baseAmount = numberValue(row.find(".sx-bill-item-quantity").val()) * numberValue(row.find(".sx-bill-item-price").val());
            rows.push({
                row: row,
                baseAmount: baseAmount
            });
        });

        if (mode === "percent") {
            var percentRaw = amount ? formatPercent(discountPercent(amount, totals.grossSubtotal)) + "%" : "";
            rows.forEach(function(item) {
                setRowDiscount(item.row, percentRaw);
            });
            $(".sx-bill-discount-value").val(percentRaw);
        } else {
            var distributed = 0;
            rows.forEach(function(item, index) {
                var itemAmount = 0;
                if (amount > 0 && totals.grossSubtotal > 0 && item.baseAmount > 0) {
                    itemAmount = index === rows.length - 1
                        ? amount - distributed
                        : Math.round((amount * item.baseAmount / totals.grossSubtotal) * 10000) / 10000;
                }

                distributed += itemAmount;
                var qty = numberValue(item.row.find(".sx-bill-item-quantity").val()) || 1;
                var unitAmount = itemAmount / qty;
                setRowDiscount(item.row, unitAmount ? unitAmount.toFixed(4).replace(".", ",") : "");
            });
            $(".sx-bill-discount-value").val(rawValue);
        }

        var afterTotals = billTotals();
        popover.find(".sx-bill-discount-popover-hint").text(amount ? formatPercent(discountPercent(afterTotals.itemsDiscount, afterTotals.grossSubtotal)) + "% / " + formatMoney(afterTotals.itemsDiscount) + " {$currencySymbolHtml}" : "");
        recalcBillItems();
    }

    $("body").on("input focus", ".sx-bill-item-name-input", function(e) {
        var input = $(this);
        var value = input.val();
        setNameEditing(input, true);
        if (e.type === "input") {
            input.closest("tr").find(".sx-bill-item-product-id").val("");
        }

        clearTimeout(input.data("searchTimeout"));
        input.data("searchTimeout", setTimeout(function() {
            $.ajax({
                url: productSearchUrl,
                dataType: "json",
                data: {q: value},
                success: function(data) {
                    if (document.activeElement !== input[0]) {
                        return;
                    }
                    renderProductSuggestions(input, data.items || []);
                }
            });
        }, 180));
    });

    $("body").on("blur", ".sx-bill-item-name-input", function() {
        var input = $(this);
        setTimeout(function() {
            if (!suggestions().is(":visible")) {
                setNameEditing(input, false);
            }
        }, 120);
    });

    $("body").on("mousedown", ".sx-bill-product-suggestion", function(e) {
        e.preventDefault();
        var input = suggestions().data("input");
        if (input) {
            selectProductSuggestion(input, $(this).data("product"));
        }
    });

    $("body").on("keydown", ".sx-bill-item-name-input", function(e) {
        var dropdown = suggestions();
        if (!dropdown.is(":visible")) {
            return;
        }

        var items = dropdown.find(".sx-bill-product-suggestion");
        if (!items.length) {
            if (e.key === "Escape") {
                hideSuggestions();
            }
            return;
        }

        var active = items.filter(".is-active");
        var index = active.length ? items.index(active) : -1;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            items.removeClass("is-active").eq(Math.min(index + 1, items.length - 1)).addClass("is-active");
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            items.removeClass("is-active").eq(Math.max(index - 1, 0)).addClass("is-active");
        } else if (e.key === "Enter" && active.length) {
            e.preventDefault();
            selectProductSuggestion($(this), active.data("product"));
        } else if (e.key === "Escape") {
            hideSuggestions();
        }
    });

    $("body").on("click", function(e) {
        if (!$(e.target).closest(".sx-bill-product-suggestions, .sx-bill-item-name-input").length) {
            hideSuggestions();
        }
    });

    $("body").on("input change", ".sx-bill-item-quantity, .sx-bill-item-price, .sx-bill-item-discount-value, .sx-bill-discount-value", recalcBillItems);

    $("body").on("keydown", ".sx-bill-item-price, .sx-bill-item-quantity, .sx-bill-modal-price, .sx-bill-modal-quantity", function(e) {
        if (e.key !== "ArrowUp" && e.key !== "ArrowDown") {
            return;
        }

        e.preventDefault();
        var input = $(this);
        var value = numberValue(input.val());
        var nextValue = value + (e.key === "ArrowUp" ? 1 : -1);
        if (input.is(".sx-bill-item-quantity, .sx-bill-modal-quantity")) {
            nextValue = Math.max(nextValue, 1);
        } else {
            nextValue = Math.max(nextValue, 0);
        }
        input.val(formatNumberInput(nextValue)).trigger("input").trigger("change");
    });

    $("body").on("click", ".sx-bill-item-discount-open", function(e) {
        e.preventDefault();
        openItemDiscountModal($(this).closest("tr"));
        return false;
    });

    $("body").on("click", ".sx-bill-discount-modal-backdrop", function(e) {
        if ($(e.target).is(".sx-bill-discount-modal-backdrop")) {
            closeItemDiscountModal();
        }
    });

    $("body").on("click", ".sx-bill-discount-modal-close", function() {
        closeItemDiscountModal();
        return false;
    });

    $("body").on("input change", ".sx-bill-modal-price, .sx-bill-modal-quantity, .sx-bill-modal-discount-percent", function() {
        applyItemDiscountFromModal("percent");
    });

    $("body").on("input change", ".sx-bill-modal-discount-money", function() {
        applyItemDiscountFromModal("money");
    });

    $("body").on("click", ".sx-bill-discount-modal-qty-minus, .sx-bill-discount-modal-qty-plus", function() {
        var input = $(".sx-bill-modal-quantity");
        var delta = $(this).hasClass("sx-bill-discount-modal-qty-plus") ? 1 : -1;
        input.val(Math.max(numberValue(input.val()) + delta, 1));
        applyItemDiscountFromModal("percent");
        return false;
    });

    $("body").on("click", ".sx-bill-discount-open", function(e) {
        e.preventDefault();
        e.stopPropagation();
        openBillDiscountPopover();
        return false;
    });

    $("body").on("click", ".sx-bill-discount-mode button", function() {
        var mode = $(this).data("mode");
        var popover = billDiscountPopover();
        var totals = billTotals();
        var currentAmount = parseDiscount($(".sx-bill-discount-value").val(), totals.grossSubtotal);
        var nextValue = "";
        if (currentAmount > 0) {
            nextValue = mode === "percent"
                ? formatPercent(discountPercent(currentAmount, totals.grossSubtotal))
                : formatMoney(currentAmount).replace(/\s/g, "");
        }
        popover.data("mode", mode);
        popover.find(".sx-bill-discount-mode button").removeClass("is-active");
        $(this).addClass("is-active");
        popover.find(".sx-bill-discount-popover-input").val(nextValue).focus().select();
        return false;
    });

    $("body").on("input change", ".sx-bill-discount-popover-input", applyBillDiscountPopover);

    $("body").on("click", ".sx-bill-discount-popover-apply", function() {
        applyBillDiscountPopover();
        billDiscountPopover().hide();
        return false;
    });

    $("body").on("click", function(e) {
        if (!$(e.target).closest(".sx-bill-discount-popover, .sx-bill-discount-open").length) {
            billDiscountPopover().hide();
        }
    });

    $("body").on("keydown", function(e) {
        if (e.key === "Escape") {
            closeItemDiscountModal();
            billDiscountPopover().hide();
        }
    });

    $("body").on("click", ".sx-bill-item-add", function() {
        var html = emptyRow.replace(/__index__/g, nextIndex++);
        $(".sx-bill-items tbody").append(html);
        recalcBillItems();
        return false;
    });

    $("body").on("click", ".sx-bill-item-remove", function() {
        $(this).blur();
        var rows = $(".sx-bill-items tbody tr");
        if (rows.length > 1) {
            $(this).closest("tr").remove();
        } else {
            $(this).closest("tr").find("input[type=text], input[type=hidden], input[type=number]").val("");
            $(this).closest("tr").find(".sx-bill-item-quantity").val("1");
            $(this).closest("tr").find(".sx-bill-item-measure").val("шт");
            $(this).closest("tr").find(".sx-bill-item-discount-amount").val("0");
        }
        recalcBillItems();
        return false;
    });

    $(document).on("pjax:complete", function() {
        setTimeout(recalcBillItems, 200);
    });

    recalcBillItems();
})();
JS
        );

        return <<<HTML
<div class="col-12 sx-bill-items form-group">
    <label class="control-label sx-bill-items-label">Позиции счета</label>
    <input type="hidden" id="shopbill-amount" name="{$formName}[amount]" value="{$model->amount}" />
    <input type="hidden" id="shopbill-discount_amount" name="{$formName}[discount_amount]" value="{$discountAmount}" />
    <input type="hidden" id="shopbill-currency_code" name="{$formName}[currency_code]" value="{$currencyCodeHtml}" />
    <table>
        <colgroup>
            <col class="sx-bill-item-name-col" />
            <col class="sx-bill-item-small-col" />
            <col class="sx-bill-item-small-col" />
            <col class="sx-bill-item-small-col" />
            <col class="sx-bill-item-small-col" />
            <col class="sx-bill-item-discount-col" />
            <col class="sx-bill-item-small-col" />
        </colgroup>
        <thead>
        <tr>
            <th class="sx-bill-item-name">Название</th>
            <th class="sx-bill-item-small">Цена, {$currencySymbolHtml}</th>
            <th class="sx-bill-item-small">Кол-во</th>
            <th class="sx-bill-item-small">Ед. изм.</th>
            <th class="sx-bill-item-small">НДС</th>
            <th class="sx-bill-item-small">Скидка</th>
            <th class="sx-bill-item-small">Сумма, {$currencySymbolHtml}</th>
        </tr>
        </thead>
        <tbody>{$rowsHtml}</tbody>
        <tfoot>
        <tr>
            <td colspan="7" class="sx-bill-item-add-cell">
                <a href="#" class="sx-bill-item-add"><i class="fa fa-plus"></i> Добавить товар или услугу</a>
            </td>
        </tr>
        </tfoot>
    </table>
    <div class="sx-bill-summary">
        <div class="sx-bill-summary-row">
            <div class="sx-bill-summary-label">Предитог</div>
            <div class="sx-bill-subtotal-value">0 {$currencySymbolHtml}</div>
        </div>
        <div class="sx-bill-summary-row sx-bill-discount">
            <div class="sx-bill-summary-label">Скидка</div>
            <div>
                <input type="hidden" class="sx-bill-discount-value" name="{$formName}[discount_value]" value="{$discountValue}" />
                <button type="button" class="btn sx-bill-discount-open is-empty">0 {$currencySymbolHtml}</button>
                <span class="sx-bill-discount-result"></span>
                <div class="sx-bill-discount-popover">
                    <div class="sx-bill-discount-popover-title">Размер скидки</div>
                    <div class="sx-bill-discount-popover-body">
                        <div class="sx-bill-discount-mode">
                            <button type="button" data-mode="money" class="is-active">{$currencySymbolHtml}</button>
                            <button type="button" data-mode="percent">%</button>
                        </div>
                        <input type="text" class="form-control sx-bill-discount-popover-input" value="" autocomplete="off" />
                        <div class="sx-bill-discount-popover-hint"></div>
                        <button type="button" class="btn btn-primary btn-block sx-bill-discount-popover-apply">Готово</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="sx-bill-total">Итого: <span class="sx-bill-total-value">0</span> {$currencySymbolHtml}</div>
    </div>
    <div class="sx-bill-discount-modal-backdrop">
        <div class="sx-bill-discount-modal">
            <div class="sx-bill-discount-modal-title"></div>
            <div class="sx-bill-discount-modal-meta">Скидка по позиции счета</div>

            <div class="sx-bill-discount-modal-field">
                <label>Цена</label>
                <div class="sx-bill-discount-modal-control">
                    <input type="number" step="any" class="sx-bill-modal-price" value="0" />
                    <span class="sx-bill-discount-modal-addon">{$currencySymbolHtml}</span>
                </div>
            </div>

            <div class="sx-bill-discount-modal-field">
                <label>Количество</label>
                <div class="sx-bill-discount-modal-qty">
                    <button type="button" class="sx-bill-discount-modal-qty-minus">−</button>
                    <input type="number" step="any" class="sx-bill-modal-quantity" value="1" />
                    <button type="button" class="sx-bill-discount-modal-qty-plus">+</button>
                </div>
            </div>

            <div class="sx-bill-discount-modal-field">
                <label>Скидка</label>
                <div class="sx-bill-discount-modal-discounts">
                    <div class="sx-bill-discount-modal-control">
                        <input type="text" class="sx-bill-modal-discount-percent" value="" autocomplete="off" />
                        <span class="sx-bill-discount-modal-addon">%</span>
                    </div>
                    <div class="sx-bill-discount-modal-control">
                        <input type="text" class="sx-bill-modal-discount-money" value="" autocomplete="off" />
                        <span class="sx-bill-discount-modal-addon">{$currencySymbolHtml}</span>
                    </div>
                </div>
            </div>

            <div class="sx-bill-discount-modal-footer">
                <div class="sx-bill-discount-modal-total">Итого: <strong>0</strong><span class="sx-bill-discount-modal-total-currency"> {$currencySymbolHtml}</span><span class="sx-bill-discount-modal-old-total"></span></div>
                <button type="button" class="btn btn-default sx-bill-discount-modal-close">Закрыть</button>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    public function renderBillItemRow($formName, $index, array $row, array $measureOptions = [])
    {
        $prefix = "{$formName}[billItemsData][{$index}]";
        $name = Html::encode(ArrayHelper::getValue($row, 'name'));
        $productId = Html::encode(ArrayHelper::getValue($row, 'shop_product_id'));
        $price = Html::encode((float)ArrayHelper::getValue($row, 'price', 0));
        $quantity = Html::encode((float)ArrayHelper::getValue($row, 'quantity', 1));
        $discountRawValue = (string)ArrayHelper::getValue($row, 'discount_value');
        $discountRawAmount = (float)ArrayHelper::getValue($row, 'discount_amount', 0);
        if ($discountRawValue === '' && $discountRawAmount > 0) {
            $discountRawValue = (string)$discountRawAmount;
        }
        $discountValue = Html::encode($discountRawValue);
        $discountAmount = Html::encode($discountRawAmount);
        $measure = (string)ArrayHelper::getValue($row, 'measure_name', 'шт');
        if ($measure !== '' && !isset($measureOptions[$measure])) {
            $measureOptions[$measure] = $measure;
        }
        $measureSelect = Html::dropDownList("{$prefix}[measure_name]", $measure, $measureOptions, [
            'class' => 'form-control sx-bill-item-measure',
        ]);
        $vat = Html::encode(ArrayHelper::getValue($row, 'vat_name', 'Без НДС'));
        $vatNo = $this->selectedBillItemVat($vat, 'Без НДС');
        $vat0 = $this->selectedBillItemVat($vat, 'НДС 0%');
        $vat5 = $this->selectedBillItemVat($vat, 'НДС 5%');
        $vat7 = $this->selectedBillItemVat($vat, 'НДС 7%');
        $vat10 = $this->selectedBillItemVat($vat, 'НДС 10%');
        $vat20 = $this->selectedBillItemVat($vat, 'НДС 20%');

        return <<<HTML
<tr>
    <td>
        <input type="hidden" class="sx-bill-item-product-id" name="{$prefix}[shop_product_id]" value="{$productId}" />
        <input type="text" class="form-control sx-bill-item-name-input" name="{$prefix}[name]" value="{$name}" placeholder="Выберите или введите товар/услугу" autocomplete="off" />
    </td>
    <td><input type="number" step="any" class="form-control sx-bill-item-price" name="{$prefix}[price]" value="{$price}" /></td>
    <td><input type="number" step="any" class="form-control sx-bill-item-quantity" name="{$prefix}[quantity]" value="{$quantity}" /></td>
    <td>{$measureSelect}</td>
    <td>
        <select class="form-control" name="{$prefix}[vat_name]">
            <option value="Без НДС"{$vatNo}>Без НДС</option>
            <option value="НДС 0%"{$vat0}>НДС 0%</option>
            <option value="НДС 5%"{$vat5}>НДС 5%</option>
            <option value="НДС 7%"{$vat7}>НДС 7%</option>
            <option value="НДС 10%"{$vat10}>НДС 10%</option>
            <option value="НДС 20%"{$vat20}>НДС 20%</option>
        </select>
    </td>
    <td>
        <input type="hidden" class="sx-bill-item-discount-amount" name="{$prefix}[discount_amount]" value="{$discountAmount}" />
        <input type="hidden" class="sx-bill-item-discount-value" name="{$prefix}[discount_value]" value="{$discountValue}" />
        <button type="button" class="btn sx-bill-item-discount-open is-empty">0</button>
    </td>
    <td class="sx-bill-item-amount"><span class="sx-bill-item-amount-value">0,00</span><button class="btn sx-bill-item-remove" title="Удалить"><i class="fa fa-trash"></i></button></td>
</tr>
HTML;
    }

    protected function selectedBillItemVat($current, $value)
    {
        return $current == $value ? ' selected="selected"' : '';
    }

    protected function billMeasureOptions()
    {
        $result = [];
        foreach (CmsMeasure::find()->orderBy(['priority' => SORT_ASC])->all() as $measure) {
            $value = (string)$measure->asShortText;
            if ($value !== '') {
                $result[$value] = $value;
            }
        }

        if (!$result) {
            $result['шт'] = 'шт';
        }

        return $result;
    }

    protected function billFormatMoney($value)
    {
        $value = (float)$value;
        $result = number_format($value, 2, ',', ' ');
        if (substr($result, -3) === ',00') {
            $result = substr($result, 0, -3);
        }

        return $result;
    }

    protected function billCurrencySymbol($currencyCode)
    {
        $symbols = [
            'RUB' => '₽',
            'USD' => '$',
            'EUR' => '€',
        ];

        return ArrayHelper::getValue($symbols, $currencyCode, $currencyCode);
    }



    /**
     * @param CrmBill $crmBill
     * @return bool
     */
    public function closeBill(CrmBill $crmBill)
    {
        if ($crmBill->paid_at) {
            return false;
        }

        if (!$crmBill->closed_at) {
            $crmBill->closed_at = time();
            $crmBill->save();
        }
    }

    public function updateFields($action)
    {
        if (\Yii::$app->request->get('sx-bill-product-search')) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            \Yii::$app->response->data = $this->actionBillProductSearch((string)\Yii::$app->request->get('q', ''));
            \Yii::$app->end();
        }

        /**
         * @var $model ShopBill
         * @var $mainContractor CrmContractor
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());
        if ($model->isNewRecord && !$model->due_at) {
            $model->due_at = strtotime('today +30 days');
        }

        if (!$model->isNewRecord && $model->paid_at) {
            $this->view->registerCss(<<<CSS
form.sx-backend-form.sx-bill-paid-readonly {
    opacity: .86;
}
form.sx-backend-form.sx-bill-paid-readonly .form-control:disabled,
form.sx-backend-form.sx-bill-paid-readonly select:disabled,
form.sx-backend-form.sx-bill-paid-readonly textarea:disabled {
    cursor: not-allowed;
    background-color: #f7f8f9;
}
form.sx-backend-form.sx-bill-paid-readonly .sx-bill-item-add,
form.sx-backend-form.sx-bill-paid-readonly .sx-bill-item-remove {
    pointer-events: none;
    opacity: .45;
}
CSS
            );
            $this->view->registerJs(<<<JS
(function() {
    var lockPaidBillForm = function() {
        var form = $("form.sx-backend-form").first();
        if (!form.length) {
            return;
        }

        form.addClass("sx-bill-paid-readonly");
        form.find("input, select, textarea, button").prop("disabled", true);
        form.find(".select2-selection").attr("aria-disabled", "true");
    };

    lockPaidBillForm();
    setTimeout(lockPaidBillForm, 300);
})();
JS
            );
        }

        /**
         * Если не указана платежная система надо взять первую по сортировки
         */
        if (!$model->shop_pay_system_id) {
            $first = ShopPaySystem::find()->sort()->limit(1)->one();
            if ($first) {
                $model->shop_pay_system_id = $first->id;
            }
        }

        /*$findPacts = crmDeal::find()->active();

        if ($model->isNewRecord) {
            $contractor_id = \Yii::$app->request->get("contractor_id");
            $mainContractor = CrmContractor::findOne($contractor_id);

            $findPacts
                ->andWhere([
                    'customer_crm_contractor_id' => $contractor_id,
                ]);
        } else {

            /**
             * @var $crmDeal crmDeal
            $crmDeal = $model->getcrmDeals()->one();
            $mainContractor = $crmDeal->customerCrmContractor;
            $findPacts
                ->andWhere([
                    'executor_crm_contractor_id' => $crmDeal->executor_crm_contractor_id,
                ])
                ->andWhere([
                    'customer_crm_contractor_id' => $crmDeal->customer_crm_contractor_id,
                ]);
        }*/

        $this->view->registerCSS(<<<CSS
.field-shopbill-cms_company_id {
    display: none;
}
.field-shopbill-cms_user_id {
    display: none;
}
.btn.sx-active {
    background: #6c757d !important;
    color: white;   
}
.sx-bill-due-at-shortcuts {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
    margin-bottom: 26px;
}
.sx-bill-due-at-shortcut {
    border: 0;
    border-radius: 4px;
    background: #f1f3f5;
    color: #343a40;
    padding: 6px 12px;
    cursor: pointer;
}
.sx-bill-due-at-shortcut:hover,
.sx-bill-due-at-shortcut:focus {
    background: #e2e8f0;
    outline: 0;
}
CSS
        );




        $cms_company_id = (int) $model->cms_company_id;
        $cms_user_id = (int) $model->cms_user_id;

        $this->view->registerJs(<<<JS
var cms_company_id = {$cms_company_id};
var cms_user_id = {$cms_user_id};

$("body").on("click", ".sx-choose-paymenter .btn", function(e, data) {
    $(".field-shopbill-cms_company_id").slideUp();
    $(".field-shopbill-cms_user_id").slideUp();
    
    var is_first = false;
    
    if (data) {
        if (data.is_first) {
            is_first = true;
        }
    }
    
    if (is_first === false) {
        $("#shopbill-cms_company_id").val("");
        $("#shopbill-cms_user_id").val("");
    }
    
    
    $(".sx-choose-paymenter .btn").removeClass("sx-active");
    $(this).addClass("sx-active");
    $($(this).data("view")).slideDown();
    return false;
});

function reloadView() {
    var cms_company_id = $("#shopbill-cms_company_id").val();
    var cms_user_id = $("#shopbill-cms_user_id").val();
    
    if (cms_company_id) {
        $(".cms_company_id-btn").trigger("click", {
            'is_first' : true
        });
    } else if(cms_user_id) {
        $(".cms_user_id-btn").trigger("click", {
            'is_first' : true
        });
    }
    
    return false;
}

reloadView();



$(document).on('pjax:complete', function (e) {
    setTimeout(function() {
        reloadView();
    }, 200);
});

$("body").on("click", ".sx-bill-due-at-shortcut", function() {
    var days = parseInt($(this).data("days"), 10);
    var date = new Date();
    date.setDate(date.getDate() + days);

    var day = String(date.getDate()).padStart(2, "0");
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var year = date.getFullYear();
    var formatted = day + "-" + month + "-" + year;
    var timestamp = Math.floor(date.getTime() / 1000);
    var field = $(".field-shopbill-due_at");

    field.find("input[type=text], input:not([type])").val(formatted).trigger("change");
    field.find("input[type=hidden]").val(timestamp).trigger("change");
    return false;
});
JS
        );


        $result = [];

        $result['shop_pay_system_id'] = [
            'class'        => WidgetField::class,
            'widgetClass'  => AjaxSelectModel::class,
            'widgetConfig' => [
                'modelClass' => ShopPaySystem::class,
                'searchQuery' => function($word = '') {
                    $query = ShopPaySystem::find();
                    if ($word) {
                        $query->search($word);
                    }
                    return $query;
                },
                'options'       => [
                    'data' => [
                        'form-reload' => 'true',
                    ],
                ],
            ],
        ];

        $result['due_at'] = [
            'class'        => WidgetField::class,
            'widgetClass'  => DateControl::class,
            'widgetConfig' => [
                'type' => DateControl::FORMAT_DATE,
            ],
        ];

        $result['due_at_shortcuts'] = [
            'class' => HtmlBlock::class,
            'content' => <<<HTML
<div class="col-12 sx-bill-due-at-shortcuts">
    <button type="button" class="sx-bill-due-at-shortcut" data-days="1">1 день</button>
    <button type="button" class="sx-bill-due-at-shortcut" data-days="3">3 дня</button>
    <button type="button" class="sx-bill-due-at-shortcut" data-days="7">7 дней</button>
    <button type="button" class="sx-bill-due-at-shortcut" data-days="14">14 дней</button>
    <button type="button" class="sx-bill-due-at-shortcut" data-days="30">30 дней</button>
</div>
HTML
        ];





        $result['div'] = [
            'class' => HtmlBlock::class,
            'content' => '<div class="col-12 sx-choose-paymenter form-group"><div class="btn-group btn-block" role="group" aria-label="Basic example">
                  <button type="button" class="btn btn-default cms_company_id-btn" data-view=".field-shopbill-cms_company_id">Компания</button>
                  <button type="button" class="btn btn-default cms_user_id-btn" data-view=".field-shopbill-cms_user_id">Контакт</button>
                </div></div>'
        ];

        $result['cms_company_id'] = [
            'class'        => WidgetField::class,
            'widgetClass'  => AjaxSelectModel::class,
            'widgetConfig' => [
                'options'       => [
                    'data' => [
                        'form-reload' => 'true',
                    ],
                ],
                'modelClass' => CmsCompany::class,
                'searchQuery' => function($word = '') {
                    $query = CmsCompany::find()->forManager();
                    if ($word) {
                        $query->search($word);
                    }
                    return $query;
                },
            ],
        ];


        $result['cms_user_id'] = [
            'class'        => WidgetField::class,
            'widgetClass'  => AjaxSelectModel::class,

            'widgetConfig' => [
                'options'       => [
                    'data' => [
                        'form-reload' => 'true',
                    ],
                ],
                'modelClass' => CmsUser::class,
                'searchQuery' => function($word = '') {
                    $query = CmsUser::find()->forManager();
                    if ($word) {
                        $query->search($word);
                    }
                    return $query;
                },
            ],
        ];


        /*$result['client'] = [
            'class'  => FieldSet::class,
            'name'   => 'Компания / Клиент',
            'fields' => [
                'div' => [
                    'class' => HtmlBlock::class,
                    'content' => '<div class="col-12 sx-choose-paymenter"><div class="btn-group btn-block" role="group" aria-label="Basic example">
                          <button type="button" class="btn btn-default cms_company_id-btn" data-view=".field-shopbill-cms_company_id">Компания</button>
                          <button type="button" class="btn btn-default cms_user_id-btn" data-view=".field-shopbill-cms_user_id">Контакт</button>
                        </div></div>'
                ],

                'cms_company_id' => [
                    'class'        => WidgetField::class,
                    'widgetClass'  => AjaxSelectModel::class,
                    'widgetConfig' => [
                        'options'       => [
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ],
                        'modelClass' => CmsCompany::class,
                        'searchQuery' => function($word = '') {
                            $query = CmsCompany::find()->forManager();
                            if ($word) {
                                $query->search($word);
                            }
                            return $query;
                        },
                    ],
                ],

                'cms_user_id' => [
                    'class'        => WidgetField::class,
                    'widgetClass'  => AjaxSelectModel::class,

                    'widgetConfig' => [
                        'options'       => [
                            'data' => [
                                'form-reload' => 'true',
                            ],
                        ],
                        'modelClass' => CmsUser::class,
                        'searchQuery' => function($word = '') {
                            $query = CmsUser::find()->forManager();
                            if ($word) {
                                $query->search($word);
                            }
                            return $query;
                        },
                    ],
                ],
            ],
        ];*/



        $senderData = [];

        if ($model->cms_company_id || $model->cms_user_id) {

            $query = CmsContractor::find()
                ->forManager()
            ;

            if ($model->cms_company_id) {
                $query->joinWith('companies as companies');
                $query->andWhere(['companies.id' => $model->cms_company_id]);
            }

            if ($model->cms_user_id) {
                $query->joinWith('users as users');
                $query->andWhere(['users.id' => $model->cms_user_id]);
            }

            $senderData = ArrayHelper::map($query->all(), 'id', 'asText');
            if (count($senderData) == 1 && $model->isNewRecord) {
                foreach ($senderData as $id => $idData)
                {
                    $model->sender_contractor_id = $id;
                }
            }
        }

        $bankData = [];
        if ($model->shopPaySystem && $model->shopPaySystem->handler instanceof BankTransferPaysystemHandler) {
            /**
             * @var $handler BankTransferPaysystemHandler
             */
            $handler = $model->shopPaySystem->handler;
            if (!$model->receiver_contractor_id && $handler->receiver_contractor_id) {
                $model->receiver_contractor_id = $handler->receiver_contractor_id;
            }
            if (!$model->receiver_contractor_bank_id && $handler->receiver_contractor_bank_id) {
                $model->receiver_contractor_bank_id = $handler->receiver_contractor_bank_id;
            }
        }

        if ($model->receiver_contractor_id) {
            $bankData = ArrayHelper::map($model->receiverContractor->banks, 'id', 'asText');
        }

        if ($model->shopPaySystem) {
            if ($model->shopPaySystem->handler instanceof BankTransferPaysystemHandler) {
                $result['legal'] = [
                    'class'  => FieldSet::class,
                    'name'   => 'Реквизиты',
                    'fields' => [

                        'sender_contractor_id' => [
                            'class'        => SelectField::class,
                            'items'  => $senderData,
                        ],

                        'receiver_contractor_id' => [
                            'class'        => WidgetField::class,
                            'widgetClass'  => AjaxSelectModel::class,

                            'widgetConfig' => [
                                'options'       => [
                                    'data' => [
                                        'form-reload' => 'true',
                                    ],
                                ],
                                'modelClass' => CmsContractor::class,
                                'searchQuery' => function($word = '') {
                                    $query = CmsContractor::find()->our();
                                    if ($word) {
                                        $query->search($word);
                                    }
                                    return $query;
                                },
                            ],
                        ],

                        'receiver_contractor_bank_id' => [
                            'class'          => SelectField::class,
                            'items'          => $bankData,
                        ],
                    ],
                ];
            }
        }




        $dealData = [];

        if ($model->cms_company_id || $model->cms_user_id) {
            $query = CmsDeal::find()
                ->forManager()
            ;

            if ($model->cms_company_id) {
                $query->andWhere(['cms_company_id' => $model->cms_company_id]);
            }

            if ($model->cms_user_id) {
                $query->andWhere(['cms_user_id' => $model->cms_user_id]);
            }


            if ($model->isNewRecord) {
                $query->active();
            }


            $dealData = ArrayHelper::map($query->all(), 'id', 'asText');
        }













        $result['relations'] = [
            'class'  => FieldSet::class,
            'name'   => 'Связи',
            'fields' => [
                "deals" => [
                    'class'        => SelectField::class,
                    'multiple' => true,
                    'items'  => $dealData,
                ]

            ]
        ];


        $result['description'] = [
            'class'  => FieldSet::class,
            'name'   => 'Комментарий',
            'fields' => [
                "description" => [
                    'class' => TextareaField::class,
                ]

            ]
        ];


        $result['billItemsData'] = [
            'class' => HtmlBlock::class,
            'content' => $this->renderBillItemsField($model),
        ];

        /*if ($model->isNewRecord) {
            $result['isCreateNotify'] = [
                'class' => BoolField::class,
            ];
        }*/

        return $result;
    }
}

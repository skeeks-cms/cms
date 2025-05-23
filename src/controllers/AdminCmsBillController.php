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
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\actions\BackendModelMultiAction;
use skeeks\cms\backend\actions\BackendModelMultiDialogEditAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsDealType;
use skeeks\cms\models\CmsUser;
use skeeks\cms\money\models\MoneyCurrency;
use skeeks\cms\money\Money;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\shop\models\queries\ShopBillQuery;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopPaySystem;
use skeeks\cms\shop\paysystem\BankTransferPaysystemHandler;
use skeeks\cms\widgets\AjaxSelect;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
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

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            'create' => [
                /*'isVisible' => false,*/
                'fields'    => [$this, 'updateFields'],
            ],
            'update' => [
                'fields'         => [$this, 'updateFields'],
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
                    ],

                    'filtersModel' => [

                        'rules'            => [
                            ['q', 'safe'],
                            ['ready', 'safe'],
                            ['crated', 'safe'],
                            ['paid', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                            'paid',
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
                                        $data = explode("-", $e->field->value);
                                        $start = strtotime(trim(ArrayHelper::getValue($data, 0) . " 00:00:00"));
                                        $end = strtotime(trim(ArrayHelper::getValue($data, 1) .  " 23:59:59"));

                                        $query->andWhere(['>=', "created_at", $start]);
                                        $query->andWhere(['<=', "created_at", $end]);


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
                                        $data = explode("-", $e->field->value);
                                        $start = strtotime(trim(ArrayHelper::getValue($data, 0) . " 00:00:00"));
                                        $end = strtotime(trim(ArrayHelper::getValue($data, 1) .  " 23:59:59"));

                                        $query->andWhere(['>=', "paid_at", $start]);
                                        $query->andWhere(['<=', "paid_at", $end]);


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
                        
                        'cms_company_id',
                        'cms_user_id',

                        'sender_contractor_id',

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

                        'id' => [
                            'value' => function (ShopBill $ShopBill) {
                                return Html::a($ShopBill->id, $ShopBill->url, [
                                    'target'    => '_blank',
                                    'data-pjax' => 0,
                                ]);
                            },
                        ],

                        'code' => [
                            'value' => function (ShopBill $ShopBill) {
                                return Html::a($ShopBill->code, $ShopBill->url, [
                                    'target'    => '_blank',
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

                                return Html::a("Счет №".$ShopBill->id." от ".\Yii::$app->formatter->asDate($ShopBill->created_at), $ShopBill->url, [
                                        'target'    => '_blank',
                                        'data-pjax' => 0,
                                    ])."<br />".Html::tag('small', $ShopBill->shopPaySystem->name).$last;
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


                        'sender_contractor_id' => [
                            'value' => function (ShopBill $crmDeal) {

                                if ($crmDeal->sender_contractor_id) {
                                    return AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-contractor',
                                        'modelId'      => $crmDeal->senderContractor->id,
                                        'content'      => '<i class="far fa-user"></i> '.$crmDeal->senderContractor->asText,
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                }
                                return '';
                                

                            },
                        ],
                        'cms_company_id' => [
                            'value' => function (ShopBill $crmDeal) {

                                if ($crmDeal->cms_company_id) {
                                    return AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-company',
                                        'modelId'      => $crmDeal->company->id,
                                        'content'      => '<i class="fas fa-users"></i> '.$crmDeal->company->asText,
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                }
                                return '';


                            },
                        ],
                        'cms_user_id' => [
                            'value' => function (ShopBill $crmDeal) {

                                if ($crmDeal->cms_user_id) {
                                    return AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-user',
                                        'modelId'      => $crmDeal->cmsUser->id,
                                        'content'      => '<i class="far fa-user"></i> '.$crmDeal->cmsUser->asText,
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                }
                                return '';


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
        /**
         * @var $model ShopBill
         * @var $mainContractor CrmContractor
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

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
        


        $result['client'] = [
            'class'  => FieldSet::class,
            'name'   => 'Компания или клиент (заполнить хотя бы одно)',
            'fields' => [
                
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
        ];
        
        
        $result['description'] = [
            'class' => TextareaField::class,
        ];
        
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
            
            $dealData = ArrayHelper::map($query->all(), 'id', 'asText');
        }
        
        $result['deals'] = [
            'class'        => SelectField::class,
            'multiple' => true,
            'items'  => $dealData,
        ];
        
        
        
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
        
        
        $result['amount'] = [
            'class'  => FieldSet::class,
            'name'   => 'Сумма',
            'fields' => [
                
                'amount' => [
                    'class' => NumberField::class,
                    'step'  => 0.01,
                ],
        
                'currency_code' => [
                    'class' => SelectField::class,
                    'items' => ArrayHelper::map(
                        MoneyCurrency::find()->where(['is_active' => 1])->all(),
                        'code',
                        'asText'
                    ),
                ],
            ]
        ];
        
        $result['dates'] = [
            'class'  => FieldSet::class,
            'name'   => 'Статусы',
            'fields' => [
                
                'paid_at' => [
                    'class'        => WidgetField::class,
                    'widgetClass'  => DateControl::class,
                    'widgetConfig' => [
                        'type' => DateControl::FORMAT_DATETIME,
                    ],
                ],
    
                'closed_at' => [
                    'class'        => WidgetField::class,
                    'widgetClass'  => DateControl::class,
                    'widgetConfig' => [
                        'type' => DateControl::FORMAT_DATETIME,
                    ],
                ],
            ]
        ];
        
        

        /*if ($model->isNewRecord) {
            $result['isCreateNotify'] = [
                'class' => BoolField::class,
            ];
        }*/

        return $result;
    }
}

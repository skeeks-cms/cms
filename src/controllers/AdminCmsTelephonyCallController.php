<?php
/**
 * @author Semenov Alexander
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsUser;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;

class AdminCmsTelephonyCallController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Телефонные звонки");
        $this->modelShowAttribute = 'provider_call_id';
        $this->modelClassName = CmsTelephonyCall::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            'create' => new UnsetArrayValue(),
            'update' => new UnsetArrayValue(),
            'delete' => new UnsetArrayValue(),
            'delete-multi' => new UnsetArrayValue(),

            'index' => [
                'on beforeRender' => function (Event $e) {
                    /*$e->content = Alert::widget([
                        'closeButton' => false,
                        'options'     => [
                            'class' => 'alert-default',
                        ],
                        'body'        => <<<HTML
<!--<p>
В этом разделе отображается история всех телефонных звонков,
полученных через подключённых операторов телефонии.
</p>-->
HTML
                        ,
                    ]);*/
                },

                /*"backendShowings" => false,*/
                "filters"         => [
                    'visibleFilters' => [
                        'q',
                        //'date',
                        'cms_telephony_provider_id',
                        'cms_worker_user_id',

                        'cms_company_id',
                        'cms_user_id',

                        'status',
                    ],
                    'filtersModel' => [
                        'fields' => [

                            'status' => [
                                'defaultMode'       => FilterModeEq::ID,
                                'isAllowChangeMode' => false,
                                'field'             => [
                                    'class'    => SelectField::class,
                                    //'widgetClass' => SelectModelDialogUserWidget::class,
                                    'items'    => CmsTelephonyCall::statuses(),
                                    'multiple' => true
                                    //'multiple'    => new UnsetArrayValue(),
                                ],
                            ],

                            'cms_company_id' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $q = CmsCompany::find()->forManager();

                                            if ($word) {
                                                $q->search($word);
                                            }

                                            return $q;
                                        },
                                    ],
                                ],
                            ],
                            'cms_user_id' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $q = CmsUser::find()->forManager();

                                            if ($word) {
                                                $q->search($word);
                                            }

                                            return $q;
                                        },
                                    ],
                                ],
                            ],

                            'cms_worker_user_id' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $q = CmsUser::find()->isWorker();

                                            if ($word) {
                                                $q->search($word);
                                            }

                                            return $q;
                                        },
                                    ],
                                ],
                            ],
                        ]
                    ]
                ],

                'grid' => [

                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         */
                        $query = $e->sender->dataProvider->query;
                        $query->forManager();
                        $query->groupBy([CmsTelephonyCall::tableName().'.id']);

                        // при необходимости можно ограничить по сайту / пользователю
                        // $query->cmsSite();
                    },

                    'defaultOrder' => [
                        'created_at' => SORT_DESC,
                    ],

                    'visibleColumns' => [
                        /*'checkbox',*/
                        'actions',

                        'created_at',
                        'direction',
                        'from',
                        'to',
                        /*'custom',
                        'phones',*/

                        'status',
                        'duration',
                    ],

                    'columns' => [

                        'direction' => [
                            'headerOptions' => [
                                'style' => ['width' => '60px;'],
                            ],
                            'format'        => 'raw',
                            'value'         => function (CmsTelephonyCall $call) {
                                /*return $call->isIncoming()
                                    ? '<span title="Входящий">⬅️</span>'
                                    : '<span title="Исходящий">➡️</span>';*/
                                return $call->isIncoming()
                                    ? '<span title="Входящий">Входящий</span>'
                                    : '<span title="Входящий">Исходящий</span>';
                            },
                        ],

                        'phones' => [
                            'label'  => 'От / Кому',
                            'format' => 'raw',
                            'value'  => function (CmsTelephonyCall $call) {
                                $from = Html::tag('div', Html::encode($call->provider_phone_from));
                                $to = Html::tag('div', Html::encode($call->provider_phone_to), [
                                    'style' => 'color: gray; font-size: 11px;',
                                ]);
                                return $from.$to;
                            },
                        ],

                        'from' => [
                            'label'  => 'От',
                            'format' => 'raw',
                            'value'  => function (CmsTelephonyCall $call) {

                                if ($call->isIncoming()) {

                                    //сначала клиент
                                    $data = [];
                                    if ($call->company) {

                                        $data[] = AjaxControllerActionsWidget::widget([
                                            'controllerId' => '/cms/admin-cms-company',
                                            'modelId'      => $call->company->id,
                                            'isRunFirstActionOnClick' => true,
                                            'content'      => '<i class="fas fa-users"></i> '.$call->company->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);
                                        $data[] = "<div style='color: gray;'>" . $call->client_phone . "</div>";

                                    } elseif ($call->user) {
                                        $data[] = AjaxControllerActionsWidget::widget([
                                            'controllerId' => '/cms/admin-user',
                                            'isRunFirstActionOnClick' => true,
                                            'modelId'      => $call->user->id,
                                            'content'      => '<i class="fas fa-users"></i> '.$call->user->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);
                                        $data[] = "<div style='color: gray;'>" . $call->client_phone . "</div>";
                                    } else {
                                        $data[] = $call->client_phone;
                                    }




                                } else {

                                    $data = [];


                                    if ($call->workerUser) {
                                        $data[] = \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                                            'user'    => $call->workerUser,
                                            'isSmall' => true,
                                        ]);
                                        $data[] = "<div style='color: gray;'>" . $call->provider_phone_from . "</div>";
                                    } else {
                                        $data[] = $call->provider_phone_from;
                                    }


                                }

                                return "<div style='display: flex; flex-direction: column;'>".implode("", $data)."</div>";

                            },

                        ],

                        'to'     => [
                            'label'  => 'Кому',
                            'format' => 'raw',
                            'value'  => function (CmsTelephonyCall $call) {

                                if ($call->isIncoming()) {


                                    $data = [];


                                    if ($call->workerUser) {
                                        $data[] = \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                                            'user'    => $call->workerUser,
                                            'isSmall' => true,
                                        ]);
                                        $data[] = "<div style='color: gray;'>" . $call->provider_phone_from . "</div>";
                                    } else {
                                        $data[] = $call->provider_phone_from;
                                    }


                                } else {


                                    //сначала клиент
                                    $data = [];
                                    if ($call->company) {

                                        $data[] = AjaxControllerActionsWidget::widget([
                                            'isRunFirstActionOnClick' => true,
                                            'controllerId' => '/cms/admin-cms-company',
                                            'modelId'      => $call->company->id,
                                            'content'      => '<i class="fas fa-users"></i> '.$call->company->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);
                                        $data[] = "<div style='color: gray;'>" . $call->client_phone . "</div>";

                                    } elseif ($call->user) {
                                        $data[] = AjaxControllerActionsWidget::widget([
                                            'isRunFirstActionOnClick' => true,
                                            'controllerId' => '/cms/admin-user',
                                            'modelId'      => $call->user->id,
                                            'content'      => '<i class="fas fa-users"></i> '.$call->user->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);
                                        $data[] = "<div style='color: gray;'>" . $call->client_phone . "</div>";
                                    } else {
                                        $data[] = $call->client_phone;
                                    }




                                }

                                return "<div style='display: flex; flex-direction: column;'>".implode("", $data)."</div>";

                            },

                        ],
                        'custom' => [
                            'label'  => 'Звонок',
                            'format' => 'raw',
                            'value'  => function (CmsTelephonyCall $call) {

                                $result = [];

                                if ($call->isIncoming()) {

                                    //сначала клиент
                                    $data = [];
                                    if ($call->company) {

                                        $data[] = AjaxControllerActionsWidget::widget([
                                            'controllerId' => '/cms/admin-cms-company',
                                            'modelId'      => $call->company->id,
                                            'content'      => '<i class="fas fa-users"></i> '.$call->company->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);

                                    } elseif ($call->user) {
                                        $data[] = AjaxControllerActionsWidget::widget([
                                            'controllerId' => '/cms/admin-user',
                                            'modelId'      => $call->user->id,
                                            'content'      => '<i class="fas fa-users"></i> '.$call->user->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);
                                    } else {
                                        $data[] = $call->client_phone;
                                    }

                                    if ($call->workerUser) {
                                        $data[] = \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                                            'user'    => $call->workerUser,
                                            'isSmall' => true,
                                        ]);
                                    } else {
                                        $data[] = $call->provider_phone_to;
                                    }


                                } else {

                                    $data = [];


                                    if ($call->workerUser) {
                                        $data[] = \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                                            'user'    => $call->workerUser,
                                            'isSmall' => true,
                                        ]);
                                    } else {
                                        $data[] = $call->provider_phone_from;
                                    }

                                    if ($call->company) {

                                        $data[] = AjaxControllerActionsWidget::widget([
                                            'controllerId' => '/cms/admin-cms-company',
                                            'modelId'      => $call->company->id,
                                            'content'      => '<i class="fas fa-users"></i> '.$call->company->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);

                                    } elseif ($call->user) {
                                        $data[] = AjaxControllerActionsWidget::widget([
                                            'controllerId' => '/cms/admin-user',
                                            'modelId'      => $call->user->id,
                                            'content'      => '<i class="fas fa-users"></i> '.$call->user->asText,
                                            'options'      => [
                                                'style' => 'text-align: left;',
                                            ],
                                        ]);
                                    } else {
                                        $data[] = $call->client_phone;
                                    }


                                }

                                return "<div style='display: flex;'>".implode(" → ", $data)."</div>";


                            },
                        ],

                        'created_at' => [
                            'class' => DateTimeColumnData::class,
                        ],

                        'status' => [
                            'headerOptions' => [
                                'style' => ['width' => '160px;'],
                            ],
                            'format'        => 'raw',
                            'value'         => function (CmsTelephonyCall $call) {
                                $data = [];

                                $color = match ($call->status) {
                                    CmsTelephonyCall::STATUS_ANSWERED => 'green',
                                    CmsTelephonyCall::STATUS_FAILED => 'red',
                                    CmsTelephonyCall::STATUS_CONVERSATION => 'orange',
                                    CmsTelephonyCall::STATUS_RINGING => 'orange',
                                    default => 'gray',
                                };

                                $data[] = Html::tag('div', $call->statusAsText, [
                                    'style' => "color: {$color}; font-weight: bold;",
                                ]);

                                if ($call->provider) {
                                    $data[] = Html::tag('div', $call->provider->name, [
                                        'style' => 'font-size: 10px; color: gray;',
                                    ]);
                                }

                                if ($call->cms_record_file_id) {
                                    $data[] = Html::a('▶ запись', $call->cmsRecordFile->src, [
                                        'target' => '_blank',
                                        'data' => [
                                            'pjax' => 0
                                        ],
                                        'style'  => 'font-size: 11px; display: block;',
                                    ]);
                                }

                                return implode('', $data);
                            },
                        ],

                        'duration' => [
                            'headerOptions' => [
                                'style' => ['width' => '90px;'],
                            ],
                            'value'         => function (CmsTelephonyCall $call) {
                                return $call->getDurationFormatted();
                            },
                        ],

                        /*'actions' => [
                            'class' => DefaultActionColumn::class,
                        ],*/
                    ],
                ],
            ],
        ]);

        return $actions;
    }
}

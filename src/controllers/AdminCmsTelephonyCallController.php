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
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
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
                    \Yii::$app->view->registerCss(<<<CSS
audio.sx-call-audio::-webkit-media-controls-mute-button,
audio.sx-call-audio::-webkit-media-controls-volume-slider,
audio.sx-call-audio::-webkit-media-controls-volume-control-container {
    display: none !important;
}
CSS
                    );

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
                        'date',
                        'cms_telephony_provider_id',
                        'cms_worker_user_id',

                        'cms_company_id',

                        'status',
                    ],
                    'filtersModel' => [
                        'rules' => [
                            ['date', 'safe'],
                        ],
                        'attributeDefines' => [
                            'date',
                        ],
                        'fields' => [

                            'date' => [
                                'class'       => WidgetField::class,
                                'widgetClass' => DaterangeInputWidget::class,
                                'widgetConfig' => [
                                    'options' => [
                                        'placeholder' => 'Диапазон дат',
                                    ],
                                ],
                                'label' => 'Дата',
                                'on apply' => function (QueryFiltersEvent $e) {
                                    if ($e->field->value && ($range = DaterangeInputWidget::parseRange($e->field->value))) {
                                        [$start, $end] = $range;
                                        $createdAt = CmsTelephonyCall::tableName().'.created_at';
                                        $e->dataProvider->query->andWhere(['>=', $createdAt, $start]);
                                        $e->dataProvider->query->andWhere(['<=', $createdAt, $end]);
                                    }
                                },
                            ],

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
                        'custom',

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
                                if ($call->workerUser) {
                                    $worker = \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                                        'user'    => $call->workerUser,
                                        'isSmall' => true,
                                    ]);
                                } else {
                                    $workerPhone = $call->isIncoming()
                                        ? $call->provider_phone_to
                                        : $call->provider_phone_from;
                                    $worker = Html::encode($workerPhone);
                                }

                                if ($call->company) {
                                    $client = AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-company',
                                        'modelId'      => $call->company->id,
                                        'isRunFirstActionOnClick' => true,
                                        'content'      => '<i class="fas fa-users"></i> '.$call->company->asText,
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                } elseif ($call->user) {
                                    $client = AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-user',
                                        'modelId'      => $call->user->id,
                                        'isRunFirstActionOnClick' => true,
                                        'content'      => '<i class="fas fa-users"></i> '.$call->user->asText,
                                        'options'      => [
                                            'style' => 'text-align: left;',
                                        ],
                                    ]);
                                } else {
                                    $client = Html::encode($call->client_phone);
                                }

                                $workerPhone = $call->provider_user_num ?: ($call->isIncoming()
                                    ? $call->provider_phone_to
                                    : $call->provider_phone_from);

                                $workerMeta = array_filter([
                                    $workerPhone,
                                    $call->provider ? $call->provider->name : null,
                                ]);

                                $worker = Html::tag('div', $worker.Html::tag('div', Html::encode(implode(' · ', $workerMeta)), [
                                    'style' => 'color: gray; font-size: 11px;',
                                ]), [
                                    'style' => 'width: 240px; min-width: 240px;',
                                ]);

                                $client = Html::tag('div', $client.Html::tag('div', Html::encode($call->client_phone), [
                                    'style' => 'color: gray; font-size: 11px;',
                                ]), [
                                    'style' => 'min-width: 0;',
                                ]);

                                $arrow = $call->isIncoming() ? '←' : '→';
                                $direction = $call->isIncoming() ? 'Входящий' : 'Исходящий';
                                $arrow = Html::tag('span', $arrow, [
                                    'title'      => $direction,
                                    'aria-label' => $direction,
                                    'style'      => 'font-size: 22px; color: #6c757d; margin: 0 20px;',
                                ]);

                                return Html::tag('div', $worker.$arrow.$client, [
                                    'style' => 'display: flex; align-items: center; min-width: 0;',
                                ]);
                            },
                        ],

                        'created_at' => [
                            'class' => DateTimeColumnData::class,
                        ],

                        'duration' => [
                            'headerOptions' => [
                                'style' => ['width' => '320px;'],
                            ],
                            'format'        => 'raw',
                            'value'         => function (CmsTelephonyCall $call) {
                                if ($call->status !== CmsTelephonyCall::STATUS_ANSWERED) {
                                    $color = $call->status === CmsTelephonyCall::STATUS_FAILED ? 'red' : 'orange';
                                    return Html::tag('span', $call->statusAsText, [
                                        'style' => "color: {$color}; font-weight: bold;",
                                    ]);
                                }

                                $duration = $call->getDurationFormatted();
                                if ($duration === '00:00:00') {
                                    return '';
                                }

                                if ($call->cms_record_file_id) {
                                    return Html::tag('audio', '', [
                                        'class'      => 'sx-call-audio',
                                        'controls'   => true,
                                        'preload'    => 'metadata',
                                        'src'        => $call->cmsRecordFile->src,
                                        'style'      => 'display: block; width: 300px; max-width: 100%; height: 32px;',
                                        'aria-label' => 'Запись звонка',
                                        'onloadedmetadata' => 'this.volume = 1;',
                                        'onvolumechange'   => 'if (this.volume !== 1) { this.volume = 1; }',
                                    ]);
                                }

                                return $duration;
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

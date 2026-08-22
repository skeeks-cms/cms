<?php
/**
 * @author Semenov Alexander
 */

namespace skeeks\cms\controllers;

use skeeks\cms\assets\CmsTelephonyCallAdminAsset;
use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\actions\BackendModelViewAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\forms\CmsTelephonyCallLinksForm;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsUser;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\services\CmsTelephonyCallLinkService;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
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
        $this->modelDefaultAction = 'view';
        $this->modelHeader = function () {
            return $this->renderPartial('@skeeks/cms/views/admin-cms-telephony-call/_model_header', [
                'model' => $this->model,
            ]);
        };

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

            'view' => [
                'class' => BackendModelViewAction::class,
                'name' => 'Карточка звонка',
                'priority' => 10,
                'callback' => fn() => $this->render('view', ['model' => $this->model]),
            ],
            'links' => [
                'class' => BackendModelUpdateAction::class,
                'name' => 'Привязки',
                'icon' => 'fa fa-link',
                'priority' => 20,
                'fields' => [$this, 'linkFields'],
                'on '.BackendModelUpdateAction::EVENT_INIT_FORM_MODELS => static function (Event $event) {
                    $event->sender->formModels['links'] = CmsTelephonyCallLinksForm::fromCall(
                        $event->sender->model
                    );
                },
                'on '.BackendModelUpdateAction::EVENT_BEFORE_SAVE => function (Event $event) {
                    /** @var BackendModelUpdateAction $action */
                    $action = $event->sender;
                    /** @var CmsTelephonyCallLinksForm $form */
                    $form = $action->formModels['links'];
                    $action->isSaveFormModels = false;

                    (new CmsTelephonyCallLinkService())->updateLinks(
                        $action->model,
                        $form->cms_lead_id !== null ? (int)$form->cms_lead_id : null,
                        $form->cms_company_id !== null ? (int)$form->cms_company_id : null,
                        $form->cms_user_id !== null ? (int)$form->cms_user_id : null
                    );
                    $action->afterSaveUrl = ['view', 'pk' => $action->model->id];
                },
            ],

            'index' => [
                'on beforeRender' => function (Event $e) {
                    CmsTelephonyCallAdminAsset::register(\Yii::$app->view);

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
                                'class' => 'sx-call-direction-column',
                            ],
                            'contentOptions' => [
                                'class' => 'sx-call-direction-column',
                            ],
                            'format'        => 'raw',
                            'value'         => function (CmsTelephonyCall $call) {
                                $direction = $call->isIncoming() ? 'Входящий' : 'Исходящий';
                                return Html::tag('span', $direction, [
                                    'class' => 'sx-collection-cell__secondary',
                                    'title' => $direction,
                                ]);
                            },
                        ],

                        'phones' => [
                            'label'  => 'От / Кому',
                            'format' => 'raw',
                            'value'  => function (CmsTelephonyCall $call) {
                                return Html::tag(
                                    'div',
                                    Html::tag('div', Html::encode($call->provider_phone_from), [
                                        'class' => 'sx-collection-cell__primary',
                                    ])
                                    .Html::tag('div', Html::encode($call->provider_phone_to), [
                                        'class' => 'sx-collection-cell__secondary',
                                    ]),
                                    ['class' => 'sx-collection-cell sx-collection-cell--stack']
                                );
                            },
                        ],

                        'from' => [
                            'label'  => 'От',
                            'format' => 'raw',
                            'value'  => function (CmsTelephonyCall $call) {
                                return $call->isIncoming()
                                    ? $this->renderClientIdentity($call)
                                    : $this->renderWorkerIdentity($call, $call->provider_phone_from);
                            },
                        ],

                        'to'     => [
                            'label'  => 'Кому',
                            'format' => 'raw',
                            'value'  => function (CmsTelephonyCall $call) {
                                return $call->isIncoming()
                                    ? $this->renderWorkerIdentity($call, $call->provider_phone_from)
                                    : $this->renderClientIdentity($call);
                            },
                        ],
                        'custom' => [
                            'label'  => 'Звонок',
                            'format' => 'raw',
                            'value'  => function (CmsTelephonyCall $call) {
                                if ($call->workerUser) {
                                    $worker = CmsWorkerViewWidget::widget([
                                        'user'    => $call->workerUser,
                                        'isSmall' => true,
                                    ]);
                                } else {
                                    $workerPhone = $call->isIncoming()
                                        ? $call->provider_phone_to
                                        : $call->provider_phone_from;
                                    $worker = Html::encode($workerPhone);
                                }

                                $client = $this->renderClientPrimary($call);

                                $workerPhone = $call->provider_user_num ?: ($call->isIncoming()
                                    ? $call->provider_phone_to
                                    : $call->provider_phone_from);

                                $workerMeta = array_filter([
                                    $workerPhone,
                                    $call->provider ? $call->provider->name : null,
                                ]);

                                $worker = Html::tag(
                                    'div',
                                    $worker.Html::tag('div', Html::encode(implode(' · ', $workerMeta)), [
                                        'class' => 'sx-collection-cell__secondary',
                                    ]),
                                    ['class' => 'sx-call-party__worker sx-collection-cell sx-collection-cell--stack']
                                );

                                if ($call->company || $call->user) {
                                    $client .= Html::tag('div', Html::encode($call->client_phone), [
                                        'class' => 'sx-collection-cell__secondary',
                                    ]);
                                }
                                $client = Html::tag(
                                    'div',
                                    $client,
                                    ['class' => 'sx-call-party__client sx-collection-cell sx-collection-cell--stack']
                                );

                                $arrow = $call->isIncoming() ? '←' : '→';
                                $direction = $call->isIncoming() ? 'Входящий' : 'Исходящий';
                                $arrow = Html::tag('span', $arrow, [
                                    'title'      => $direction,
                                    'aria-label' => $direction,
                                    'class'      => 'sx-call-party__direction',
                                ]);

                                return Html::tag('div', $worker.$arrow.$client, [
                                    'class' => 'sx-call-party',
                                ]);
                            },
                        ],

                        'created_at' => [
                            'class' => DateTimeColumnData::class,
                        ],

                        'duration' => [
                            'headerOptions' => [
                                'class' => 'sx-call-recording-column',
                            ],
                            'contentOptions' => [
                                'class' => 'sx-call-recording-column',
                            ],
                            'format'        => 'raw',
                            'value'         => function (CmsTelephonyCall $call) {
                                if ($call->status !== CmsTelephonyCall::STATUS_ANSWERED) {
                                    $statusClass = $call->status === CmsTelephonyCall::STATUS_FAILED
                                        ? 'sx-status--danger'
                                        : 'sx-status--warning';
                                    return Html::tag('span', Html::encode($call->statusAsText), [
                                        'class' => 'sx-status '.$statusClass,
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

    public function linkFields(): array
    {
        return [
            'links.cms_lead_id' => $this->entityLinkField(
                CmsLead::class,
                static function ($word = '') {
                    $query = (new CmsTelephonyCallLinkService())->availableLeadQuery();
                    if ($word !== '') {
                        $query->search($word);
                    }
                    return $query;
                }
            ),
            'links.cms_company_id' => $this->entityLinkField(
                CmsCompany::class,
                static function ($word = '') {
                    $query = (new CmsTelephonyCallLinkService())->availableCompanyQuery();
                    if ($word !== '') {
                        $query->search($word);
                    }
                    return $query;
                }
            ),
            'links.cms_user_id' => $this->entityLinkField(
                CmsUser::class,
                static function ($word = '') {
                    $query = (new CmsTelephonyCallLinkService())->availableUserQuery();
                    if ($word !== '') {
                        $query->search($word);
                    }
                    return $query;
                }
            ),
        ];
    }

    private function entityLinkField(string $modelClass, callable $searchQuery): array
    {
        return [
            'class' => WidgetField::class,
            'widgetClass' => AjaxSelectModel::class,
            'widgetConfig' => [
                'modelClass' => $modelClass,
                'multiple' => false,
                'searchQuery' => $searchQuery,
            ],
        ];
    }

    private function renderClientPrimary(CmsTelephonyCall $call)
    {
        if ($call->company) {
            return BackendEntityLink::widget([
                'controllerId' => '/cms/admin-cms-company',
                'modelId'      => $call->company->id,
                'content'      => '<i class="fas fa-users"></i> '.Html::encode($call->company->asText),
                'options'      => [
                    'class' => 'sx-collection-cell__primary sx-call-party__link',
                ],
            ]);
        }

        if ($call->user) {
            return BackendEntityLink::widget([
                'controllerId' => '/cms/admin-user',
                'modelId'      => $call->user->id,
                'content'      => '<i class="fas fa-user"></i> '.Html::encode($call->user->asText),
                'options'      => [
                    'class' => 'sx-collection-cell__primary sx-call-party__link',
                ],
            ]);
        }

        return Html::tag('div', Html::encode($call->client_phone), [
            'class' => 'sx-collection-cell__primary',
        ]);
    }

    private function renderClientIdentity(CmsTelephonyCall $call)
    {
        $content = $this->renderClientPrimary($call);
        if (($call->company || $call->user) && $call->client_phone) {
            $content .= Html::tag('div', Html::encode($call->client_phone), [
                'class' => 'sx-collection-cell__secondary',
            ]);
        }

        return Html::tag('div', $content, [
            'class' => 'sx-collection-cell sx-collection-cell--stack',
        ]);
    }

    private function renderWorkerIdentity(CmsTelephonyCall $call, $phone)
    {
        if ($call->workerUser) {
            $content = CmsWorkerViewWidget::widget([
                'user'    => $call->workerUser,
                'isSmall' => true,
            ]);

            if ($phone) {
                $content .= Html::tag('div', Html::encode($phone), [
                    'class' => 'sx-collection-cell__secondary',
                ]);
            }
        } else {
            $content = Html::tag('div', Html::encode($phone), [
                'class' => 'sx-collection-cell__primary',
            ]);
        }

        return Html::tag('div', $content, [
            'class' => 'sx-collection-cell sx-collection-cell--stack',
        ]);
    }
}

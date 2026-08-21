<?php

namespace skeeks\cms\controllers;

use skeeks\cms\assets\admin\LeadProcessAsset;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\BackendEntityLinkColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\UserColumnData;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsUser;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\services\CmsLeadIdentityService;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\admin\CmsLeadStatusWidget;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\helpers\UnsetArrayValue;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;

class AdminCmsLeadController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Лиды';
        $this->modelShowAttribute = 'name';
        $this->modelClassName = CmsLead::class;
        $this->modelDefaultAction = 'view';
        $this->permissionName = 'cms/admin-lead';
        $this->generateAccessActions = false;
        $this->modelHeader = function () {
            return $this->renderPartial('@skeeks/cms/views/admin-cms-lead/_model_header', [
                'model' => $this->model,
            ]);
        };
        parent::init();
    }

    public function actions()
    {
        $utmAttributes = array_keys(CmsLead::utmLabels());

        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'name' => 'Лиды',
                'configKey' => 'cms/leads-v1',
                'emptyState' => [
                    'title' => 'Лидов пока нет',
                    'description' => 'Лиды появятся из форм, партнёрской программы, телефонии или после ручного добавления.',
                    'icon' => 'fas fa-user-plus',
                    'action' => ['backendAction' => 'create', 'label' => 'Добавить лид'],
                ],
                'filters' => [
                    'visibleFilters' => array_merge(['q', 'status', 'source_type'], $utmAttributes),
                    'filtersModel' => [
                        'rules' => [[array_merge(['q'], $utmAttributes), 'safe']],
                        'attributeDefines' => array_merge(['q'], $utmAttributes),
                        'fields' => ArrayHelper::merge([
                            'q' => [
                                'label' => 'Поиск',
                                'elementOptions' => ['placeholder' => 'Название, телефон, email или описание'],
                                'on apply' => static function (QueryFiltersEvent $event) {
                                    $value = trim((string)$event->field->value);
                                    if ($value === '') { return; }
                                    $event->dataProvider->query->search($value);
                                },
                            ],
                            'status' => $this->selectFilter(CmsLead::statuses()),
                            'source_type' => $this->selectFilter(CmsLead::sources()),
                        ], $this->utmFilters()),
                    ],
                ],
                'grid' => [
                    'on init' => static function (Event $event) {
                        $event->sender->dataProvider->query->forManager()->cmsSite();
                    },
                    'defaultPageSize' => 50,
                    'defaultOrder' => ['created_at' => SORT_DESC],
                    'visibleColumns' => ['actions', 'name', 'status', 'source_type', 'contacts', 'executor_id', 'partner_id', 'created_at'],
                    'columns' => [
                        'name' => [
                            'class' => BackendEntityLinkColumn::class,
                            'controllerId' => '/cms/admin-cms-lead',
                            'action' => 'view',
                            'attribute' => 'name',
                        ],
                        'status' => [
                            'format' => 'raw',
                            'value' => static fn(CmsLead $model) => CmsLeadStatusWidget::widget(['lead' => $model]),
                        ],
                        'source_type' => ['value' => static fn(CmsLead $model) => $model->sourceNameAsText],
                        'contacts' => [
                            'label' => 'Контакты',
                            'format' => 'raw',
                            'value' => static function (CmsLead $model) {
                                $parts = [];
                                foreach ($model->phones as $phone) {
                                    $parts[] = Html::a(Html::encode($phone->value), 'tel:'.preg_replace('/[^\d+]/', '', $phone->value));
                                }
                                foreach ($model->emails as $email) {
                                    $parts[] = Html::a(Html::encode($email->value), 'mailto:'.$email->value);
                                }
                                return $parts ? implode('<br>', $parts) : '—';
                            },
                        ],
                        'executor_id' => ['class' => UserColumnData::class, 'label' => 'Ответственный'],
                        'partner_id' => ['class' => UserColumnData::class, 'label' => 'Партнёр'],
                        'created_at' => ['class' => DateTimeColumnData::class, 'label' => 'Добавлен'],
                    ],
                ],
            ],
            'create' => [
                'name' => 'Новый лид',
                'fields' => [$this, 'editFields'],
                'on beforeValidate' => static function (Event $event) {
                    /** @var CmsLead $model */
                    $model = $event->sender->model;
                    $model->cms_site_id = (int)\Yii::$app->skeeks->site->id;
                    foreach (['submitted_by_id', 'cms_company_id', 'cms_user_id', 'source_ref', 'source_url', 'source_data',
                        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'processed_at',
                        'reject_reason', 'result_comment'] as $attribute) {
                        $model->{$attribute} = null;
                    }
                    $model->status = $model->executor_id
                        ? CmsLead::STATUS_IN_WORK
                        : CmsLead::STATUS_NEW;
                },
            ],
            'view' => [
                'class' => BackendModelAction::class,
                'name' => 'Карточка лида',
                'icon' => 'fa fa-eye',
                'priority' => 10,
                'callback' => fn() => $this->render('view', ['model' => $this->model]),
            ],
            'send-sms' => [
                'class' => BackendModelAction::class,
                'isVisible' => false,
                'accessCallback' => fn() => $this->canWorkWithModel(),
                'callback' => [$this, 'sendSms'],
            ],
            'process' => [
                'class' => BackendModelUpdateAction::class,
                'name' => 'Обработка',
                'icon' => 'fa fa-edit',
                'priority' => 20,
                'fields' => [$this, 'processFields'],
                'accessCallback' => fn() => $this->canWorkWithModel(),
                'on beforeValidate' => function (Event $event) {
                    $model = $event->sender->model;
                    $this->restoreAttributes($model, [
                        'cms_site_id', 'submitted_by_id', 'partner_id', 'executor_id', 'cms_company_id', 'cms_user_id',
                        'source_type', 'source_ref', 'source_name', 'source_url', 'source_data', 'name', 'description',
                        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                    ]);
                    if (in_array($model->status, [CmsLead::STATUS_SUCCESS, CmsLead::STATUS_REJECTED], true)) {
                        $model->processed_at = time();
                    }
                },
            ],
            'edit' => [
                'class' => BackendModelUpdateAction::class,
                'name' => 'Редактирование',
                'icon' => 'fa fa-pencil',
                'priority' => 30,
                'fields' => [$this, 'editFields'],
                'accessCallback' => fn() => $this->canWorkWithModel(),
                'on beforeValidate' => function (Event $event) {
                    $protected = [
                        'cms_site_id', 'submitted_by_id', 'cms_company_id', 'cms_user_id', 'source_ref', 'source_url',
                        'source_data', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                        'status', 'reject_reason', 'result_comment', 'processed_at',
                    ];
                    if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
                        $protected[] = 'executor_id';
                    }
                    $this->restoreAttributes($event->sender->model, $protected);
                },
            ],
            'log' => [
                'class' => BackendModelLogAction::class,
                'name' => 'Вся активность',
                'priority' => 40,
            ],
            'claim' => [
                'class' => BackendModelAction::class,
                'name' => 'Взять в работу',
                'isVisible' => false,
                'callback' => [$this, 'claim'],
                'accessCallback' => fn() => $this->model && $this->model->canBeClaimed,
            ],
            'add-comment' => [
                'class' => BackendModelAction::class,
                'isVisible' => false,
                'callback' => [$this, 'addComment'],
                'accessCallback' => fn() => $this->canWorkWithModel(),
            ],
            'matches' => [
                'class' => BackendModelAction::class,
                'isVisible' => false,
                'callback' => [$this, 'matches'],
            ],
            'link-identity' => [
                'class' => BackendModelAction::class,
                'isVisible' => false,
                'callback' => [$this, 'linkIdentity'],
                'accessCallback' => fn() => $this->canWorkWithModel(),
            ],
            'delete' => new UnsetArrayValue(),
            'delete-multi' => new UnsetArrayValue(),
            'update' => new UnsetArrayValue(),
        ]);
    }

    private function selectFilter(array $items): array
    {
        return [
            'defaultMode' => FilterModeEq::ID,
            'isAllowChangeMode' => false,
            'field' => ['class' => SelectField::class, 'items' => $items, 'multiple' => true],
        ];
    }

    private function utmFilters(): array
    {
        $filters = [];
        foreach (CmsLead::utmLabels() as $attribute => $label) {
            $filters[$attribute] = [
                'label' => $label,
                'defaultMode' => FilterModeEq::ID,
                'isAllowChangeMode' => false,
            ];
        }

        return $filters;
    }

    public function getModel()
    {
        if ($this->_model === null) {
            $pk = \Yii::$app->request->get($this->requestPkParamName);
            if ($pk) {
                $this->_model = CmsLead::find()
                    ->forManager()
                    ->cmsSite()
                    ->andWhere([$this->modelPkAttribute => $pk])
                    ->limit(1)
                    ->one();
                if (!$this->_model) {
                    throw new NotFoundHttpException('Лид не найден.');
                }
            }
        }

        return $this->_model;
    }

    public function claim()
    {
        if (!\Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Лид можно взять в работу только кнопкой из карточки.');
        }
        try {
            if (!$this->model->canBeClaimed) {
                throw new ConflictHttpException('Лид уже закреплён. Обновите карточку.');
            }
            $this->model->executor_id = (int)\Yii::$app->user->id;
            $this->model->status = CmsLead::STATUS_IN_WORK;
            if (!$this->model->save()) {
                throw new \RuntimeException(implode('; ', $this->model->getFirstErrors()));
            }
        } catch (StaleObjectException $e) {
            throw new ConflictHttpException('Лид уже закреплён другим менеджером.', 0, $e);
        }
        return $this->redirect(['view', 'pk' => $this->model->id]);
    }

    public function addComment()
    {
        $response = new RequestResponse();
        try {
            if (!$response->isRequestAjaxPost()) { return $response; }
            $log = new CmsLog();
            if (!$log->load(\Yii::$app->request->post())) {
                throw new \RuntimeException('Не удалось прочитать комментарий.');
            }
            $log->log_type = CmsLog::LOG_TYPE_COMMENT;
            $log->model_code = $this->model->skeeksModelCode;
            $log->model_id = $this->model->id;
            $log->model_as_text = $this->model->asText;
            $log->comment = HtmlPurifier::process((string)$log->comment);
            if (trim(html_entity_decode(strip_tags($log->comment), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') {
                throw new \RuntimeException('Напишите текст комментария.');
            }
            $log->is_pinned = 0;
            if (!$log->save()) { throw new \RuntimeException(implode('; ', $log->getFirstErrors())); }
            $this->model->notifyPartnerAboutComment((int)$log->id);
            $response->success = true;
            $response->message = 'Комментарий отправлен';
        } catch (\Throwable $e) {
            $response->success = false;
            $response->message = $e->getMessage();
        }
        return $response;
    }

    public function matches()
    {
        if (!\Yii::$app->request->isAjax) {
            throw new BadRequestHttpException('Совпадения загружаются из карточки лида.');
        }
        if ($this->model->cms_company_id && $this->model->cms_user_id) {
            return '';
        }

        $matches = (new CmsLeadIdentityService())->findMatches($this->model);
        if (!$matches['companies'] && !$matches['clients']) {
            return '';
        }

        return $this->renderPartial('@skeeks/cms/views/admin-cms-lead/_matches', [
            'model' => $this->model,
            'matches' => $matches,
            'canWork' => $this->canWorkWithModel(),
        ]);
    }

    public function sendSms()
    {
        $response = new RequestResponse();
        try {
            if (!$response->isRequestAjaxPost()) {
                throw new \DomainException('SMS отправляется только из карточки лида.');
            }
            $phone = trim((string)\Yii::$app->request->post('phone'));
            $message = trim((string)\Yii::$app->request->post('message'));
            if ($phone === '' || !$this->model->getPhones()->andWhere(['value' => $phone])->exists()) {
                throw new \DomainException('Телефон не принадлежит этому лиду.');
            }
            if ($message === '') {
                throw new \DomainException('Не указано сообщение.');
            }
            if (!\Yii::$app->cms->smsProvider) {
                throw new \DomainException('На сайте не настроена SMS-отправка.');
            }
            $sms = \Yii::$app->cms->smsProvider->send($phone, $message);
            if ($sms->isError) {
                throw new \RuntimeException($sms->error_message);
            }
            $response->success = true;
            $response->message = 'Сообщение отправлено';
        } catch (\Throwable $e) {
            $response->success = false;
            $response->message = $e->getMessage();
        }
        return $response;
    }

    public function linkIdentity()
    {
        if (!\Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Привязка выполняется только явным действием из карточки лида.');
        }

        try {
            $changed = (new CmsLeadIdentityService())->linkExisting(
                $this->model,
                (int)\Yii::$app->request->post('company_id'),
                (int)\Yii::$app->request->post('client_id')
            );
            \Yii::$app->session->setFlash('success', $changed
                ? 'Существующие записи CRM привязаны к лиду.'
                : 'Эти записи уже привязаны к лиду.');
        } catch (StaleObjectException $e) {
            throw new ConflictHttpException('Лид изменился в другой вкладке. Обновите карточку.', 0, $e);
        } catch (\DomainException $e) {
            throw new BadRequestHttpException($e->getMessage(), 0, $e);
        }

        return $this->redirect(['view', 'pk' => $this->model->id]);
    }

    public function editFields($action = null): array
    {
        /** @var CmsLead|null $model */
        $model = $action ? $action->model : null;
        if ($model && $model->isNewRecord && !$model->executor_id && !\Yii::$app->user->isGuest) {
            $model->executor_id = (int)\Yii::$app->user->id;
        }

        $fields = [
            'name',
            'source_type' => ['class' => SelectField::class, 'items' => CmsLead::sources(), 'allowNull' => false],
            'source_name',
        ];

        $isNewRecord = $model && $model->isNewRecord;
        if ($isNewRecord || \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            $fields['executor_id'] = $this->managerField();
        }
        $fields['partner_id'] = $this->partnerField();
        $fields['description'] = ['class' => TextareaField::class, 'elementOptions' => ['rows' => 8]];

        return [
            'lead' => [
                'class' => FieldSet::class,
                'name' => 'Данные лида',
                'fields' => $fields,
            ],
        ];
    }

    public function processFields($action = null): array
    {
        LeadProcessAsset::register(\Yii::$app->view);
        $fields = [
            'status' => [
                'class' => SelectField::class,
                'items' => $this->model ? array_intersect_key(CmsLead::statuses(), array_flip($this->model->allowedNextStatuses())) : CmsLead::statuses(),
                'allowNull' => false,
                'elementOptions' => ['data-sx-lead-status' => '1'],
            ],
        ];
        if ($this->model && $this->model->partner_id) {
            $fields['partner_reward_value'] = [
                'class' => NumberField::class,
                'step' => 0.01,
                'elementOptions' => ['min' => 0.01],
                'hint' => 'Обязательно при успешном завершении. Бонусы начисляются атомарно при сохранении.',
                'options' => $this->statusFieldOptions([CmsLead::STATUS_SUCCESS]),
            ];
        }
        $fields['result_comment'] = [
            'class' => TextareaField::class,
            'label' => 'Результат обработки',
            'elementOptions' => ['rows' => 5],
            'options' => $this->statusFieldOptions([CmsLead::STATUS_SUCCESS]),
        ];
        $fields['reject_reason'] = [
            'class' => TextareaField::class,
            'label' => 'Причина отклонения',
            'elementOptions' => ['rows' => 5],
            'options' => $this->statusFieldOptions([CmsLead::STATUS_REJECTED]),
        ];

        return [
            'work' => [
                'class' => FieldSet::class,
                'name' => 'Работа с лидом',
                'fields' => $fields,
            ],
        ];
    }

    private function statusFieldOptions(array $statuses): array
    {
        return ['options' => [
            'class' => 'form-group',
            'data-sx-lead-statuses' => implode(' ', $statuses),
            'hidden' => !in_array($this->model->status, $statuses, true),
        ]];
    }

    private function managerField(): array
    {
        return [
            'class' => WidgetField::class,
            'label' => 'Ответственный сотрудник',
            'widgetClass' => AjaxSelectModel::class,
            'widgetConfig' => [
                'modelClass' => CmsUser::class,
                'searchQuery' => static function ($word = '') {
                    $query = CmsUser::find()->forManager()->cmsSite();
                    return $word ? $query->search($word) : $query;
                },
            ],
        ];
    }

    private function partnerField(): array
    {
        return [
            'class' => WidgetField::class,
            'label' => 'Партнёр',
            'widgetClass' => AjaxSelectModel::class,
            'widgetConfig' => [
                'modelClass' => CmsUser::class,
                'searchQuery' => static function ($word = '') {
                    $query = CmsUser::find()->forManager()->cmsSite();
                    return $word ? $query->search($word) : $query;
                },
            ],
        ];
    }

    private function canWorkWithModel(): bool
    {
        if (!$this->model || $this->model->isTerminal) { return false; }
        if (\Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) { return true; }
        return $this->model->isManagedBy((int)\Yii::$app->user->id);
    }

    private function restoreAttributes(CmsLead $model, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $model->{$attribute} = $model->getOldAttribute($attribute);
        }
    }
}

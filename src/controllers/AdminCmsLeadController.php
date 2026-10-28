<?php

namespace skeeks\cms\controllers;

use skeeks\cms\assets\admin\LeadProcessAsset;
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\BackendEntityLinkColumn;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\crm\LeadPipelines;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadRejectReason;
use skeeks\cms\models\CmsLeadStage;
use yii\helpers\Url;
use skeeks\cms\services\CmsLeadBoardService;
use skeeks\cms\forms\CmsLeadNoAnswerForm;
use skeeks\cms\models\queries\CmsLeadQuery;
use skeeks\cms\services\CmsLeadNoAnswerService;
use kartik\datecontrol\DateControl;
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
    use \skeeks\cms\controllers\traits\LeadTriageActions;

    /** The section opens on the kanban; the table stays one tab away. */
    public $defaultAction = 'kanban';

    public function init()
    {
        $this->name = 'Сделки';
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
                'name' => 'Таблица',
                'priority' => 20,
                // v2: the "next step" column and filter are part of the defaults.
                'configKey' => 'cms/leads-v2',
                'emptyState' => [
                    'title' => 'Сделок пока нет',
                    'description' => 'Сделки появятся из форм, партнёрской программы, телефонии или после ручного добавления.',
                    'icon' => 'fas fa-user-plus',
                    'action' => ['backendAction' => 'create', 'label' => 'Добавить сделку'],
                ],
                'filters' => [
                    // «Воронка» only on a site with several pipelines (a simple site sees no new filter).
                    'visibleFilters' => array_merge(['q', 'status'], count(CmsLeadPipeline::forSite()) > 1 ? ['cms_lead_pipeline_id'] : [],
                        ['cms_lead_stage_id', 'cms_lead_reject_reason_id', 'next_step', 'source_type'], $utmAttributes),
                    'filtersModel' => [
                        'rules' => [[array_merge(['q', 'next_step'], $utmAttributes), 'safe']],
                        'attributeDefines' => array_merge(['q', 'next_step'], $utmAttributes),
                        'attributeLabels' => ['next_step' => 'Следующий шаг'],
                        'fields' => ArrayHelper::merge([
                            'next_step' => [
                                'label' => 'Следующий шаг',
                                'class' => SelectField::class,
                                'items' => CmsLeadQuery::nextStepFilters(),
                                'on apply' => static function (QueryFiltersEvent $event) {
                                    $value = (string)$event->field->value;
                                    if (!array_key_exists($value, CmsLeadQuery::nextStepFilters())) { return; }
                                    $event->dataProvider->query->nextStep($value);
                                },
                            ],
                            'q' => [
                                'label' => 'Поиск',
                                'elementOptions' => ['placeholder' => 'Название, телефон, email или описание'],
                                'on apply' => static function (QueryFiltersEvent $event) {
                                    $value = trim((string)$event->field->value);
                                    if ($value === '') { return; }
                                    $event->dataProvider->query->search($value);
                                },
                            ],
                            'status' => $this->selectFilter(CmsLead::staffStatuses()),
                            'cms_lead_pipeline_id' => $this->selectFilter(ArrayHelper::map(CmsLeadPipeline::forSite(), 'id', 'name')),
                            'cms_lead_stage_id' => $this->selectFilter($this->stageItems()),
                            'cms_lead_reject_reason_id' => $this->selectFilter($this->rejectReasonItems()),
                            'source_type' => $this->selectFilter(CmsLead::sources()),
                        ], $this->utmFilters()),
                    ],
                ],
                'grid' => [
                    'on init' => static function (Event $event) {
                        $event->sender->dataProvider->query->forManager()->cmsSite()->with(['activeTasks', 'stage', 'rejectReasonItem']);
                    },
                    'defaultPageSize' => 50,
                    'defaultOrder' => ['created_at' => SORT_DESC],
                    'visibleColumns' => ['actions', 'name', 'status', 'shown_amount', 'next_step', 'source_type', 'contacts', 'executor_id', 'partner_id', 'created_at'],
                    'columns' => [
                        // One amount at a time: «Сумма» of an open or rejected deal, «Сумма сделки» of a successful one.
                        'shown_amount' => [
                            'label' => 'Сумма',
                            'format' => 'raw',
                            'value' => static function (CmsLead $model) {
                                [$label, $text] = $model->shownAmount;
                                if ($text === '') {
                                    return '';
                                }
                                $note = $model->expectedAmountNote;

                                return Html::tag('span', Html::encode($text), ['title' => $label.($note !== '' ? ', '.$note : '')])
                                    .($note !== '' ? Html::tag('div', Html::encode($note), ['class' => 'sx-collection-cell__secondary']) : '');
                            },
                        ],
                        'name' => [
                            'class' => BackendEntityLinkColumn::class,
                            'controllerId' => '/cms/admin-cms-lead',
                            'action' => 'view',
                            'attribute' => 'name',
                            'value' => static fn(CmsLead $model) => $model->displayName,
                        ],
                        'status' => [
                            'format' => 'raw',
                            'value' => static fn(CmsLead $model) => CmsLeadStatusWidget::widget(['lead' => $model, 'showStage' => true]),
                        ],
                        'next_step' => [
                            'label' => 'Следующий шаг',
                            'format' => 'raw',
                            'value' => static fn(CmsLead $model) => self::renderNextStepCell($model),
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
            'kanban' => [
                'class' => ViewBackendAction::class,
                'name' => 'Канбан',
                'icon' => 'fas fa-columns',
                'priority' => 10,
                'callback' => [$this, 'kanban'],
            ],
            'report' => [
                'class' => ViewBackendAction::class,
                'name' => 'Отчёт',
                'icon' => 'fa fa-chart-bar',
                'priority' => 25,
                'callback' => [$this, 'report'],
            ],
            'kanban-move' => [
                'class' => ViewBackendAction::class,
                'isVisible' => false,
                'callback' => [$this, 'kanbanMove'],
            ],
            'move-pipeline' => [
                'class' => BackendModelAction::class,
                'name' => 'Перенести в другую воронку',
                'isVisible' => false,
                'callback' => [$this, 'movePipeline'],
                'accessCallback' => fn() => $this->model && !$this->model->isTerminal && count(CmsLeadPipeline::forSite()) > 1
                    && ($this->canWorkWithModel() || ($this->model->status === CmsLead::STATUS_NEW
                        && \skeeks\cms\crm\CrmAccess::instance()->canTriage((int)\Yii::$app->user->id, $this->model))),
            ],
            'stage-history' => [
                'class' => BackendModelAction::class,
                'name' => 'История этапов',
                'icon' => 'fas fa-stream',
                'priority' => 38,
                'isVisible' => CmsLead::pipelineReady(),
                'callback' => fn() => $this->render('stage-history', ['model' => $this->model]),
            ],
            'view' => [
                'class' => BackendModelAction::class,
                'name' => 'Карточка сделки',
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
                // Not a tab: the stage bar of the card does it; «Успешно» / «Отказ» and the kanban open this form.
                'isVisible' => false,
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
                'icon' => 'fa fa-edit',
                'priority' => 30,
                'fields' => [$this, 'editFields'],
                'accessCallback' => fn() => $this->canWorkWithModel(),
                'on beforeValidate' => function (Event $event) {
                    $protected = [
                        'cms_site_id', 'submitted_by_id', 'cms_company_id', 'cms_user_id', 'source_ref', 'source_url',
                        'source_data', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                        'status', 'reject_reason', 'result_comment', 'processed_at', 'cms_lead_stage_id', 'cms_lead_reject_reason_id',
                    ];
                    if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
                        $protected[] = 'executor_id';
                    }
                    $this->restoreAttributes($event->sender->model, $protected);
                },
            ],
            'no-answer' => [
                'class' => BackendModelUpdateAction::class,
                'name' => 'Не дозвонились',
                'icon' => 'fas fa-phone-slash',
                'isVisible' => false,
                'size' => BackendModelUpdateAction::SIZE_SMALL,
                'buttons' => ['save'],
                'successMessage' => 'Звонок отмечен',
                'fields' => [$this, 'noAnswerFields'],
                'accessCallback' => fn() => $this->canWorkWithModel() && $this->model->status === CmsLead::STATUS_IN_WORK,
                'on initFormModels' => function (Event $event) {
                    /** @var BackendModelUpdateAction $action */
                    $action = $event->sender;
                    // Only the form is validated: the call outcome must not
                    // depend on unrelated lead fields.
                    $action->formModels = ['noAnswer' => new CmsLeadNoAnswerForm(['lead' => $this->model])];
                },
                'on beforeSave' => function (Event $event) {
                    /** @var BackendModelUpdateAction $action */
                    $action = $event->sender;
                    $action->isSaveFormModels = false;
                    (new CmsLeadNoAnswerService())->apply($this->model, $action->formModels['noAnswer']);
                },
            ],
            'deal-amount' => [
                'class' => BackendModelUpdateAction::class,
                'name' => 'Сумма сделки',
                'isVisible' => false,
                'size' => BackendModelUpdateAction::SIZE_SMALL,
                'buttons' => ['save'],
                'successMessage' => 'Сумма сделки сохранена',
                'fields' => static fn() => [
                    'amount.deal_amount' => [
                        'class' => NumberField::class,
                        'step' => 0.01,
                        'elementOptions' => ['min' => 0.01],
                        'hint' => 'Разовая сумма, на которую договорились с клиентом. Статус сделки и начисление партнёру не меняются.',
                    ],
                ],
                'accessCallback' => fn() => $this->model
                    && $this->model->status === CmsLead::STATUS_SUCCESS
                    && $this->model->canBeReopenedBy((int)\Yii::$app->user->id),
                'on initFormModels' => function (Event $event) {
                    /** @var BackendModelUpdateAction $action */
                    $action = $event->sender;
                    // Only the amount is edited; the rest of the closed lead stays as it is.
                    $form = new \yii\base\DynamicModel(['deal_amount' => $this->model->deal_amount]);
                    $form->setAttributeLabels(['deal_amount' => 'Сумма сделки, ₽']);
                    $form->addRule(['deal_amount'], 'filter', ['filter' => static function ($value) {
                        return is_string($value) ? (trim(str_replace([' ', ','], ['', '.'], $value)) ?: null) : $value;
                    }]);
                    $form->addRule(['deal_amount'], 'required', ['message' => 'Укажите сумму сделки']);
                    $form->addRule(['deal_amount'], 'number', ['min' => 0.01]);
                    $action->formModels = ['amount' => $form];
                },
                'on beforeSave' => function (Event $event) {
                    /** @var BackendModelUpdateAction $action */
                    $action = $event->sender;
                    $action->isSaveFormModels = false;
                    $this->model->deal_amount = $action->formModels['amount']->deal_amount;
                    if (!$this->model->save()) {
                        throw new \RuntimeException(implode(' ', $this->model->getFirstErrors()));
                    }
                },
            ],
            'tasks' => [
                'class' => BackendGridModelRelatedAction::class,
                'name' => 'Задачи',
                'icon' => 'fas fa-list',
                'priority' => 35,
                'controllerRoute' => '/cms/admin-cms-task',
                'relation' => ['cms_lead_id' => 'id'],
                'on gridInit' => function ($e) {
                    $e->sender->relatedIndexAction->backendShowings = false;
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
            'reopen' => [
                'class' => BackendModelAction::class,
                'name' => 'Вернуть в работу',
                'isVisible' => false,
                'callback' => [$this, 'reopen'],
                'accessCallback' => fn() => $this->model
                    && $this->model->canBeReopenedBy((int)\Yii::$app->user->id),
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
                'accessCallback' => fn() => $this->canLinkIdentity(),
            ],
            'delete' => [
                'accessCallback' => fn() => $this->canDeleteLead(),
            ],
            'delete-multi' => new UnsetArrayValue(),
            'update' => new UnsetArrayValue(),
            // The standard create form is replaced by «найти или создать» (LeadTriageActions::createLead).
            'create' => new UnsetArrayValue(),
        ], $this->triageActions());
    }

    /** Compact next-step cell: nearest open task, overdue or missing state. */
    public static function renderNextStepCell(CmsLead $model): string
    {
        $task = $model->nextTask;
        if (!$task) {
            return $model->isNextStepMissing
                ? Html::tag('span', 'Не запланирован', ['class' => 'sx-status sx-status--warning'])
                : '—';
        }

        $secondary = $task->plan_start_at
            ? \Yii::$app->formatter->asDatetime($task->plan_start_at, 'd MMM, HH:mm')
            : 'Без срока';
        if ($model->isNextStepOverdue) {
            $secondary = Html::tag('span', 'Просрочен', ['class' => 'sx-status sx-status--danger']).' '.$secondary;
        }

        return Html::tag('div',
            Html::tag('span', \skeeks\cms\backend\widgets\BackendEntityLink::widget([
                'controllerId' => '/cms/admin-cms-task',
                'modelId' => $task->id,
                'label' => $task->name,
            ]), ['class' => 'sx-collection-cell__primary'])
            .Html::tag('span', $secondary, ['class' => 'sx-collection-cell__secondary']),
            ['class' => 'sx-collection-cell sx-collection-cell--stack']
        );
    }

    public function noAnswerFields(BackendModelUpdateAction $action): array
    {
        /** @var CmsLeadNoAnswerForm $form */
        $form = $action->formModels['noAnswer'];
        $attempt = (int)$this->model->no_answer_count + 1;
        $nextTask = $this->model->getActiveTasks()
            ->andWhere([\skeeks\cms\models\CmsTask::tableName().'.executor_id' => (int)\Yii::$app->user->id])
            ->limit(1)
            ->one();
        $taskHint = $nextTask
            // Field hints are rendered as HTML.
            ? 'При переносе звонка ваша задача «'.Html::encode($nextTask->name).'» сдвинется на выбранное время.'
            : 'При переносе звонка будет поставлена задача «Перезвонить» на выбранное время.';

        $nextId = Html::getInputId($form, 'next');
        $timeFieldClass = 'field-'.Html::getInputId($form, 'callback_at');
        $reasonFieldClass = 'field-'.Html::getInputId($form, 'reject_reason_id');
        $this->view->registerJs(<<<JS
(function() {
    var toggle = function() {
        $('.{$timeFieldClass}').toggle($('#{$nextId}').val() === 'custom');
        $('.{$reasonFieldClass}').toggle($('#{$nextId}').val() === 'reject');
    };
    $('body').off('change.sxLeadNoAnswer').on('change.sxLeadNoAnswer', '#{$nextId}', toggle);
    toggle();
})();
JS
        );

        $fields = [
            'noAnswer.next' => [
                'class' => SelectField::class,
                'items' => CmsLeadNoAnswerForm::nextOptions(),
                'allowNull' => false,
                'hint' => 'Это '.$attempt.'-я попытка дозвониться. '.$taskHint,
            ],
        ];
        $reasonItems = $this->rejectReasonItems();
        if ($reasonItems) {
            $fields['noAnswer.reject_reason_id'] = [
                'class' => SelectField::class,
                'items' => $reasonItems,
            ];
        }

        return $fields + [
            'noAnswer.callback_at' => [
                'class' => WidgetField::class,
                'widgetClass' => DateControl::class,
                'widgetConfig' => [
                    'type' => DateControl::FORMAT_DATETIME,
                    'displayFormat' => 'php:d-m-Y H:i',
                    'saveFormat' => 'php:U',
                    'options' => ['autocomplete' => 'off'],
                    'widgetOptions' => ['pluginOptions' => ['minuteStep' => 5]],
                ],
            ],
            'noAnswer.comment' => [
                'class' => TextareaField::class,
                'elementOptions' => ['rows' => 3, 'placeholder' => 'Необязательно: что удалось выяснить'],
            ],
        ];
    }

    public function kanban()
    {
        $request = \Yii::$app->request;
        $userId = (int)\Yii::$app->user->id;
        $managers = $this->kanbanManagers();

        $executorId = static::boardExecutor($request->get('executor'), $userId, $managers);
        $q = mb_substr(trim((string)$request->get('q')), 0, 100);

        $isAdmin = \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        $siteId = (int)\Yii::$app->skeeks->site->id;

        // П2б: one pipeline at a time, as in amoCRM and Bitrix24; the default one first.
        $pipelines = CmsLeadPipeline::forSite($siteId);
        $pipeline = null;
        $pipelineId = (int)$request->get('pipeline');
        foreach ($pipelines as $item) {
            if ((int)$item->id === $pipelineId) {
                $pipeline = $item;
            }
        }
        $pipeline = $pipeline ?: CmsLeadPipeline::defaultFor($siteId);

        $filters = [
            'source_type' => array_key_exists((string)$request->get('source'), CmsLead::sources()) ? (string)$request->get('source') : '',
            CmsLeadBoardService::FILTER_NO_TASK => (bool)$request->get('no_task'),
        ];

        return $this->render('kanban', [
            'columns' => (new CmsLeadBoardService($pipeline, $filters))->columns($executorId ?: null, $q),
            'pipelines' => $pipelines,
            'pipeline' => $pipeline,
            'filters' => $filters,
            'executorId' => $executorId,
            'q' => $q,
            'managers' => $managers,
            'isAdmin' => $isAdmin,
            'setup' => $isAdmin ? [
                'stages' => (bool)CmsLeadStage::forPipeline($pipeline, $siteId),
                'rejectReasons' => CmsLeadRejectReason::siteHasReasons($siteId),
            ] : null,
        ]);
    }

    /**
     * Whose deals the kanban shows: no `executor` — the viewer («Мои сделки»,
     * the default from the menu; the historical `mine=1` lands here too),
     * `executor=all` or the empty choice of the dropdown — all available
     * (0), an id — that employee. An employee outside the viewer's scope
     * ($managers) is ignored rather than revealing an empty board.
     */
    public static function boardExecutor($executor, int $userId, array $managers): int
    {
        $executorId = $executor === null ? $userId : (int)$executor;

        return $executorId && isset($managers[$executorId]) ? $executorId : 0;
    }

    /**
     * «Перенести в другую воронку» from the card: the pipeline and its stage.
     * GET renders the small window, POST moves (LeadPipelines::move()).
     */
    public function movePipeline()
    {
        $lead = $this->model;
        $userId = (int)\Yii::$app->user->id;
        $pipelines = CmsLeadPipeline::forSite((int)$lead->cms_site_id);
        $error = null;
        if (\Yii::$app->request->isPost) {
            try {
                LeadPipelines::instance()->move($lead,
                    (int)\Yii::$app->request->post('pipeline_id'),
                    (int)\Yii::$app->request->post('stage_id') ?: null,
                    $userId);
                $pipeline = $lead->getEffectivePipeline();
                \Yii::$app->session->setFlash('success', 'Сделка перенесена в воронку «'.($pipeline ? $pipeline->name : '').'».');

                return $this->renderContent(Html::script('window.top.location.href = '.\yii\helpers\Json::htmlEncode(Url::to(['view', 'pk' => $lead->id])).';'));
            } catch (\DomainException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('move-pipeline', [
            'model' => $lead,
            'pipelines' => $pipelines,
            'error' => $error,
        ]);
    }

    /**
     * Employees whose leads the viewer may look at on the kanban, id => name.
     * Administrators see every active employee; a manager sees self and the
     * subordinates whose leads `CmsLeadQuery::forManager()` already exposes.
     */
    private function kanbanManagers(): array
    {
        /** @var CmsUser $user */
        $user = \Yii::$app->user->identity;
        if (!$user) {
            return [];
        }

        if (\Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            $users = CmsUser::find()->cmsSite()->isWorker()
                ->andWhere([CmsUser::tableName().'.is_active' => 1])
                ->all();
        } else {
            $users = $user->subordinates;
        }

        $result = [(int)$user->id => (string)$user->shortDisplayName];
        foreach ($users as $item) {
            $result[(int)$item->id] = (string)$item->shortDisplayName;
        }
        asort($result, SORT_NATURAL | SORT_FLAG_CASE);

        return $result;
    }

    public function report()
    {
        $request = \Yii::$app->request;
        $period = trim((string)$request->get('period'));
        if ($period === '') {
            $period = date('01.m.Y').' - '.date('t.m.Y');
        }
        $from = $this->parseReportDate($period, false);
        $to = $this->parseReportDate($period, true);
        if (!$from || !$to || $from > $to) {
            $period = date('01.m.Y').' - '.date('t.m.Y');
            $from = $this->parseReportDate($period, false);
            $to = $this->parseReportDate($period, true);
        }
        $source = (string)$request->get('source_type');
        if (!array_key_exists($source, CmsLead::sources())) {
            $source = '';
        }
        // П2б: the report of one pipeline, by the history of transitions.
        $pipelines = CmsLeadPipeline::forSite();
        $pipeline = null;
        foreach ($pipelines as $item) {
            if ((int)$item->id === (int)$request->get('pipeline')) {
                $pipeline = $item;
            }
        }
        $pipeline = $pipeline ?: CmsLeadPipeline::defaultFor();
        $managers = $this->kanbanManagers();
        $executorId = (int)$request->get('executor');
        if ($executorId && !isset($managers[$executorId])) {
            $executorId = 0;
        }

        return $this->render('report', [
            'period' => $period,
            'source' => $source,
            'pipelines' => $pipelines,
            'pipeline' => $pipeline,
            'managers' => $managers,
            'executorId' => $executorId,
            'funnel' => (new \skeeks\cms\services\CmsLeadFunnelReportService())->build($pipeline, $from, $to, array_filter([
                'source_type' => $source,
                'executor_id' => $executorId,
            ])),
            'report' => (new \skeeks\cms\services\CmsLeadReportService())->build($from, $to, $source ?: null, $pipeline),
        ]);
    }

    /** Start or end of a "dd.mm.yyyy - dd.mm.yyyy" period in the site time zone. */
    private function parseReportDate(string $period, bool $isEnd): ?int
    {
        $parts = preg_split('/\s+-\s+/', $period);
        $date = trim((string)($parts[$isEnd ? 1 : 0] ?? $parts[0] ?? ''));
        $timeZone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
        foreach (['d.m.Y', 'd/m/Y', 'Y-m-d'] as $format) {
            $dateTime = \DateTime::createFromFormat($format.' H:i:s', $date.' '.($isEnd ? '23:59:59' : '00:00:00'), $timeZone);
            if ($dateTime instanceof \DateTime && $dateTime->format($format) === $date) {
                return $dateTime->getTimestamp();
            }
        }

        return null;
    }

    public function kanbanMove()
    {
        $response = new RequestResponse();
        if (!$response->isRequestAjaxPost()) {
            throw new BadRequestHttpException('Сделка переносится только на канбане.');
        }

        try {
            $lead = CmsLead::find()->forManager()->cmsSite()
                ->andWhere([CmsLead::tableName().'.id' => (int)\Yii::$app->request->post('pk')])
                ->one();
            if (!$lead) {
                throw new \RuntimeException('Сделка не найдена или недоступна вам.');
            }

            $column = (string)\Yii::$app->request->post('column');
            if ($lead->status === CmsLead::STATUS_NEW && strpos($column, CmsLeadBoardService::STAGE_PREFIX) === 0
                && $this->needsSorting($lead)) {
                // A deal with a known client or an open deal of it is sorted first: a repeat, support or spam.
                if (!\skeeks\cms\crm\CrmAccess::instance()->canTriage((int)\Yii::$app->user->id, $lead)) {
                    throw new \RuntimeException('Сделка уже закреплена за другим менеджером.');
                }
                $response->data = ['openUrl' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                    '/cms/admin-cms-lead/triage',
                    'pk' => $lead->id,
                    'stage_id' => (int)substr($column, strlen(CmsLeadBoardService::STAGE_PREFIX)) ?: null,
                ])->enableEmptyLayout()->enableNoActions()->url];
                $response->success = true;

                return $response;
            }
            if (CmsLeadBoardService::forLead($lead)->move($lead, $column, (int)\Yii::$app->user->id)) {
                $lead = CmsLead::find()->with(['activeTasks', 'stage', 'executor', 'rejectReasonItem'])->andWhere(['id' => $lead->id])->one();
                $response->data = ['card' => $this->renderPartial('_kanban-card', ['model' => $lead])];
            } else {
                $status = $column === CmsLeadBoardService::COLUMN_SUCCESS ? CmsLead::STATUS_SUCCESS : CmsLead::STATUS_REJECTED;
                $response->data = ['openUrl' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                    '/cms/admin-cms-lead/process',
                    'pk' => $lead->id,
                    'status' => $status,
                ])->enableEmptyLayout()->enableNoActions()->url];
            }
            $response->success = true;
        } catch (\RuntimeException $e) {
            $response->success = false;
            $response->message = $e->getMessage();
        }

        return $response;
    }

    /**
     * Dropping an unsorted deal on a stage takes it into work as before П2а,
     * unless the CRM already knows its client (a strong match): then the
     * sorting window opens. A simple site without CRM data keeps one move.
     */
    private function needsSorting(CmsLead $lead): bool
    {
        $matcher = \skeeks\cms\crm\CrmMatcher::instance();
        foreach ($matcher->rawFind($matcher->inputOfLead($lead)) as $candidate) {
            if ($candidate['strong']) {
                return true;
            }
        }

        return (bool)$lead->cms_company_id || (bool)$lead->cms_user_id;
    }

    /** Reject reason choices of the current site; empty until the table exists. */
    private function rejectReasonItems(): array
    {
        try {
            return ArrayHelper::map(CmsLeadRejectReason::forSite(), 'id', 'name');
        } catch (\yii\db\Exception $e) {
            return [];
        }
    }

    /** Stage choices of the current site (grouped by pipeline when there are several); empty until the stage table exists. */
    private function stageItems(): array
    {
        try {
            return LeadPipelines::instance()->stageItems();
        } catch (\yii\db\Exception $e) {
            return [];
        }
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
        // A related lead grid is initialized inside a company or client
        // controller request, where `pk` belongs to that parent record.
        if (\Yii::$app->controller !== $this) {
            return null;
        }

        if ($this->_model === null) {
            $pk = \Yii::$app->request->get($this->requestPkParamName);
            if ($pk) {
                $this->_model = CmsLead::find()
                    ->forManager()
                    ->cmsSite()
                    ->andWhere([CmsLead::tableName().'.'.$this->modelPkAttribute => $pk])
                    ->limit(1)
                    ->one();
                if (!$this->_model) {
                    throw new NotFoundHttpException('Сделка не найдена.');
                }
            }
        }

        return $this->_model;
    }

    public function claim()
    {
        if (!\Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Сделку можно взять в работу только кнопкой из карточки.');
        }
        try {
            if (!$this->model->canBeClaimed) {
                throw new ConflictHttpException('Сделка уже закреплена. Обновите карточку.');
            }
            $this->model->executor_id = (int)\Yii::$app->user->id;
            $this->model->status = CmsLead::STATUS_IN_WORK;
            if (!$this->model->save()) {
                throw new \RuntimeException(implode('; ', $this->model->getFirstErrors()));
            }
        } catch (StaleObjectException $e) {
            throw new ConflictHttpException('Сделка уже закреплена другим менеджером.', 0, $e);
        }
        return $this->redirect(['view', 'pk' => $this->model->id]);
    }

    public function reopen()
    {
        if (!\Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Вернуть сделку в работу можно только кнопкой из карточки.');
        }
        try {
            if (!$this->model->canBeReopenedBy((int)\Yii::$app->user->id)) {
                throw new ConflictHttpException('Сделка уже возвращена в работу или недоступна вам. Обновите карточку.');
            }
            $this->model->status = CmsLead::STATUS_IN_WORK;
            $this->model->processed_at = null;
            if (!$this->model->save()) {
                throw new \RuntimeException(implode('; ', $this->model->getFirstErrors()));
            }
            \Yii::$app->session->setFlash('success', 'Сделка возвращена в работу.');
        } catch (StaleObjectException $e) {
            throw new ConflictHttpException('Сделка уже изменена в другой вкладке. Обновите карточку.', 0, $e);
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
            throw new BadRequestHttpException('Совпадения загружаются из карточки сделки.');
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
            'canLink' => $this->canLinkIdentity(),
        ]);
    }

    public function sendSms()
    {
        $response = new RequestResponse();
        try {
            if (!$response->isRequestAjaxPost()) {
                throw new \DomainException('SMS отправляется только из карточки сделки.');
            }
            $phone = trim((string)\Yii::$app->request->post('phone'));
            $message = trim((string)\Yii::$app->request->post('message'));
            if ($phone === '' || !$this->model->getPhones()->andWhere(['value' => $phone])->exists()) {
                throw new \DomainException('Телефон не принадлежит этой сделке.');
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
            throw new BadRequestHttpException('Привязка выполняется только явным действием из карточки сделки.');
        }

        try {
            $changed = (new CmsLeadIdentityService())->linkExisting(
                $this->model,
                (int)\Yii::$app->request->post('company_id'),
                (int)\Yii::$app->request->post('client_id')
            );
            \Yii::$app->session->setFlash('success', $changed
                ? 'Существующие записи CRM привязаны к сделке.'
                : 'Эти записи уже привязаны к сделке.');
        } catch (StaleObjectException $e) {
            \Yii::$app->session->setFlash('error', 'Сделка изменилась в другой вкладке. Обновите карточку.');
        } catch (\DomainException $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());
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

        $fields = ['name' => []];
        if (CmsLead::amountReady() && (!$model || $model->status !== CmsLead::STATUS_SUCCESS)) {
            // An open deal shows «Сумма» only; «Сумма сделки» belongs to the success form.
            $fields['amount'] = ['class' => NumberField::class, 'elementOptions' => ['placeholder' => 'Ожидаемая сумма, ₽', 'min' => 0, 'step' => 'any']];
        }
        $fields['source_type'] = ['class' => SelectField::class, 'items' => CmsLead::sources(), 'allowNull' => false];
        $fields['source_name'] = [];

        $isNewRecord = $model && $model->isNewRecord;
        if ($isNewRecord || \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            $fields['executor_id'] = $this->managerField();
        }
        $fields['partner_id'] = $this->partnerField();
        $fields['description'] = ['class' => TextareaField::class, 'elementOptions' => ['rows' => 8]];

        return [
            'lead' => [
                'class' => FieldSet::class,
                'name' => 'Данные сделки',
                'fields' => $fields,
            ],
        ];
    }

    public function processFields($action = null): array
    {
        LeadProcessAsset::register(\Yii::$app->view);
        // The kanban and the stage bar open this form with a target status.
        // Only the control is preselected: the model (and the card header
        // rendered from it) keeps the saved status until the form is saved.
        $statusOptions = ['data-sx-lead-status' => '1'];
        $requestedStatus = (string)\Yii::$app->request->get('status');
        if ($this->model && !\Yii::$app->request->isPost && $requestedStatus !== ''
            && in_array($requestedStatus, $this->model->allowedNextStatuses(), true)) {
            $statusOptions['data-sx-lead-status-preselect'] = $requestedStatus;
        }
        $fields = [
            'status' => [
                'class' => SelectField::class,
                'items' => $this->model ? array_intersect_key(CmsLead::staffStatuses(), array_flip($this->model->allowedNextStatuses())) : CmsLead::staffStatuses(),
                'allowNull' => false,
                'elementOptions' => $statusOptions,
            ],
        ];
        // Only the stages of the deal's own pipeline.
        $stageItems = $this->model ? ArrayHelper::map($this->model->pipelineStages, 'id', 'name') : $this->stageItems();
        if ($stageItems) {
            $fields['cms_lead_stage_id'] = [
                'class' => SelectField::class,
                'items' => $stageItems,
                'nullLabel' => 'Без этапа',
                'options' => $this->statusFieldOptions([CmsLead::STATUS_IN_WORK]),
            ];
        }
        if ($this->model && ($this->model->deal_amount === null || $this->model->deal_amount === '')
            && $this->model->hasAttribute('amount') && $this->model->getAttribute('amount') !== null && !\Yii::$app->request->isPost) {
            // «Сумма сделки» starts from «Сумма»; the manager may correct it (not saved unless the deal succeeds).
            $this->model->deal_amount = $this->model->getAttribute('amount');
        }
        $fields['deal_amount'] = [
            'class' => NumberField::class,
            'step' => 0.01,
            'elementOptions' => ['min' => 0.01, 'data-sx-lead-deal-amount' => '1'],
            'hint' => 'Разовая сумма, на которую договорились с клиентом.',
            'options' => $this->statusFieldOptions([CmsLead::STATUS_SUCCESS]),
        ];
        if ($this->model && $this->model->partner_id) {
            $percentButtons = '';
            foreach ([10, 20, 30] as $percent) {
                $percentButtons .= Html::button($percent.'%', [
                    'class' => 'sx-button sx-button--secondary sx-button--sm',
                    'data-sx-lead-reward-percent' => $percent,
                    'title' => $percent.'% от суммы сделки',
                ]).' ';
            }
            $fields['partner_reward_value'] = [
                'class' => NumberField::class,
                'step' => 0.01,
                'elementOptions' => ['min' => 0.01, 'data-sx-lead-partner-reward' => '1'],
                'hint' => 'Обязательно при успешном завершении. Бонусы начисляются атомарно при сохранении.'
                    .'<div class="sx-lead-reward-percents">От суммы сделки: '.$percentButtons.'</div>',
                'options' => $this->statusFieldOptions([CmsLead::STATUS_SUCCESS]),
            ];
        }
        $fields['result_comment'] = [
            'class' => TextareaField::class,
            'label' => 'Результат обработки',
            'elementOptions' => ['rows' => 5],
            'options' => $this->statusFieldOptions([CmsLead::STATUS_SUCCESS]),
        ];
        $reasonItems = $this->rejectReasonItems();
        if ($reasonItems) {
            $fields['cms_lead_reject_reason_id'] = [
                'class' => SelectField::class,
                'items' => $reasonItems,
                'options' => $this->statusFieldOptions([CmsLead::STATUS_REJECTED]),
            ];
        }
        $fields['reject_reason'] = [
            'class' => TextareaField::class,
            'label' => $reasonItems ? 'Комментарий к отказу' : 'Причина отклонения',
            'hint' => $reasonItems ? 'Необязательно. Если не заполнить, сохранится название причины.' : null,
            'elementOptions' => ['rows' => $reasonItems ? 3 : 5],
            'options' => $this->statusFieldOptions([CmsLead::STATUS_REJECTED]),
        ];

        return [
            'work' => [
                'class' => FieldSet::class,
                'name' => 'Работа со сделкой',
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

    private function canLinkIdentity(): bool
    {
        return $this->canWorkWithModel()
            && $this->model->status === CmsLead::STATUS_IN_WORK;
    }

    private function canDeleteLead(): bool
    {
        return \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
    }

    private function restoreAttributes(CmsLead $model, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $model->{$attribute} = $model->getOldAttribute($attribute);
        }
    }
}

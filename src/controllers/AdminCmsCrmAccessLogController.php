<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\controllers\AdminCmsCrmAccessRequestController as Requests;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmAccessLog;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessLog;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsUser;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * «Доступы» («Клиенты и сделки»): the journal of the access to clients
 * (crm\CrmAccessLog) on the standard backend list — columns, filters, sorting,
 * export and the saved tabs «Ждут моего решения» (the default one) and «Все»
 * (migration m261029_150000). A pending request is decided in its row
 * («Одобрить» / «Отказать», AdminCmsCrmAccessRequestController::decide());
 * its card stays the request's `view`. Who sees what —
 * CrmAccessLog::visibleCondition(); the menu counter — the requests waiting
 * for the user's decision.
 */
class AdminCmsCrmAccessLogController extends BackendModelStandartController
{
    public const SCOPE_WAITING = 'waiting';
    public const SCOPE_ALL = 'all';

    public function init()
    {
        $this->name = 'Доступы';
        $this->modelShowAttribute = 'asText';
        $this->modelClassName = CmsCrmAccessLog::class;
        $this->modelDefaultAction = 'view';
        // The deals right, as the requests: every employee of the CRM sees his events.
        $this->permissionName = 'cms/admin-lead';
        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        $table = CmsCrmAccessLog::tableName();
        $actions = ArrayHelper::merge(parent::actions(), [
            'index' => [
                'accessCallback' => static fn() => CrmAccessLog::ready() && CrmAccess::mainReady(),
                'pageHeader' => ['description' => $this->hint(), 'actions' => []],
                'emptyState' => [
                    'title' => 'Событий нет',
                    'description' => 'Во вкладке «Ждут моего решения» — запросы сотрудников о ваших клиентах. Все события доступа — во вкладке «Все».',
                    'icon' => 'fas fa-user-shield',
                ],
                'filters' => [
                    'visibleFilters' => ['scope', 'event', 'employee', 'company', 'period'],
                    'filtersModel' => [
                        'rules' => [[['scope', 'event', 'employee', 'company', 'period'], 'safe']],
                        'attributeDefines' => ['scope', 'event', 'employee', 'company', 'period'],
                        'fields' => [
                            'scope' => [
                                'class' => SelectField::class,
                                'label' => 'События',
                                'items' => [self::SCOPE_WAITING => 'Ждут моего решения', self::SCOPE_ALL => 'Все'],
                                'on apply' => function (QueryFiltersEvent $e) {
                                    if ((string)$e->field->value === self::SCOPE_WAITING) {
                                        $e->dataProvider->query->andWhere(CrmAccessLog::waitingCondition((int)\Yii::$app->user->id));
                                    }
                                },
                            ],
                            'event' => [
                                'class' => SelectField::class,
                                'label' => 'Событие',
                                'items' => CrmAccessLog::events(),
                                'on apply' => function (QueryFiltersEvent $e) use ($table) {
                                    if ($e->field->value && isset(CrmAccessLog::events()[(string)$e->field->value])) {
                                        $e->dataProvider->query->andWhere([$table.'.event' => (string)$e->field->value]);
                                    }
                                },
                            ],
                            'employee' => [
                                'class' => WidgetField::class,
                                'label' => 'Сотрудник',
                                'widgetClass' => AjaxSelectModel::class,
                                'widgetConfig' => [
                                    'modelClass' => CmsUser::class,
                                    'modelShowAttribute' => 'shortDisplayName',
                                    'searchQuery' => static fn($word = '') => $word ? CmsUser::find()->isWorker()->search($word) : CmsUser::find()->isWorker(),
                                ],
                                'on apply' => function (QueryFiltersEvent $e) use ($table) {
                                    if ($userId = (int)$e->field->value) {
                                        $e->dataProvider->query->andWhere(['or', [$table.'.actor_id' => $userId], [$table.'.user_id' => $userId]]);
                                    }
                                },
                            ],
                            'company' => [
                                'class' => WidgetField::class,
                                'label' => 'Клиент',
                                'widgetClass' => AjaxSelectModel::class,
                                'widgetConfig' => [
                                    'modelClass' => CmsCompany::class,
                                    'searchQuery' => static function ($word = '') {
                                        $query = CmsCompany::find();
                                        CrmAccess::applyClientVisibility($query);

                                        return $word ? $query->andWhere(['like', CmsCompany::tableName().'.name', $word]) : $query;
                                    },
                                ],
                                'on apply' => function (QueryFiltersEvent $e) use ($table) {
                                    if ($companyId = (int)$e->field->value) {
                                        $e->dataProvider->query->andWhere([$table.'.record_code' => CmsCrmAccessRequest::RECORD_COMPANY, $table.'.record_id' => $companyId]);
                                    }
                                },
                            ],
                            'period' => [
                                'class' => WidgetField::class,
                                'label' => 'Даты',
                                'widgetClass' => DaterangeInputWidget::class,
                                'widgetConfig' => ['options' => ['placeholder' => 'Диапазон дат']],
                                'on apply' => function (QueryFiltersEvent $e) use ($table) {
                                    if ($e->field->value && ($range = DaterangeInputWidget::parseRange($e->field->value))) {
                                        $e->dataProvider->query->andWhere(['between', $table.'.created_at', (int)$range[0], (int)$range[1]]);
                                    }
                                },
                            ],
                        ],
                    ],
                ],
                'grid' => [
                    'on init' => function (Event $event) use ($table) {
                        $query = $event->sender->dataProvider->query;
                        $query->with(['actor', 'user', 'lead', 'request']);
                        $query->andWhere(['or', [$table.'.cms_site_id' => (int)\Yii::$app->skeeks->site->id], [$table.'.cms_site_id' => null]]);
                        if (($visible = CrmAccessLog::visibleCondition((int)\Yii::$app->user->id)) !== null) {
                            $query->andWhere($visible);
                        }
                    },
                    'defaultOrder' => ['created_at' => SORT_DESC, 'id' => SORT_DESC],
                    'visibleColumns' => ['created_at', 'event', 'client', 'actor_id', 'user_id', 'level', 'ground', 'decision'],
                    'columns' => [
                        'created_at' => [
                            'label' => 'Когда',
                            'format' => ['datetime', 'short'],
                        ],
                        'event' => [
                            'label' => 'Событие',
                            'value' => static fn(CmsCrmAccessLog $model) => $model->eventName,
                        ],
                        'client' => [
                            'label' => 'Клиент',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessLog $model) => self::clientLink($model),
                        ],
                        'actor_id' => [
                            'label' => 'Кто',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessLog $model) => $model->actor ? Requests::employeeLink($model->actor) : 'CRM',
                        ],
                        'user_id' => [
                            'label' => 'Кому',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessLog $model) => Requests::employeeLink($model->user),
                        ],
                        'level' => [
                            'label' => 'Уровень',
                            'value' => static function (CmsCrmAccessLog $model) {
                                $levels = CrmAccess::levels();
                                $name = static fn($code) => $code ? ($levels[$code] ?? $code) : '';

                                return trim($name($model->level_from).($model->level_from || $model->level_to ? ' → ' : '').$name($model->level_to), ' →') ?: '—';
                            },
                        ],
                        'ground' => [
                            'label' => 'Основание',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessLog $model) => self::ground($model),
                        ],
                        'decision' => [
                            'label' => 'Решение',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessLog $model) => self::decisionButtons($model),
                        ],
                    ],
                ],
            ],
            'view' => [
                'class' => BackendModelAction::class,
                'name' => 'Событие',
                'icon' => 'fas fa-user-shield',
                'priority' => 10,
                'callback' => [$this, 'view'],
                'accessCallback' => fn() => $this->model && self::canSee((int)\Yii::$app->user->id, $this->model),
            ],
        ]);
        // Events are written by the CRM (crm\CrmAccessLog), never by a form, and kept for good.
        unset($actions['create'], $actions['update'], $actions['delete'], $actions['delete-multi']);

        return $actions;
    }

    /** An event opens the card of its request, else of its client. */
    public function view()
    {
        /** @var CmsCrmAccessLog $log */
        $log = $this->model;
        if ($log->request_id) {
            return $this->redirect(Url::to(['/cms/admin-cms-crm-access-request/view', 'pk' => (int)$log->request_id] + \Yii::$app->request->getQueryParams()));
        }
        $record = $log->getRecord();
        if ($record instanceof CmsCompany) {
            return $this->redirect(['/cms/admin-cms-company/view', 'pk' => (int)$record->id]);
        }
        if ($record) {
            return $this->redirect(['/cms/admin-user/view', 'pk' => (int)$record->id]);
        }

        return $this->redirect(['index']);
    }

    /** The hint at the top of the list: how many requests wait for my decision. */
    protected function hint(): string
    {
        $waiting = CrmAccess::instance()->pendingRequestsCount((int)\Yii::$app->user->id);

        return $waiting
            ? 'Ждут вашего решения: '.$waiting.'. Нажмите «Одобрить» или «Отказать» в строке запроса; подробности — по ссылке на запрос.'
            : 'Кто, кому и когда открыл доступ к клиенту: запросы и решения, выдача и смена уровня, передача, взятие из общего пула.';
    }

    public static function canSee(int $userId, CmsCrmAccessLog $log): bool
    {
        $query = CmsCrmAccessLog::find()->andWhere([CmsCrmAccessLog::tableName().'.id' => (int)$log->id]);
        if (($visible = CrmAccessLog::visibleCondition($userId)) !== null) {
            $query->andWhere($visible);
        }

        return $query->exists();
    }

    public static function clientLink(CmsCrmAccessLog $log): string
    {
        $record = $log->getRecord();
        // A client the viewer does not see (an event of his subordinate on a foreign client) is not named.
        $userId = (int)\Yii::$app->user->id;
        $access = CrmAccess::instance();
        // A client without the main one is named to an administrator and the heads (canSeeOrphan()).
        $code = $record instanceof CmsCompany ? 'company' : 'client';
        if ($record && !($record instanceof CmsCompany ? $access->canSeeCompany($userId, (int)$record->id) : $access->canSeeClient($userId, (int)$record->id))
            && !$access->canSeeOrphan($userId, $code, (int)$record->id)) {
            return 'клиент из базы';
        }
        if ($record instanceof CmsCompany) {
            return BackendEntityLink::widget(['controllerId' => '/cms/admin-cms-company', 'modelId' => (int)$record->id, 'action' => 'view', 'label' => (string)$record->name]);
        }
        if ($record) {
            return BackendEntityLink::widget(['controllerId' => '/cms/admin-user', 'modelId' => (int)$record->id, 'action' => 'view', 'label' => (string)$record->shortDisplayName]);
        }

        return '—';
    }

    public static function ground(CmsCrmAccessLog $log): string
    {
        $grounds = array_filter([
            $log->request_id ? ($log->request && CrmAccess::instance()->canSeeRequest((int)\Yii::$app->user->id, $log->request)
                ? BackendEntityLink::widget([
                    'controllerId' => '/cms/admin-cms-crm-access-request', 'modelId' => (int)$log->request_id, 'action' => 'view',
                    'label' => 'Запрос №'.(int)$log->request_id,
                ]) : Html::encode('Запрос №'.(int)$log->request_id)) : null,
            $log->cms_lead_id ? Requests::leadLink($log->lead) : null,
            $log->shop_payment_id ? Html::encode('Платёж №'.(int)$log->shop_payment_id) : null,
            $log->comment ? Html::encode((string)$log->comment) : null,
        ]);

        return $grounds ? implode('<br>', $grounds) : '—';
    }

    /** «Одобрить» / «Отказать» in the row of a pending request the user decides (lead-identity.js posts them). */
    public static function decisionButtons(CmsCrmAccessLog $log): string
    {
        $request = $log->event === CrmAccessLog::REQUEST ? $log->request : null;
        if (!$request) {
            return '';
        }
        if (!$request->isPending) {
            return Html::encode($request->statusName);
        }
        // One's own request is decided by others (as «Ждут моего решения» counts them).
        $userId = (int)\Yii::$app->user->id;
        if ((int)$request->requester_id === $userId || !CrmAccess::instance()->canDecideRequest($userId, $request)) {
            return 'Ждёт решения';
        }
        LeadIdentityAsset::register(\Yii::$app->view);
        $url = Url::to(['/cms/admin-cms-crm-access-request/decide']);
        $level = $request->level ?: CrmAccess::LEVEL_DEAL;

        return Html::tag('div',
            Html::hiddenInput('level', $level, ['data-sx-crm-request-level' => true]).
            Html::button('Одобрить', [
                'class' => 'sx-button sx-button--primary sx-button--sm',
                'data-sx-crm-request-decide' => 'approve',
                'data-id' => (int)$request->id,
                'data-url' => $url,
                'data-sx-confirm' => $request->cms_lead_id
                    ? 'Выдать сотруднику доступ «'.(CrmAccess::levels()[$level] ?? $level).'»?'
                    : 'Разрешить сотруднику работать с клиентом? На сотрудника будет создана сделка.',
            ]).' '.
            Html::button($request->cms_lead_id ? 'Не расширять' : 'Отказать', [
                'class' => 'sx-button sx-button--secondary sx-button--sm',
                'data-sx-crm-request-decide' => 'decline',
                'data-id' => (int)$request->id,
                'data-url' => $url,
            ]),
            ['class' => 'sx-lead-hint__actions']);
    }
}

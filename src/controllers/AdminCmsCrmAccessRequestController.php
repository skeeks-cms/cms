<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmAccessRequests;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * «Запросы доступа» (П2а, «Клиенты и сделки»): requests of employees to work
 * with a client of another main responsible (crm\CrmAccessRequests). The list
 * — «Ждут моего решения» by default, «Мои запросы», «Все»; the card opens in
 * the backend window (also from a notification): who asks, the level, the
 * client with its employees and open deals, the deal, «Одобрить» with a level
 * or «Отказать». A request is seen by its deciders and its sender only
 * (CrmAccess::canSeeRequest()); the section belongs to the core.
 */
class AdminCmsCrmAccessRequestController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Запросы доступа';
        $this->modelShowAttribute = 'asText';
        $this->modelClassName = CmsCrmAccessRequest::class;
        $this->modelDefaultAction = 'view';
        // The deals right: managers decide requests about their clients.
        $this->permissionName = 'cms/admin-lead';
        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            'index' => [
                'accessCallback' => fn() => CrmAccess::instance()->canUseRequests((int)\Yii::$app->user->id),
                'emptyState' => [
                    'title' => 'Запросов нет',
                    'description' => 'Здесь появляются запросы сотрудников, которые нашли вашего клиента и работают с ним по своей сделке или просят разрешения.',
                    'icon' => 'fas fa-user-lock',
                ],
                'filters' => [
                    'visibleFilters' => ['scope'],
                    'filtersModel' => [
                        'rules' => [['scope', 'safe']],
                        'attributeDefines' => ['scope'],
                        'fields' => [
                            'scope' => [
                                'class' => SelectField::class,
                                'label' => 'Запросы',
                                'items' => $this->scopes(),
                                'allowNull' => false,
                                'on apply' => function (QueryFiltersEvent $e) {
                                    $scope = (string)$e->field->value ?: CrmAccess::REQUESTS_TO_ME;
                                    if (!isset($this->scopes()[$scope])) {
                                        $scope = CrmAccess::REQUESTS_TO_ME;
                                    }
                                    $e->dataProvider->query->andWhere(CrmAccess::instance()->requestsCondition((int)\Yii::$app->user->id, $scope));
                                },
                            ],
                        ],
                    ],
                ],
                'grid' => [
                    'on init' => function (Event $event) {
                        $query = $event->sender->dataProvider->query;
                        $query->andFilterWhere([CmsCrmAccessRequest::tableName().'.cms_site_id' => (int)\Yii::$app->skeeks->site->id]);
                        // Without the filter applied (the first open): «Ждут моего решения».
                        if (!\Yii::$app->request->get('findex')) {
                            $query->andWhere(CrmAccess::instance()->requestsCondition((int)\Yii::$app->user->id, CrmAccess::REQUESTS_TO_ME));
                        }
                    },
                    'defaultOrder' => ['id' => SORT_DESC],
                    'visibleColumns' => ['request', 'client', 'requester_id', 'level', 'cms_lead_id', 'created_at', 'status'],
                    'columns' => [
                        'request' => [
                            'label' => 'Запрос',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessRequest $model) => BackendEntityLink::widget([
                                'controllerId' => '/cms/admin-cms-crm-access-request', 'modelId' => $model->id, 'action' => 'view',
                                'label' => $model->asText(),
                            ]),
                        ],
                        'client' => [
                            'label' => 'Клиент',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessRequest $model) => self::recordLink($model),
                        ],
                        'requester_id' => [
                            'label' => 'Кто просит',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessRequest $model) => self::employeeLink($model->requester),
                        ],
                        'level' => [
                            'label' => 'Уровень',
                            'value' => static fn(CmsCrmAccessRequest $model) => $model->levelName,
                        ],
                        'cms_lead_id' => [
                            'label' => 'Сделка',
                            'format' => 'raw',
                            'value' => static fn(CmsCrmAccessRequest $model) => self::leadLink($model->lead),
                        ],
                        'created_at' => [
                            'label' => 'Когда',
                            'format' => ['datetime', 'short'],
                        ],
                        'status' => [
                            'label' => 'Статус',
                            'value' => static fn(CmsCrmAccessRequest $model) => $model->statusName,
                        ],
                    ],
                ],
            ],
            'view' => [
                'class' => BackendModelAction::class,
                'name' => 'Запрос',
                'icon' => 'fas fa-user-lock',
                'priority' => 10,
                'callback' => [$this, 'view'],
                'accessCallback' => fn() => $this->model && CrmAccess::instance()->canSeeRequest((int)\Yii::$app->user->id, $this->model),
            ],
            'decide' => [
                'class' => ViewBackendAction::class,
                'isVisible' => false,
                'callback' => [$this, 'decide'],
            ],
        ]);
        // Requests are made by the CRM (crm\CrmAccessRequests), never by a form.
        unset($actions['create'], $actions['delete'], $actions['delete-multi']);
        // A record link opened in a new tab (BackendEntityLink falls back to "update") shows the card.
        $actions['update'] = [
            'class' => BackendModelAction::class,
            'isVisible' => false,
            'callback' => fn() => $this->redirect(['view', 'pk' => $this->model->id]),
            'accessCallback' => fn() => $this->model && CrmAccess::instance()->canSeeRequest((int)\Yii::$app->user->id, $this->model),
        ];

        return $actions;
    }

    /** «Все» — for a leader or an administrator. */
    protected function scopes(): array
    {
        $access = CrmAccess::instance();
        $userId = (int)\Yii::$app->user->id;
        $scopes = [CrmAccess::REQUESTS_TO_ME => 'Ждут моего решения', CrmAccess::REQUESTS_MINE => 'Мои запросы'];
        if ($access->isAdmin($userId) || \skeeks\cms\models\CmsDepartment::subordinateIds($userId)) {
            $scopes[CrmAccess::REQUESTS_ALL] = 'Все';
        }

        return $scopes;
    }

    public function view()
    {
        /** @var CmsCrmAccessRequest $request */
        $request = $this->model;
        $userId = (int)\Yii::$app->user->id;
        $access = CrmAccess::instance();
        if (!$access->canSeeRequest($userId, $request)) {
            throw new ForbiddenHttpException('Запрос видят сотрудник, который его отправил, основной ответственный клиента, его руководитель и администратор.');
        }
        $recordCode = (string)$request->record_code;
        $recordId = (int)$request->record_id;
        $column = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id';
        $isDecider = $access->isRequestDecider($userId, $request);

        return $this->render('@skeeks/cms/views/admin-cms-crm-access-request/view', [
            'model' => $request,
            'canDecide' => $access->canDecideRequest($userId, $request),
            'canCancel' => $request->isPending && (int)$request->requester_id === $userId,
            // The requester sees the client as in a match (its employees and deals only when he sees the client).
            'clientOpen' => $isDecider || ($recordCode === CmsCrmAccessRequest::RECORD_COMPANY
                    ? $access->canSeeCompany($userId, $recordId) : $access->canSeeClient($userId, $recordId)),
            'employees' => $access->accessList($recordCode, $recordId),
            'openLeads' => CmsLead::find()->andWhere([$column => $recordId, 'status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]])
                ->orderBy(['id' => SORT_DESC])->limit(20)->all(),
            'clientName' => (new CrmAccessRequests($access))->recordName($request, $userId),
        ]);
    }

    public function decide()
    {
        $response = new RequestResponse();
        if (!$response->isRequestAjaxPost()) {
            throw new BadRequestHttpException('Запрос решается кнопкой в его карточке.');
        }
        try {
            $request = CmsCrmAccessRequest::findOne((int)\Yii::$app->request->post('id'));
            if (!$request) {
                throw new NotFoundHttpException('Запрос не найден.');
            }
            $service = new CrmAccessRequests();
            $decision = (string)\Yii::$app->request->post('decision');
            if ($decision === 'cancel') {
                $service->cancel($request, (int)\Yii::$app->user->id);
                $response->message = 'Запрос отменён.';
            } else {
                $response->message = $service->decide($request, $decision === 'approve', (int)\Yii::$app->user->id,
                    trim((string)\Yii::$app->request->post('comment')) ?: null,
                    trim((string)\Yii::$app->request->post('level')) ?: null);
            }
            $response->success = true;
        } catch (\DomainException $e) {
            $response->success = false;
            $response->message = $e->getMessage();
        }

        return $response;
    }

    /** The company or the client of a request as a link to its card (a hidden person stays "контакт из базы"). */
    public static function recordLink(CmsCrmAccessRequest $request): string
    {
        $record = $request->record;
        $userId = (int)\Yii::$app->user->id;
        $name = (new CrmAccessRequests())->recordName($request, $userId);
        if ($record instanceof CmsCompany) {
            return 'Компания '.BackendEntityLink::widget(['controllerId' => '/cms/admin-cms-company', 'modelId' => $record->id, 'action' => 'view', 'label' => $name]);
        }
        if ($record instanceof CmsUser && CrmAccess::instance()->canSeeClient($userId, (int)$record->id)) {
            return 'Контакт '.BackendEntityLink::widget(['controllerId' => '/cms/admin-user', 'modelId' => $record->id, 'action' => 'view', 'label' => $name]);
        }

        return Html::encode($name);
    }

    /** An employee: his employee card, else his user card. */
    public static function employeeLink(?CmsUser $user): string
    {
        if (!$user) {
            return '—';
        }
        $controller = \Yii::$app->user->can('cms/admin-worker') ? '/cms/admin-worker' : '/cms/admin-user';

        return BackendEntityLink::widget(['controllerId' => $controller, 'modelId' => $user->id, 'label' => (string)$user->shortDisplayName]);
    }

    public static function leadLink(?CmsLead $lead): string
    {
        if (!$lead) {
            return '—';
        }

        return BackendEntityLink::widget(['controllerId' => '/cms/admin-cms-lead', 'modelId' => $lead->id, 'action' => 'view', 'label' => '№'.(int)$lead->id.' '.$lead->name]);
    }
}

<?php

namespace skeeks\cms\controllers\traits;

use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmAccessLog;
use skeeks\cms\crm\CrmClientTransfer;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use yii\helpers\Url;

/**
 * «Доступ» in the company and client cards (П2а+): everything about who works
 * with the client in one tab — the main responsible and «Передать клиента»
 * (a change of the main responsible by an administrator or a leader,
 * crm\CrmClientTransfer; the former main stays with "edit"), the employees
 * with their levels and where the access comes from, «Добавить сотрудника»,
 * «Доступ только по согласованию», pending requests about the client. Changed
 * by the main responsible, his leaders and administrators
 * (CrmAccess::canManageAccess()); others see it read only with their own
 * level. The tab is shown only where the client has employees (or to who may
 * transfer it), so a simple site without CRM work sees nothing new. The old
 * «Передать клиента» address opens «Доступ»; it still takes the transfer post.
 */
trait ClientTransferActions
{
    public function clientTransferAction(): array
    {
        return [
            'class' => BackendModelAction::class,
            'name' => 'Передать клиента',
            'icon' => 'fas fa-people-arrows',
            'priority' => 900,
            // «Передать клиента» is a block of «Доступ» now.
            'isVisible' => false,
            'callback' => [$this, 'clientTransfer'],
            'accessCallback' => function () {
                [$code, $id] = $this->transferRecord();
                if (!\Yii::$app->request->isPost) {
                    // The old address only leads to «Доступ» (which decides what the viewer sees).
                    return (bool)$id;
                }

                return $id && CrmAccess::instance()->canTransferClient((int)\Yii::$app->user->id, $code, $id)
                    && ($code === CmsCrmAccessRequest::RECORD_COMPANY
                        ? CrmAccess::instance()->companyResponsibleIds($id, null)
                        : CrmAccess::instance()->clientResponsibleIds($id, null));
            },
        ];
    }

    public function clientTransfer()
    {
        [$code, $id] = $this->transferRecord();
        $service = new CrmClientTransfer();
        $accessUrl = Url::current([0 => '/'.$this->uniqueId.'/crm-access']);
        if (!\Yii::$app->request->isPost) {
            return $this->redirect($accessUrl);
        }
        if (\Yii::$app->request->isPost) {
            $response = new RequestResponse();
            try {
                $response->message = $service->transfer(
                    $code,
                    $id,
                    (int)\Yii::$app->request->post('executor_id'),
                    (int)\Yii::$app->user->id,
                    (array)\Yii::$app->request->post('keep_leads', []),
                    (array)\Yii::$app->request->post('keep_tasks', [])
                );
                $response->success = true;
                $response->data = ['redirect' => $accessUrl];
                \Yii::$app->session->setFlash('success', $response->message);
            } catch (\DomainException $e) {
                $response->success = false;
                $response->message = $e->getMessage();
            }

            return $response;
        }

    }

    /**
     * «Доступы» — a tab of «Компании» (the journal of the access to clients,
     * crm\CrmAccessLog): every employee of the CRM sees the events he may see.
     */
    public function clientAccessLogAction(): array
    {
        return [
            'class' => \skeeks\cms\backend\ViewBackendAction::class,
            'name' => 'Доступы',
            'icon' => 'fas fa-user-shield',
            'priority' => 1100,
            'callback' => [$this, 'clientAccessLog'],
            'accessCallback' => static fn() => CrmAccessLog::ready() && CrmAccess::mainReady(),
        ];
    }

    public function clientAccessLog()
    {
        $request = \Yii::$app->request;
        $userId = (int)\Yii::$app->user->id;
        $table = \skeeks\cms\models\CmsCrmAccessLog::tableName();
        $waiting = (int)\skeeks\cms\models\CmsCrmAccessLog::find()->andWhere(CrmAccessLog::waitingCondition($userId))->count();
        $filters = [
            'scope' => (string)$request->get('scope', $waiting ? 'waiting' : 'all'),
            'event' => (string)$request->get('event', ''),
            'employee' => (int)$request->get('employee', 0),
            'company' => (int)$request->get('company', 0),
            'dates' => (string)$request->get('dates', ''),
        ];
        $query = \skeeks\cms\models\CmsCrmAccessLog::find()->with(['actor', 'user', 'lead', 'request']);
        if (($visible = CrmAccessLog::visibleCondition($userId)) !== null) {
            $query->andWhere($visible);
        }
        if ($filters['scope'] === 'waiting') {
            $query->andWhere(CrmAccessLog::waitingCondition($userId));
        }
        if (isset(CrmAccessLog::events()[$filters['event']])) {
            $query->andWhere([$table.'.event' => $filters['event']]);
        }
        if ($filters['employee']) {
            $query->andWhere(['or', [$table.'.actor_id' => $filters['employee']], [$table.'.user_id' => $filters['employee']]]);
        }
        if ($filters['company']) {
            $query->andWhere([$table.'.record_code' => CmsCrmAccessRequest::RECORD_COMPANY, $table.'.record_id' => $filters['company']]);
        }
        if ($filters['dates'] && ($range = \skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget::parseRange($filters['dates']))) {
            $query->andWhere(['between', $table.'.created_at', (int)$range[0], (int)$range[1]]);
        }

        return $this->render('@skeeks/cms/views/crm/access-log', [
            'provider' => new \yii\data\ActiveDataProvider([
                'query' => $query->orderBy([$table.'.created_at' => SORT_DESC, $table.'.id' => SORT_DESC]),
                'pagination' => ['pageSize' => 50],
            ]),
            'filters' => $filters,
            'waiting' => $waiting,
        ]);
    }

    /**
     * «Взять в работу» of a card of the general pool (П2д): the employee becomes
     * the main responsible ("full"), no request, no notification; the card opens
     * in full. A protected client without employees is assigned by a leader or
     * an administrator.
     */
    public function clientTakeAction(): array
    {
        return [
            'class' => BackendModelAction::class,
            'name' => 'Взять в работу',
            'isVisible' => false,
            'callback' => [$this, 'clientTake'],
            'accessCallback' => function () {
                [$code, $id] = $this->transferRecord();

                return $id && CrmAccess::instance()->canSeePool((int)\Yii::$app->user->id, $code, $id);
            },
        ];
    }

    public function clientTake()
    {
        [$code, $id] = $this->transferRecord();
        $viewUrl = Url::current([0 => '/'.$this->uniqueId.'/view']);
        if (!\Yii::$app->request->isPost) {
            return $this->redirect($viewUrl);
        }
        $response = new RequestResponse();
        $access = CrmAccess::instance();
        $actorId = (int)\Yii::$app->user->id;
        if (!$access->canTakeIntoWork($code, $id)) {
            $response->success = false;
            $response->message = $access->inPool($code, $id)
                ? 'Клиент под защитой: назначить ответственного может руководитель или администратор.'
                : 'Клиента уже взял в работу другой сотрудник. Обновите страницу.';

            return $response;
        }
        $access->setMain($code, $id, $actorId);
        CrmAccessLog::write(CrmAccessLog::TAKE, $code, $id, ['actor_id' => $actorId, 'user_id' => $actorId, 'level_to' => CrmAccess::LEVEL_FULL]);
        $response->success = true;
        $response->message = 'Клиент взят в работу: вы его основной ответственный.';

        return $response;
    }

    /** «+ Добавить сотрудника»: a small form in the standard backend window (the tab and the card block). */
    public function clientAccessAddAction(): array
    {
        return [
            'class' => BackendModelAction::class,
            'name' => 'Добавить сотрудника',
            'isVisible' => false,
            'callback' => [$this, 'clientAccessAdd'],
            'accessCallback' => function () {
                [$code, $id] = $this->transferRecord();

                return $id && CrmAccess::mainReady() && CrmAccess::instance()->canManageAccess((int)\Yii::$app->user->id, $code, $id);
            },
        ];
    }

    public function clientAccessAdd()
    {
        [$code, $id] = $this->transferRecord();
        if (\Yii::$app->request->isPost) {
            $response = new RequestResponse();
            try {
                $this->addAccess($code, $id, (int)\Yii::$app->request->post('user_id'), (string)\Yii::$app->request->post('level'));
                $response->message = 'Сотрудник добавлен.';
                $response->success = true;
            } catch (\DomainException $e) {
                $response->success = false;
                $response->message = $e->getMessage();
            }

            return $response;
        }

        return $this->render('@skeeks/cms/views/crm/access-add', ['model' => $this->model]);
    }

    /** Gives an employee a link with a level; an employee already linked is changed in the list. */
    protected function addAccess(string $code, int $id, int $userId, string $level): void
    {
        $access = CrmAccess::instance();
        if (!$access->canManageAccess((int)\Yii::$app->user->id, $code, $id)) {
            throw new \DomainException('Доступ к клиенту меняют основной ответственный, его руководитель или администратор.');
        }
        if (!isset(CrmAccess::levels()[$level])) {
            throw new \DomainException('Выберите уровень доступа.');
        }
        if (!CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $userId, CmsUser::tableName().'.is_active' => 1])->exists()) {
            throw new \DomainException('Выберите сотрудника.');
        }
        if ($access->linkLevel($userId, $code, $id) !== null) {
            throw new \DomainException('Этот сотрудник уже работает с клиентом: измените его уровень в списке.');
        }
        $access->grantLink($code, $id, $userId, $level);
        CrmAccessLog::write(CrmAccessLog::GRANT, $code, $id, ['actor_id' => (int)\Yii::$app->user->id, 'user_id' => $userId, 'level_to' => $level]);
    }

    public function clientAccessAction(): array
    {
        return [
            'class' => BackendModelAction::class,
            'name' => 'Доступ',
            'icon' => 'svg:lock',
            'priority' => 890,
            'callback' => [$this, 'clientAccess'],
            'accessCallback' => function () {
                [$code, $id] = $this->transferRecord();

                return $id && CrmAccess::mainReady() && (CrmAccess::instance()->accessList($code, $id)
                    || CrmAccess::instance()->canTransferClient((int)\Yii::$app->user->id, $code, $id));
            },
        ];
    }

    public function clientAccess()
    {
        [$code, $id] = $this->transferRecord();
        $access = CrmAccess::instance();
        $actorId = (int)\Yii::$app->user->id;
        $canManage = $access->canManageAccess($actorId, $code, $id);
        if (\Yii::$app->request->isPost) {
            $response = new RequestResponse();
            $request = \Yii::$app->request;
            try {
                if (!$canManage) {
                    throw new \DomainException('Доступ к клиенту меняют основной ответственный, его руководитель или администратор.');
                }
                switch ((string)$request->post('op')) {
                    case 'approval':
                    case 'protection':
                        // П2д: «Авто» (by payments and services) or «Всегда по согласованию».
                        $always = (string)$request->post('value') === 'always' || (string)$request->post('value') === '1';
                        $protectionBefore = \skeeks\cms\crm\CrmProtection::state($code, $id);
                        $access->setApprovalRequired($code, $id, $always);
                        $protectionAfter = \skeeks\cms\crm\CrmProtection::state($code, $id);
                        if ($protectionBefore !== $protectionAfter) {
                            CrmAccessLog::write(CrmAccessLog::PROTECTION, $code, $id, ['actor_id' => $actorId,
                                'comment' => \skeeks\cms\crm\CrmProtection::states()[$protectionBefore].' → '.\skeeks\cms\crm\CrmProtection::states()[$protectionAfter]]);
                        }
                        $response->message = $always ? 'Теперь с клиентом работают только по согласованию.'
                            : 'Защита клиента считается автоматически: '.\skeeks\cms\crm\CrmProtection::describe($code, $id).'.';
                        break;
                    case 'pool':
                        (new \skeeks\cms\crm\CrmProtection())->returnToPool($code, $id, $actorId);
                        $response->message = 'Клиент возвращён в общий пул: его возьмёт в работу первый сотрудник, который его найдёт.';
                        break;
                    case 'level':
                        $levelBefore = $access->linkLevel((int)$request->post('user_id'), $code, $id);
                        $access->setLevel($code, $id, (int)$request->post('user_id'), (string)$request->post('level'));
                        CrmAccessLog::write(CrmAccessLog::LEVEL, $code, $id, ['actor_id' => $actorId, 'user_id' => (int)$request->post('user_id'),
                            'level_from' => $levelBefore, 'level_to' => (string)$request->post('level')]);
                        $response->message = 'Уровень доступа изменён.';
                        break;
                    case 'remove':
                        $levelBefore = $access->linkLevel((int)$request->post('user_id'), $code, $id);
                        $access->removeLink($code, $id, (int)$request->post('user_id'));
                        CrmAccessLog::write(CrmAccessLog::REMOVE, $code, $id, ['actor_id' => $actorId, 'user_id' => (int)$request->post('user_id'), 'level_from' => $levelBefore]);
                        $response->message = 'Сотрудник больше не работает с клиентом.';
                        break;
                    case 'add':
                        $this->addAccess($code, $id, (int)$request->post('user_id'), (string)$request->post('level'));
                        $response->message = 'Сотрудник добавлен.';
                        break;
                    default:
                        throw new \DomainException('Неизвестное действие.');
                }
                $response->success = true;
            } catch (\DomainException $e) {
                $response->success = false;
                $response->message = $e->getMessage();
            }

            return $response;
        }

        $employees = $access->accessRows($code, $id);
        $mainId = $access->mainResponsibleId($code, $id);
        $canTransfer = $access->canTransferClient($actorId, $code, $id);
        $myLevel = $access->clientDataLevel($actorId, $code, $id);

        // The history of the access to this client (the journal), what the viewer may see.
        $history = [];
        if (CrmAccessLog::ready()) {
            $historyQuery = \skeeks\cms\models\CmsCrmAccessLog::find()->with(['actor', 'user', 'lead', 'request'])
                ->andWhere(['record_code' => $code, 'record_id' => $id])->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])->limit(100);
            if (($visible = CrmAccessLog::visibleCondition($actorId)) !== null && !$canManage) {
                $historyQuery->andWhere($visible);
            }
            $history = $historyQuery->all();
        }

        return $this->render('@skeeks/cms/views/crm/access', [
            'history' => $history,
            'model' => $this->model,
            'employees' => $employees,
            'leaders' => $mainId ? $access->names(CmsDepartment::leaderIdsOf($mainId)) : [],
            'canManage' => $canManage,
            'approval' => $access->approvalRequired($code, $id),
            'protectionReady' => \skeeks\cms\crm\CrmProtection::ready(),
            'protectionState' => \skeeks\cms\crm\CrmProtection::state($code, $id),
            'protectionText' => \skeeks\cms\crm\CrmProtection::describe($code, $id),
            'canTransfer' => $canTransfer,
            'transferUrl' => Url::current([0 => '/'.$this->uniqueId.'/transfer']),
            'work' => $canTransfer ? (new CrmClientTransfer())->openWork($code, $id) : ['leads' => [], 'tasks' => []],
            'requests' => CmsCrmAccessRequest::find()->andWhere([
                'record_code' => $code, 'record_id' => $id,
                'status' => CmsCrmAccessRequest::STATUS_PENDING, 'kind' => CmsCrmAccessRequest::KIND_WORK,
            ])->orderBy(['id' => SORT_DESC])->all(),
            'myLevel' => $mainId === $actorId ? 'основной ответственный' : ($myLevel ? mb_strtolower(CrmAccess::levels()[$myLevel] ?? $myLevel, 'UTF-8') : null),
        ]);
    }

    /** @return array [record code, id] of the current card; id 0 for an employee card. */
    protected function transferRecord(): array
    {
        $model = $this->model;
        if ($model instanceof CmsCompany) {
            return [CmsCrmAccessRequest::RECORD_COMPANY, (int)$model->id];
        }
        if ($model instanceof CmsUser && !$model->is_worker) {
            return [CmsCrmAccessRequest::RECORD_CLIENT, (int)$model->id];
        }

        return [CmsCrmAccessRequest::RECORD_CLIENT, 0];
    }
}

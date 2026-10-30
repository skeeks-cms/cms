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
    /** @var bool[] "employee:record code:id" => whether «Доступ» is shown, within one request */
    private array $clientAccessTabAnswers = [];

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
     * The old address of the journal (a tab of «Компании» before 29.10.2026):
     * the journal is the section «Доступы» (AdminCmsCrmAccessLogController).
     */
    public function clientAccessLogAction(): array
    {
        return [
            'class' => \skeeks\cms\backend\ViewBackendAction::class,
            'isVisible' => false,
            'callback' => fn() => $this->redirect(['/cms/admin-cms-crm-access-log/index']),
        ];
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
                $this->addAccess($code, $id, (int)\Yii::$app->request->post('user_id'), (string)\Yii::$app->request->post('level'),
                    (bool)\Yii::$app->request->post('make_main'));
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
    /**
     * @param bool $makeMain a client without the main one: this employee becomes it (else the one who adds, CrmAccess::ensureMain())
     */
    protected function addAccess(string $code, int $id, int $userId, string $level, bool $makeMain = false): void
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
        if ($makeMain && $access->mainResponsibleId($code, $id) === null) {
            $access->setMain($code, $id, $userId);
            CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, $code, $id, ['actor_id' => (int)\Yii::$app->user->id, 'user_id' => $userId,
                'level_to' => CrmAccess::LEVEL_FULL, 'comment' => 'сделан основным при добавлении']);

            return;
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
            'priority' => \skeeks\cms\crm\CrmCardTabs::PRIORITY_ACCESS,
            'callback' => [$this, 'clientAccess'],
            'accessCallback' => function () {
                [$code, $id] = $this->transferRecord();
                $userId = (int)\Yii::$app->user->id;
                // A card asks it for every tab menu of the page (audit final4: six times per feed):
                // one answer per employee and record within this controller, that is one request.
                $key = $userId.':'.$code.':'.(int)$id;
                if (array_key_exists($key, $this->clientAccessTabAnswers)) {
                    return $this->clientAccessTabAnswers[$key];
                }
                $access = CrmAccess::instance();
                // Only who sees the client: a hidden one shows neither its name nor its employees.
                $sees = $code === CmsCrmAccessRequest::RECORD_COMPANY ? $access->canSeeCompany($userId, (int)$id) : $access->canSeeClient($userId, (int)$id);

                return $this->clientAccessTabAnswers[$key] = $id && CrmAccess::mainReady() && $sees
                    && ($access->accessList($code, $id) || $access->canTransferClient($userId, $code, $id));
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
                    case 'level':
                        $levelBefore = $access->linkLevel((int)$request->post('user_id'), $code, $id);
                        if (!$access->setLevel($code, $id, (int)$request->post('user_id'), (string)$request->post('level'))) {
                            // The same level again: a success, no journal event.
                            $response->message = 'Уровень доступа не изменился.';
                            break;
                        }
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
            // The reason names payments and services: only for who sees the whole client ("full"), not a card of the pool.
            'protectionText' => \skeeks\cms\crm\CrmProtection::describe($code, $id, $canManage || CrmAccess::seesWholeClient($access->wholeClientLevel($actorId, $code, $id))),
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

<?php

namespace skeeks\cms\controllers\traits;

use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\crm\CrmAccess;
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
                        $access->setApprovalRequired($code, $id, (bool)$request->post('value'));
                        $response->message = $request->post('value') ? 'Теперь с клиентом работают только по согласованию.' : 'Согласование для работы с клиентом выключено.';
                        break;
                    case 'level':
                        $access->setLevel($code, $id, (int)$request->post('user_id'), (string)$request->post('level'));
                        $response->message = 'Уровень доступа изменён.';
                        break;
                    case 'remove':
                        $access->removeLink($code, $id, (int)$request->post('user_id'));
                        $response->message = 'Сотрудник больше не работает с клиентом.';
                        break;
                    case 'add':
                        $userId = (int)$request->post('user_id');
                        $level = (string)$request->post('level');
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

        $employees = $access->accessList($code, $id);
        // Where an access comes from: «Работать с этим клиентом» leaves "deal" to who has a deal of the client.
        $dealOwners = array_map('intval', CmsLead::find()->select('executor_id')->distinct()
            ->andWhere([$code === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id' => $id])
            ->andWhere(['not', ['executor_id' => null]])->column());
        foreach ($employees as &$employee) {
            $employee['origin'] = $employee['main'] ? 'основной ответственный'
                : ($employee['level'] === CrmAccess::LEVEL_DEAL && in_array($employee['id'], $dealOwners, true) ? 'по своей сделке' : 'выдан вручную');
        }
        unset($employee);
        $mainId = $access->mainResponsibleId($code, $id);
        $canTransfer = $access->canTransferClient($actorId, $code, $id);
        $myLevel = $access->clientDataLevel($actorId, $code, $id);

        return $this->render('@skeeks/cms/views/crm/access', [
            'model' => $this->model,
            'employees' => $employees,
            'leaders' => $mainId ? $access->names(CmsDepartment::leaderIdsOf($mainId)) : [],
            'canManage' => $canManage,
            'approval' => $access->approvalRequired($code, $id),
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

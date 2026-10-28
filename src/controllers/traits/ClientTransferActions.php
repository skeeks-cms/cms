<?php

namespace skeeks\cms\controllers\traits;

use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\crm\CrmClientTransfer;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsUser;
use yii\helpers\Url;

/**
 * «Передать клиента» and «Доступ к клиенту» in the company and client cards
 * (П2а). Transfer — a change of the main responsible by an administrator or a
 * leader (crm\CrmClientTransfer). Access — the main responsible first, the
 * employees with their levels, «Доступ только по согласованию»; changed by the
 * main responsible, his leaders and administrators (CrmAccess::canManageAccess()).
 * Both are shown only where the client has employees, so a simple site without
 * CRM work sees nothing new.
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
            'callback' => [$this, 'clientTransfer'],
            'accessCallback' => function () {
                [$code, $id] = $this->transferRecord();

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
                $response->data = ['redirect' => Url::current()];
                \Yii::$app->session->setFlash('success', $response->message);
            } catch (\DomainException $e) {
                $response->success = false;
                $response->message = $e->getMessage();
            }

            return $response;
        }

        $access = CrmAccess::instance();

        return $this->render('@skeeks/cms/views/crm/transfer', [
            'model' => $this->model,
            'recordCode' => $code,
            'responsible' => $access->names($code === CmsCrmAccessRequest::RECORD_COMPANY
                ? $access->companyResponsibleIds($id, null)
                : $access->clientResponsibleIds($id, null)),
            'work' => $service->openWork($code, $id),
        ]);
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

                return $id && CrmAccess::mainReady() && CrmAccess::instance()->accessList($code, $id);
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

        return $this->render('@skeeks/cms/views/crm/access', [
            'model' => $this->model,
            'employees' => $access->accessList($code, $id),
            'canManage' => $canManage,
            'approval' => $access->approvalRequired($code, $id),
            'canTransfer' => $access->canTransferClient($actorId, $code, $id),
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

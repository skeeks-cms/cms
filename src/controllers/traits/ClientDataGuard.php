<?php

namespace skeeks\cms\controllers\traits;

use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCrmAccessRequest;

/**
 * П2а+: the controllers of client data rows (phones, emails, addresses,
 * links, people of a company) let change them only to an employee who may
 * edit the client (CrmAccess::canEditClientRecord(): "edit", "full", a leader,
 * an administrator). "Свои сделки" sees the rows and contacts the client;
 * update, delete and delete-multi are refused (403, and their buttons are
 * hidden as for any action without access); create too, except a new phone
 * or email (clientDataAddable()).
 *
 * Usage in actions(): `return $this->guardClientData($actions);`
 * The controller names the owner column of its rows in clientDataColumn().
 */
trait ClientDataGuard
{
    /** The owner column of the rows: "cms_company_id" (a company) or "cms_user_id" (a person). */
    abstract protected function clientDataColumn(): string;

    /**
     * Phones and emails: a new row may be added on every level
     * (CrmAccess::canAddClientContact()), existing ones are changed from "edit".
     */
    protected function clientDataAddable(): bool
    {
        return false;
    }

    protected function guardClientData(array $actions, array $names = ['create', 'update', 'delete', 'delete-multi']): array
    {
        foreach ($names as $name) {
            if (!isset($actions[$name])) {
                continue;
            }
            $previous = $actions[$name]['accessCallback'] ?? null;
            $actions[$name]['accessCallback'] = function ($action = null) use ($name, $previous) {
                if (is_callable($previous) && !call_user_func($previous, $action)) {
                    return false;
                }
                if ($previous === false) {
                    return false;
                }

                return in_array($name, ['create', 'delete-multi'], true)
                    ? $this->canEditRequestedClient()
                    : ($this->model === null || CrmAccess::instance()->canEditClientRecord((int)\Yii::$app->user->id, $this->model));
            };
            if ($name === 'delete-multi') {
                $each = $actions[$name]['eachAccessCallback'] ?? null;
                $actions[$name]['eachAccessCallback'] = function ($model) use ($each) {
                    return (!is_callable($each) || call_user_func($each, $model))
                        && CrmAccess::instance()->canEditClientRecord((int)\Yii::$app->user->id, $model);
                };
            }
        }

        return $actions;
    }

    /**
     * The client a new row is made for: the owner column in the query and in
     * the posted form (both are checked, a form cannot switch to another client).
     */
    protected function canEditRequestedClient(): bool
    {
        $column = $this->clientDataColumn();
        $formName = (new $this->modelClassName())->formName();
        $code = $column === 'cms_company_id' ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT;
        $access = CrmAccess::instance();
        $userId = (int)\Yii::$app->user->id;
        foreach ([\Yii::$app->request->get($formName), \Yii::$app->request->post($formName)] as $data) {
            $id = (int)($data[$column] ?? 0);
            if ($id > 0 && !($this->clientDataAddable() ? $access->canAddClientContact($userId, [$code, $id]) : $access->canEditClientRecord($userId, [$code, $id]))) {
                return false;
            }
        }

        return true;
    }
}

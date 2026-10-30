<?php

namespace skeeks\cms\controllers\traits;

use skeeks\cms\backend\actions\BackendModelCreateAction;
use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\models\CmsLead;
use skeeks\cms\rbac\CmsManager;
use yii\base\Event;
use yii\helpers\ArrayHelper;

/**
 * Create/update/delete of a record that belongs to a lead (phone, email,
 * address, link) for a `BackendModelStandartController`.
 *
 * Only the lead's responsible manager or an administrator may change it. The
 * lead id of a new record comes from the request (`<FormName>[cms_lead_id]`),
 * of an existing one from its saved value, so a posted form cannot move the
 * record to another lead.
 *
 * П2г (owner's decision 30.10.2026): phones, emails and addresses of a lead
 * are «данные обращения» — written only by incoming sources, never added or
 * changed by hand (`$requestData`); contacts are added to the company or the
 * person of the lead. A wrong row may still be removed by the responsible.
 */
trait LeadContactActions
{
    /**
     * @param string|null $size form window size, BackendAction::SIZE_*; null for the regular window
     * @param bool $requestData rows of the request: create and update are refused to everybody
     */
    protected function leadContactActions(?string $size = BackendAction::SIZE_SMALL, bool $requestData = false): array
    {
        $access = fn() => $this->canManageLeadContact();
        $write = $requestData ? static fn() => false : $access;
        $lockLead = function (Event $event): void {
            $event->sender->model->cms_lead_id = $this->trustedLeadId($event->sender->model);
        };

        return [
            // The flat list spans every lead, so it is not a manager's tool.
            'index' => [
                'accessCallback' => static fn() => \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS),
            ],
            'create' => [
                'fields' => [$this, 'updateFields'],
                'size' => $size,
                'buttons' => ['save'],
                'accessCallback' => $write,
                'isVisible' => !$requestData,
                'on '.BackendModelCreateAction::EVENT_BEFORE_VALIDATE => $lockLead,
            ],
            'update' => [
                'fields' => [$this, 'updateFields'],
                'size' => $size,
                'buttons' => ['save'],
                'accessCallback' => $write,
                'isVisible' => !$requestData,
                'on '.BackendModelUpdateAction::EVENT_BEFORE_VALIDATE => $lockLead,
            ],
            'delete' => ['accessCallback' => $access],
        ];
    }

    protected function canManageLeadContact(): bool
    {
        $leadId = $this->model && !$this->model->isNewRecord
            ? (int)$this->model->cms_lead_id
            : $this->requestedLeadId();
        $lead = CmsLead::find()
            ->forManager()
            ->cmsSite()
            ->andWhere([CmsLead::tableName().'.id' => $leadId])
            ->one();

        return $lead && $lead->canBeWorkedBy((int)\Yii::$app->user->id);
    }

    protected function trustedLeadId($model): int
    {
        return $model->isNewRecord
            ? $this->requestedLeadId()
            : (int)$model->getOldAttribute('cms_lead_id');
    }

    private function requestedLeadId(): int
    {
        $formName = (new $this->modelClassName())->formName();

        return (int)ArrayHelper::getValue(\Yii::$app->request->get($formName, []), 'cms_lead_id');
    }
}

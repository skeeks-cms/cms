<?php

namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLead2contractor;
use yii\db\ActiveRecord;

/**
 * Requisites of a lead: `create?lead_id=<id>` adds, `detach?lead_id=<id>&contractor_id=<id>`
 * removes the link (the contractor itself stays, it may serve other records).
 */
class AdminCmsLeadContractorController extends AdminCmsContractorAttachController
{
    public function init()
    {
        parent::init();
        $this->name = 'Реквизиты лида';
        // Lead managers work here without the company section permission.
        $this->permissionName = 'cms/admin-lead';
        $this->permissionNames = [$this->permissionName => $this->name];
    }

    public function actions()
    {
        return array_merge(parent::actions(), [
            'detach' => [
                'class' => \skeeks\cms\backend\ViewBackendAction::class,
                'isVisible' => false,
                'accessCallback' => fn() => $this->canAttach($this->owner),
                'callback' => [$this, 'detach'],
            ],
        ]);
    }

    public function detach()
    {
        $rr = new \skeeks\cms\helpers\RequestResponse();
        if (!$rr->isRequestAjaxPost()) {
            throw new \yii\web\BadRequestHttpException();
        }
        $map = CmsLead2contractor::find()->andWhere([
            'cms_lead_id' => (int)$this->owner->id,
            'cms_contractor_id' => (int)\Yii::$app->request->post('contractor_id'),
        ])->one();
        $rr->success = $map && $map->delete() !== false;
        $rr->message = $rr->success ? 'Реквизиты убраны из лида.' : 'Реквизиты не найдены.';

        return $rr;
    }

    protected function ownerParam(): string
    {
        return 'lead_id';
    }

    protected function findOwner(int $id): ?ActiveRecord
    {
        return CmsLead::find()->forManager()->cmsSite()->andWhere([CmsLead::tableName().'.id' => $id])->one();
    }

    protected function canAttach(ActiveRecord $owner): bool
    {
        /** @var CmsLead $owner */
        return $owner->canBeWorkedBy((int)\Yii::$app->user->id);
    }

    protected function isAttached(ActiveRecord $owner, CmsContractor $contractor): bool
    {
        return CmsLead2contractor::find()
            ->andWhere(['cms_lead_id' => (int)$owner->id, 'cms_contractor_id' => (int)$contractor->id])
            ->exists();
    }

    protected function attach(ActiveRecord $owner, CmsContractor $contractor): void
    {
        if ($this->isAttached($owner, $contractor)) {
            return;
        }
        $map = new CmsLead2contractor([
            'cms_lead_id' => (int)$owner->id,
            'cms_contractor_id' => (int)$contractor->id,
        ]);
        if (!$map->save()) {
            throw new \RuntimeException(implode('; ', $map->getFirstErrors()));
        }
    }

    protected function ownerUrl(ActiveRecord $owner): array
    {
        return ['/cms/admin-cms-lead/view', 'pk' => (int)$owner->id];
    }
}

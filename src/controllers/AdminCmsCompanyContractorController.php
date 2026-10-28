<?php

namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2contractor;
use skeeks\cms\models\CmsContractor;
use yii\db\ActiveRecord;

/** Requisites of a company: `create?company_id=<id>`. */
class AdminCmsCompanyContractorController extends AdminCmsContractorAttachController
{
    public function init()
    {
        parent::init();
        $this->name = 'Реквизиты компании';
    }

    protected function ownerParam(): string
    {
        return 'company_id';
    }

    protected function findOwner(int $id): ?ActiveRecord
    {
        return CmsCompany::find()->forManager()->andWhere([CmsCompany::tableName().'.id' => $id])->one();
    }

    protected function canAttach(ActiveRecord $owner): bool
    {
        // П2а+: requisites are the client data — "edit", "full", a leader, an administrator.
        return \Yii::$app->user->can('cms/admin-company/manage', ['model' => $owner])
            && \skeeks\cms\crm\CrmAccess::instance()->canEditClientRecord((int)\Yii::$app->user->id, $owner);
    }

    protected function isAttached(ActiveRecord $owner, CmsContractor $contractor): bool
    {
        return CmsCompany2contractor::find()
            ->andWhere(['cms_company_id' => (int)$owner->id, 'cms_contractor_id' => (int)$contractor->id])
            ->exists();
    }

    protected function attach(ActiveRecord $owner, CmsContractor $contractor): void
    {
        if ($this->isAttached($owner, $contractor)) {
            return;
        }
        // П2а: an INN of another company is linked only with a reason and marked as a possible duplicate.
        $others = array_values(array_diff(array_map('intval', CmsCompany2contractor::find()->select('cms_company_id')
            ->andWhere(['cms_contractor_id' => (int)$contractor->id])->column()), [(int)$owner->id]));
        $reason = trim((string)\Yii::$app->request->post('CrmDuplicate', [])['reason'] ?? '');
        if ($others && \skeeks\cms\crm\CrmDuplicateGuard::ready()) {
            if (\skeeks\cms\crm\CrmDuplicateGuard::refuses($reason)) {
                throw new \DomainException(\skeeks\cms\crm\CrmDuplicateGuard::message('ИНН '.$contractor->inn, $others));
            }
            \skeeks\cms\crm\CrmDuplicateGuard::mark((int)$owner->id, $others, $reason);
        }
        $map = new CmsCompany2contractor([
            'cms_company_id' => (int)$owner->id,
            'cms_contractor_id' => (int)$contractor->id,
        ]);
        if (!$map->save()) {
            throw new \RuntimeException(implode('; ', $map->getFirstErrors()));
        }
    }

    public function updateFields($action)
    {
        $result = parent::updateFields($action);
        $result['crm_duplicate_reason'] = [
            'class' => \skeeks\yii2\form\fields\HtmlBlock::class,
            'content' => '<div class="form-group"><label class="control-label">'.\yii\helpers\Html::encode(\skeeks\cms\crm\CrmDuplicateGuard::reasonLabel()).'</label>'
                .\yii\helpers\Html::textarea('CrmDuplicate[reason]', (string)(\Yii::$app->request->post('CrmDuplicate', [])['reason'] ?? ''), [
                    'class' => 'form-control', 'rows' => 2,
                    'placeholder' => 'Заполните, только если CRM сообщит, что реквизиты с этим ИНН уже есть у другой компании',
                ]).'</div>',
        ];

        return $result;
    }

    protected function ownerUrl(ActiveRecord $owner): array
    {
        return ['/cms/admin-cms-company/view', 'pk' => (int)$owner->id];
    }
}

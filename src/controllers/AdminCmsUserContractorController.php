<?php

namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsContractorMap;
use skeeks\cms\models\CmsUser;
use yii\db\ActiveRecord;

/** Requisites of a client (user): `create?user_id=<id>`. */
class AdminCmsUserContractorController extends AdminCmsContractorAttachController
{
    public function init()
    {
        parent::init();
        $this->name = 'Реквизиты клиента';
        // Client managers work here without the company section permission.
        $this->permissionName = 'cms/admin-user';
        $this->permissionNames = [$this->permissionName => $this->name];
    }

    protected function ownerParam(): string
    {
        return 'user_id';
    }

    protected function findOwner(int $id): ?ActiveRecord
    {
        return CmsUser::find()->cmsSite()->forManager()->andWhere([CmsUser::tableName().'.id' => $id])->one();
    }

    protected function canAttach(ActiveRecord $owner): bool
    {
        return \Yii::$app->user->can('cms/admin-user/manage', ['model' => $owner]);
    }

    protected function isAttached(ActiveRecord $owner, CmsContractor $contractor): bool
    {
        return CmsContractorMap::find()
            ->andWhere(['cms_user_id' => (int)$owner->id, 'cms_contractor_id' => (int)$contractor->id])
            ->exists();
    }

    protected function attach(ActiveRecord $owner, CmsContractor $contractor): void
    {
        if ($this->isAttached($owner, $contractor)) {
            return;
        }
        $map = new CmsContractorMap([
            'cms_user_id' => (int)$owner->id,
            'cms_contractor_id' => (int)$contractor->id,
        ]);
        if (!$map->save()) {
            throw new \RuntimeException(implode('; ', $map->getFirstErrors()));
        }
    }

    protected function ownerUrl(ActiveRecord $owner): array
    {
        return ['/cms/admin-user/view', 'pk' => (int)$owner->id];
    }
}

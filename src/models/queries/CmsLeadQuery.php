<?php

namespace skeeks\cms\models\queries;

use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;

class CmsLeadQuery extends CmsActiveQuery
{
    public function forPartner(int $userId)
    {
        return $this->andWhere([$this->getPrimaryTableName().'.partner_id' => $userId]);
    }

    public function forExecutor(int $userId)
    {
        return $this->andWhere([$this->getPrimaryTableName().'.executor_id' => $userId]);
    }

    /**
     * Restricts leads to the CRM scope available to an employee.
     *
     * An unassigned lead without a known CRM identity remains in the common
     * queue. Once an executor or CRM identity is known, visibility follows the
     * executor hierarchy and the standard client/company forManager() scopes.
     */
    public function forManager(User $user = null)
    {
        if ($user === null) {
            $user = \Yii::$app->user->identity;
            $isCanAdmin = \Yii::$app->user->can(CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
        } else {
            $isCanAdmin = \Yii::$app->authManager->checkAccess(
                $user->id,
                CmsManager::PERMISSION_ROLE_ADMIN_ACCESS
            );
        }

        if (!$user) {
            return $this->andWhere('0=1');
        }
        if ($isCanAdmin) {
            return $this;
        }

        $managerIds = [(int)$user->id];
        foreach ($user->subordinates as $subordinate) {
            $managerIds[] = (int)$subordinate->id;
        }
        $managerIds = array_values(array_unique($managerIds));

        $availableClientIds = CmsUser::find()
            ->forManager($user)
            // A worker can be linked to their own user record through the
            // manager relation. That must not expose partner leads submitted
            // by the same worker as ordinary CRM work.
            ->andWhere(['<>', CmsUser::tableName().'.id', (int)$user->id])
            ->select(CmsUser::tableName().'.id');
        $availableCompanyIds = CmsCompany::find()
            ->forManager($user)
            ->select(CmsCompany::tableName().'.id');
        $table = $this->getPrimaryTableName();

        return $this->andWhere(['or',
            [$table.'.executor_id' => $managerIds],
            [$table.'.submitted_by_id' => $availableClientIds],
            [$table.'.partner_id' => $availableClientIds],
            [$table.'.cms_user_id' => $availableClientIds],
            [$table.'.cms_company_id' => $availableCompanyIds],
            ['and',
                [$table.'.executor_id' => null],
                [$table.'.submitted_by_id' => null],
                [$table.'.partner_id' => null],
                [$table.'.cms_user_id' => null],
                [$table.'.cms_company_id' => null],
            ],
        ]);
    }

    public function fromSource(string $type, string $reference, ?int $siteId = null)
    {
        return $this->andWhere([
            $this->getPrimaryTableName().'.cms_site_id' => $siteId,
            $this->getPrimaryTableName().'.source_type' => $type,
            $this->getPrimaryTableName().'.source_ref'  => $reference,
        ]);
    }

    public function search($word = '')
    {
        $word = trim($word);
        if ($word === '') {
            return $this;
        }

        $leadTable = $this->getPrimaryTableName();
        $condition = ['or',
            ['like', $leadTable.'.name', $word],
            ['like', $leadTable.'.description', $word],
        ];

        if (str_contains($word, '@')) {
            $this->joinWith(['emails leadEmails']);
            $condition[] = ['like', 'leadEmails.value', mb_strtolower($word, 'UTF-8')];
        }

        if ($phoneCondition = PhoneHelper::likeCondition('leadPhones.value', $word)) {
            $this->joinWith(['phones leadPhones']);
            $condition[] = $phoneCondition;
        }

        return $this->andWhere($condition)->groupBy($leadTable.'.id');
    }
}

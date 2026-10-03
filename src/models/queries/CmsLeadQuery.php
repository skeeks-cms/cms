<?php

namespace skeeks\cms\models\queries;

use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
use yii\db\Query;

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
     * Unassigned leads require evidence of an available company. Unmatched
     * leads are administrator triage; assignment retains the executor hierarchy.
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

        $availableCompanyIds = CmsCompany::find()
            ->forManager($user)
            ->select(CmsCompany::tableName().'.id');
        $table = $this->getPrimaryTableName();

        return $this->andWhere(['or',
            [$table.'.executor_id' => $managerIds],
            ['exists', $availableCompanyIds->andWhere($this->companyEvidenceCondition($table, (int)$user->id))],
        ]);
    }

    /** Read-only evidence; never automatically converts or links a lead. */
    private function companyEvidenceCondition(string $lead, int $viewerId): array
    {
        $company = CmsCompany::tableName();
        $links = (new Query())->select(new \yii\db\Expression('1'))->from(['leadCompanyUser' => '{{%cms_company2user}}'])
            ->where('leadCompanyUser.cms_company_id = '.$company.'.id')
            ->andWhere(['<>', 'leadCompanyUser.cms_user_id', $viewerId])
            ->andWhere(['or',
                'leadCompanyUser.cms_user_id = '.$lead.'.cms_user_id',
                'leadCompanyUser.cms_user_id = '.$lead.'.submitted_by_id',
                'leadCompanyUser.cms_user_id = '.$lead.'.partner_id',
                ['exists', $this->contactEvidence('{{%cms_lead_phone}}', '{{%cms_user_phone}}', $lead, 'leadCompanyUser.cms_user_id', 'cms_user_id', true)],
                ['exists', $this->contactEvidence('{{%cms_lead_email}}', '{{%cms_user_email}}', $lead, 'leadCompanyUser.cms_user_id', 'cms_user_id', false)],
            ]);

        return ['or',
            $lead.'.cms_company_id = '.$company.'.id',
            ['exists', $links],
            ['exists', $this->contactEvidence('{{%cms_lead_phone}}', '{{%cms_company_phone}}', $lead, $company.'.id', 'cms_company_id', true)],
            ['exists', $this->contactEvidence('{{%cms_lead_email}}', '{{%cms_company_email}}', $lead, $company.'.id', 'cms_company_id', false)],
        ];
    }

    private function contactEvidence(string $leadContacts, string $crmContacts, string $lead, string $owner, string $ownerColumn, bool $phone): Query
    {
        $left = $phone ? $this->phoneKey('leadContact.value') : 'LOWER(TRIM(leadContact.value))';
        $right = $phone ? $this->phoneKey('crmContact.value') : 'LOWER(TRIM(crmContact.value))';
        return (new Query())->select(new \yii\db\Expression('1'))->from(['leadContact' => $leadContacts])
            ->innerJoin(['crmContact' => $crmContacts], $left.' = '.$right)
            ->where('leadContact.cms_lead_id = '.$lead.'.id')
            ->andWhere('crmContact.'.$ownerColumn.' = '.$owner)
            ->andWhere($phone ? 'LENGTH('.$left.') >= 10' : $left." <> ''");
    }

    private function phoneKey(string $column): string
    {
        $digits = PhoneHelper::sqlDigits($column);
        return "CASE WHEN LENGTH($digits) = 11 AND SUBSTR($digits, 1, 1) IN ('7', '8') THEN SUBSTR($digits, 2) ELSE $digits END";
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

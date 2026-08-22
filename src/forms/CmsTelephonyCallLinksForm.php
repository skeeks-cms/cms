<?php

namespace skeeks\cms\forms;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsUser;
use skeeks\cms\services\CmsTelephonyCallLinkService;
use yii\base\Model;

/**
 * Manager-facing selection of the CRM entities linked to one canonical call.
 */
class CmsTelephonyCallLinksForm extends Model
{
    public $cms_lead_id;
    public $cms_company_id;
    public $cms_user_id;

    public static function fromCall(CmsTelephonyCall $call): self
    {
        $form = new self();
        $form->cms_lead_id = $call->cms_lead_id ?: null;
        $form->cms_company_id = $call->cms_company_id ?: null;
        $form->cms_user_id = $call->cms_user_id ?: null;

        return $form;
    }

    public function rules()
    {
        return [
            [['cms_lead_id', 'cms_company_id', 'cms_user_id'], 'default', 'value' => null],
            [['cms_lead_id', 'cms_company_id', 'cms_user_id'], 'integer'],
            ['cms_lead_id', 'validateLead'],
            ['cms_company_id', 'validateCompany'],
            ['cms_user_id', 'validateUser'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'cms_lead_id' => 'Лид',
            'cms_company_id' => 'Компания',
            'cms_user_id' => 'Клиент',
        ];
    }

    public function validateLead($attribute): void
    {
        $this->validateScopedEntity($attribute, CmsLead::class);
    }

    public function validateCompany($attribute): void
    {
        $this->validateScopedEntity($attribute, CmsCompany::class);
    }

    public function validateUser($attribute): void
    {
        $this->validateScopedEntity($attribute, CmsUser::class);
    }

    private function validateScopedEntity(string $attribute, string $modelClass): void
    {
        if ($this->{$attribute} === null || $this->hasErrors($attribute)) {
            return;
        }

        $service = new CmsTelephonyCallLinkService();
        if ($modelClass === CmsLead::class) {
            $query = $service->availableLeadQuery();
        } elseif ($modelClass === CmsCompany::class) {
            $query = $service->availableCompanyQuery();
        } else {
            $query = $service->availableUserQuery();
        }

        $exists = $query
            ->andWhere([$modelClass::tableName().'.id' => (int)$this->{$attribute}])
            ->exists();

        if (!$exists) {
            $this->addError($attribute, 'Запись недоступна менеджеру на текущем сайте.');
        }
    }
}

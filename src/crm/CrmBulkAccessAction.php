<?php

namespace skeeks\cms\crm;

use skeeks\cms\backend\actions\BackendModelMultiWindowAction;
use yii\db\ActiveQuery;

/**
 * A bulk action of the access over the company list (CrmBulkAccessForm):
 * the standard window of the marked rows or «для всех», with the count of the
 * companies before the confirmation. Shown to who manages the access (an
 * administrator, a leader); each company is checked by
 * CrmAccess::canManageAccess() — a foreign one refuses the whole selection.
 */
class CrmBulkAccessAction extends BackendModelMultiWindowAction
{
    /** @var string CrmBulkAccessForm::MAIN|GRANT|PROTECTION */
    public $operation = CrmBulkAccessForm::MAIN;

    public $size = 'small';

    public function init()
    {
        $this->formModel = ['class' => CrmBulkAccessForm::class, 'operation' => $this->operation];
        $this->fields = [CrmBulkAccessForm::class, 'formFields'];
        $this->buttons = ['save'];
        $this->accessCallback = static function () {
            $access = CrmAccess::instance();
            $userId = (int)\Yii::$app->user->id;

            return CrmAccess::mainReady() && ($access->isAdmin($userId) || \skeeks\cms\models\CmsDepartment::subordinateIds($userId));
        };
        $this->eachAccessCallback = static fn($company) => CrmAccess::instance()->canManageAccess((int)\Yii::$app->user->id, 'company', (int)$company->id);
        $this->modelsQueryCallback = static function (ActiveQuery $query) {
            CrmAccess::applyClientVisibility($query);

            return $query->distinct();
        };
        $this->applyCallback = static fn($company, CrmBulkAccessForm $form) => $form->apply($company);
        parent::init();
    }

    /** The companies of the selection (the window shows it before the confirmation). */
    public function selectedCount(): int
    {
        $selection = $this->getSelection();

        return $selection ? count((array)$selection['pks']) : 0;
    }
}

<?php

namespace skeeks\cms\crm;

use skeeks\cms\backend\actions\BackendModelMultiWindowAction;
use yii\db\ActiveQuery;

/**
 * A bulk action of the access over the company or client list
 * (CrmBulkAccessForm, wired by CrmClientGrid::bulkActions()): the standard
 * window of the marked rows or «для всех», with the count before the
 * confirmation. Shown to who manages the access (an administrator, a leader);
 * each record is checked by CrmAccess::canManageAccess() — a foreign one (or an
 * employee in the client list) refuses the whole selection.
 */
class CrmBulkAccessAction extends BackendModelMultiWindowAction
{
    /** @var string CrmBulkAccessForm::MAIN|GRANT|PROTECTION */
    public $operation = CrmBulkAccessForm::MAIN;

    /** @var string CmsCrmAccessRequest::RECORD_COMPANY|RECORD_CLIENT */
    public $recordCode = \skeeks\cms\models\CmsCrmAccessRequest::RECORD_COMPANY;

    public $size = 'small';

    public function init()
    {
        $this->formModel = ['class' => CrmBulkAccessForm::class, 'operation' => $this->operation, 'recordCode' => $this->recordCode];
        $this->fields = [CrmBulkAccessForm::class, 'formFields'];
        $this->buttons = ['save'];
        $this->accessCallback = static function () {
            $access = CrmAccess::instance();
            $userId = (int)\Yii::$app->user->id;

            return CrmAccess::mainReady() && ($access->isAdmin($userId) || \skeeks\cms\models\CmsDepartment::subordinateIds($userId));
        };
        $recordCode = (string)$this->recordCode;
        $this->eachAccessCallback = static fn($record) => !($record->hasAttribute('is_worker') && $record->is_worker)
            && CrmAccess::instance()->canManageAccess((int)\Yii::$app->user->id, $recordCode, (int)$record->id);
        $this->modelsQueryCallback = static function (ActiveQuery $query) {
            CrmAccess::applyClientVisibility($query);

            return $query->distinct();
        };
        $this->applyCallback = static fn($record, CrmBulkAccessForm $form) => $form->apply($record);
        parent::init();
    }

    /** The records of the selection (the window shows it before the confirmation). */
    public function selectedCount(): int
    {
        $selection = $this->getSelection();

        return $selection ? count((array)$selection['pks']) : 0;
    }
}

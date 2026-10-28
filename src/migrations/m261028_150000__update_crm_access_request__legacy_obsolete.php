<?php

use skeeks\cms\crm\CrmAccessRequests;
use yii\db\Migration;

/**
 * П2а, owner's decision 28.10.2026: access requests are reduced to one kind,
 * «Работать с этим клиентом» (work). Pending requests of the old kinds
 * («Сделка себе» — delegate, «Доступ к клиенту» — access) become "obsolete":
 * the sender is notified how access is given now, the notifications of the
 * deciders about them are marked read (CrmAccessRequests::closeLegacy()).
 * Decided requests of the old kinds stay as they are, read only.
 */
class m261028_150000__update_crm_access_request__legacy_obsolete extends Migration
{
    public function safeUp()
    {
        $closed = (new CrmAccessRequests())->closeLegacy();
        echo "    > closed legacy access requests: {$closed}\n";
    }

    /**
     * Nothing to undo: the senders were already notified, and a request of an
     * old kind can no longer be decided, so reopening it would leave it hanging.
     */
    public function safeDown()
    {
        return true;
    }
}

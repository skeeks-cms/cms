<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use yii\db\ActiveRecord;

/**
 * One kind of an incoming record of a client (a call, a letter, a chat
 * message) for CrmInbound: how it is found, whose it is, how it is linked to
 * a deal by hand. Registered in `crmInbound.attachers` by its type.
 */
interface CrmInboundAttacherInterface
{
    /** The record by its id, null when there is none. */
    public function find(int $id): ?ActiveRecord;

    /**
     * The clients of the record found by their contacts: companies and
     * persons (not employees).
     *
     * @return ActiveRecord[]
     */
    public function clients(ActiveRecord $inbound): array;

    /** The record lies in this deal already. */
    public function isLinked(ActiveRecord $inbound, CmsLead $lead): bool;

    /** Links the record to the deal (its feed shows it), as the manual linking does. */
    public function attach(ActiveRecord $inbound, CmsLead $lead, int $actorId): void;

    /** «Письмо «Тема»», «Звонок +7 …» — the record in words for the notification and the page. */
    public function describe(ActiveRecord $inbound): string;
}

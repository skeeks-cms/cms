<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsTask;
use yii\base\Event;

/**
 * CmsLead::EVENT_TRIAGED on the sorted deal and LeadTriage::EVENT_LEAD_TRIAGED
 * on the application, after the transaction is committed. Packages move
 * their own links to where the work continues
 * (cms-communication: letters and chats of the request to the target deal
 * or the support task). The core does not depend on them.
 */
class TriageEvent extends Event
{
    /** The sorted deal. */
    public ?CmsLead $lead = null;

    /** CmsLead::TRIAGE_* or "accepted" for a deal taken into work. */
    public string $result = '';

    /** The deal the request was added to (merged). */
    public ?CmsLead $target = null;

    /** The support task (support). */
    public ?CmsTask $task = null;

    public ?int $actorId = null;
}

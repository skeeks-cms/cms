<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use yii\base\Event;

/**
 * «Откуда узнали»: a package names the item of a new deal by evidence the core
 * does not have (a call-tracking number of its telephony, a letter of its
 * mailing). Triggered on the class LeadReferrals (LeadReferrals::EVENT_DETECT)
 * before the built-in rules, only while the deal has no item:
 *
 * - set `referralId` (an item of the deal's site) or `code` (the item code,
 *   for example `mailing`) and a human `note` ("По номеру коллтрекинга …");
 * - `handled = true` without an item stops the built-in rules (the package
 *   knows the item must stay empty).
 *
 * A handler never throws: an error is logged and the deal is saved anyway.
 */
class LeadReferralEvent extends Event
{
    /** The deal, maybe not saved yet. */
    public ?CmsLead $lead = null;

    /** What the rules look at (LeadReferrals::context()): params, referrer, channel, phone. */
    public array $context = [];

    /** The item chosen by the handler. */
    public ?int $referralId = null;

    /** Or its code within the site of the deal. */
    public ?string $code = null;

    /** Why, shown in the card under the value. */
    public string $note = '';
}

<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use yii\base\Event;

/**
 * A package tells where a new request of a source came from, by evidence the
 * core does not know (cms-communication: the visit of a letter link the
 * browser keeps in its cookie). Triggered on the class CmsLeadService inside
 * the transaction of a new request only (a repeated source event returns its
 * deal earlier and asks nobody):
 *
 * - CmsLeadService::EVENT_LEAD_ORIGIN — before routing; `lead` is not saved.
 *   A handler names the client (`companyId`, `clientId`) and who works with
 *   the request when the client has no main responsible (`executorId`), or
 *   an open deal the request goes on in (`continueLead`). InboundQueue
 *   gives the deal to the main responsible of the named client (never
 *   changes him), otherwise to `executorId`; the levels are those of a deal
 *   routed to a found client. Nothing set — the request is routed as usual.
 * - CmsLeadService::EVENT_LEAD_ORIGIN_SAVED — after the request and its
 *   contacts are saved, the same event object: `lead` is the saved request,
 *   `target` the deal where the work goes on (the request itself, or
 *   `continueLead` it was added to). A handler writes its feed entries and
 *   links its records to `target`.
 *
 * A request that goes on in `continueLead` keeps its own row and source
 * reference: it is closed as added to that deal (triage_result=merged,
 * merged_into_id), so the source lookup returns the deal where the work is.
 */
class LeadOriginEvent extends Event
{
    /** The new request (not saved before routing). */
    public ?CmsLead $lead = null;

    /** @var array[] normalized contacts of the request: value, name, sort */
    public array $phones = [];

    /** @var array[] */
    public array $emails = [];

    /** The company the request comes from. */
    public ?int $companyId = null;

    /** The client (a person) the request comes from. */
    public ?int $clientId = null;

    /** Who gets the request when the named client has no main responsible (an active employee with the deals right). */
    public ?int $executorId = null;

    /** Who `executorId` is, for the feed: "сотруднику, отправившему письмо". */
    public string $executorRole = '';

    /** An open deal the request goes on in instead of a new one. */
    public ?CmsLead $continueLead = null;

    /** Tell the responsible of `continueLead` that a request was added (off when the handler already told him). */
    public bool $notifyContinue = true;

    /** Why, for the feed: "Заявка пришла после перехода по ссылке из письма". */
    public string $reason = '';

    /** Data of the handler kept from the first trigger to the second. */
    public array $origin = [];

    /** After saving: the deal where the work goes on. */
    public ?CmsLead $target = null;

    /** The handler named the client or the deal. */
    public function isKnown(): bool
    {
        return $this->continueLead !== null || $this->companyId || $this->clientId;
    }
}

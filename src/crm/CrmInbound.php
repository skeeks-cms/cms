<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use yii\base\Component;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * An incoming letter, call or chat message of a client and its deal (owner's
 * decision 29.10.2026): «Свои сделки» and "edit" see only the records of their
 * deals, so what the client sends must reach the deal.
 *
 * The receiving code (mail, chats in cms-communication, calls in the core)
 * finds the clients by their contacts and asks route():
 * - one open deal of these clients — the record goes to it too (a copy on the
 *   client and a copy on the deal, as before);
 * - several — no guess: the executors of those deals are notified («Клиент
 *   «…» написал — привяжите к своей сделке», kind KIND) with the page of the
 *   manual linking (`admin-cms-lead/attach-inbound`), once per event;
 * - none — the client only (its main responsible and "full" see it).
 * A reply in the same conversation keeps the links of the conversation: the
 * caller does not ask route() then.
 *
 * Kinds of records: `attachers` (type => CrmInboundAttacherInterface), the
 * call of the core here, letters and chat messages from cms-communication.
 */
class CrmInbound extends Component
{
    /** The notification of the executors when the client has several open deals. */
    public const KIND = 'crm.inbound_unlinked';

    /** @var array type => class name or config of a CrmInboundAttacherInterface */
    public array $attachers = [
        'call' => CrmInboundCallAttacher::class,
    ];

    /** @var CrmInboundAttacherInterface[] */
    private array $_attachers = [];

    public static function instance(): self
    {
        if (\Yii::$app->has('crmInbound')) {
            return \Yii::$app->get('crmInbound');
        }
        static $default = null;

        return $default ?? ($default = new self());
    }

    public function attacher(string $type): ?CrmInboundAttacherInterface
    {
        if (!isset($this->_attachers[$type])) {
            if (empty($this->attachers[$type])) {
                return null;
            }
            $attacher = \Yii::createObject($this->attachers[$type]);
            if (!$attacher instanceof CrmInboundAttacherInterface) {
                return null;
            }
            $this->_attachers[$type] = $attacher;
        }

        return $this->_attachers[$type];
    }

    /**
     * The open deals (new, in work) of these clients, without repeats.
     *
     * @param ActiveRecord[] $clients companies and persons
     * @return CmsLead[]
     */
    public function openLeadsOf(array $clients): array
    {
        $companies = [];
        $persons = [];
        foreach ($clients as $client) {
            if ($client instanceof CmsCompany) {
                $companies[] = (int)$client->id;
            } elseif ($client instanceof CmsUser && !$client->is_worker) {
                $persons[] = (int)$client->id;
            }
        }
        if (!$companies && !$persons) {
            return [];
        }
        $table = CmsLead::tableName();

        return CmsLead::find()
            ->andWhere([$table.'.status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]])
            ->andWhere(['or', [$table.'.cms_company_id' => $companies ?: [0]], [$table.'.cms_user_id' => $persons ?: [0]]])
            ->orderBy([$table.'.id' => SORT_ASC])
            ->indexBy('id')
            ->all();
    }

    /**
     * The deal of an incoming record of these clients: the one open deal, or
     * null (none — the client only; several — their executors are notified).
     *
     * @param string $type the attacher type of the record
     * @param string $verb «написал», «позвонил»
     * @param ActiveRecord[] $clients the clients found by the contacts
     * @param string|null $eventKey one notification per event (a chat — per conversation)
     * @param bool $notify false — an old record (a history import): no notification
     */
    public function route(string $type, ActiveRecord $inbound, array $clients, string $verb, ?string $eventKey = null, bool $notify = true): ?CmsLead
    {
        $leads = $this->openLeadsOf($clients);
        if (count($leads) === 1) {
            return reset($leads);
        }
        if (count($leads) > 1 && $notify) {
            $this->notifyExecutors($type, $inbound, $clients, $leads, $verb, $eventKey);
        }

        return null;
    }

    /**
     * One notification per executor (a recipient with two deals of the client
     * gets one) and per event (eventKey, collapse): the deals in the text, the
     * link to the page that links the record to one of them.
     *
     * @param CmsLead[] $leads
     */
    public function notifyExecutors(string $type, ActiveRecord $inbound, array $clients, array $leads, string $verb, ?string $eventKey = null): void
    {
        if (!\Yii::$app->has('notifications')) {
            return;
        }
        $byExecutor = [];
        foreach ($leads as $lead) {
            if ($lead->executor_id) {
                $byExecutor[(int)$lead->executor_id][] = $lead;
            }
        }
        $client = reset($clients);
        $clientName = $client instanceof CmsCompany ? (string)$client->name : ($client instanceof CmsUser ? (string)$client->shortDisplayName : '');
        $url = Url::to(['/cms/admin-cms-lead/attach-inbound', 'type' => $type, 'id' => (int)$inbound->primaryKey]);
        foreach ($byExecutor as $executorId => $own) {
            $numbers = implode(', ', array_map(static fn(CmsLead $lead) => '№'.(int)$lead->id, $own));
            try {
                \Yii::$app->notifications->notify($executorId, self::KIND, [
                    'name' => 'Клиент «'.$clientName.'» '.$verb.' — привяжите к своей сделке ('.$numbers.')',
                    'comment' => $this->attacher($type) ? $this->attacher($type)->describe($inbound) : null,
                    'url' => $url,
                    'eventKey' => mb_substr('crm.inbound:'.($eventKey ?? $type.':'.(int)$inbound->primaryKey), 0, 128),
                    'collapse' => true,
                ]);
            } catch (\Throwable $e) {
                \Yii::error($e, self::class);
            }
        }
    }

    /**
     * The open deals of the record's clients the employee may link it to: his
     * and his subordinates' (CrmAccess::ownOpenLeads()), all for an administrator.
     *
     * @return CmsLead[]
     */
    public function leadsFor($user, string $type, ActiveRecord $inbound): array
    {
        $attacher = $this->attacher($type);
        if (!$attacher) {
            return [];
        }
        $access = CrmAccess::instance();
        $owners = $access->isAdmin($user) ? null : $access->ownerIds($user);
        $result = [];
        foreach ($this->openLeadsOf($attacher->clients($inbound)) as $id => $lead) {
            if ($owners === null || in_array((int)$lead->executor_id, $owners, true)) {
                $result[$id] = $lead;
            }
        }

        return $result;
    }

    /** «Привязать к сделке №…»: only to an open deal of the record's clients the employee works on. */
    public function attach($user, string $type, ActiveRecord $inbound, int $leadId): CmsLead
    {
        $leads = $this->leadsFor($user, $type, $inbound);
        if (!isset($leads[$leadId])) {
            throw new \DomainException('Привязать можно только к своей открытой сделке этого клиента.');
        }
        $attacher = $this->attacher($type);
        if (!$attacher->isLinked($inbound, $leads[$leadId])) {
            $attacher->attach($inbound, $leads[$leadId], (int)(is_object($user) ? $user->id : $user));
        }

        return $leads[$leadId];
    }
}

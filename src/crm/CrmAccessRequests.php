<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsWebNotify;
use yii\helpers\Html;

/**
 * Requests about a client of another main responsible (П2а, owner's decision
 * 28.10.2026, platform-plan crm/links-contract.md, пп. 10–11):
 * - «Работать с этим клиентом» (work): the deal is already the employee's and
 *   he has the level "Свои сделки"; the main responsible (or his leader, an
 *   administrator) raises the level ("Свои сделки и данные компании",
 *   "Полный") or declines — the deal stays with the employee, nothing blocks;
 * - a client with «Доступ только по согласованию»: the same request goes
 *   first, the deal is created for the employee after the approval;
 * - "delegate" / "access" — requests made before that decision: neither
 *   created nor decided any more; the pending ones were closed as "obsolete"
 *   (closeLegacy(), m261028_150000), the cards stay read only.
 *
 * Texts name the employee and the event without a grammatical gender
 * («Петрова Анна: запрос на работу с клиентом …»).
 *
 * A new request notifies the main responsible and his leaders
 * (CrmAccess::requestDeciderIds()); any of them decides, the others see the
 * decision. A repeated request of the same employee joins the pending one.
 * The requester learns the decision with its reason.
 */
class CrmAccessRequests
{
    private CrmAccess $access;

    public function __construct(?CrmAccess $access = null)
    {
        $this->access = $access ?? CrmAccess::instance();
    }

    /**
     * @param int|null $leadId the deal the employee works on; null for «по согласованию» (created after the approval)
     */
    public function request(int $requesterId, string $recordCode, int $recordId, string $kind, ?int $leadId = null, ?string $comment = null, ?string $level = null): CmsCrmAccessRequest
    {
        if ($kind !== CmsCrmAccessRequest::KIND_WORK) {
            throw new \DomainException('Такие запросы больше не отправляются: откройте клиента и нажмите «Работать с этим клиентом».');
        }
        $level = $level ?: CrmAccess::LEVEL_DEAL;
        $pending = CmsCrmAccessRequest::find()->andWhere([
            'requester_id' => $requesterId,
            'record_code' => $recordCode,
            'record_id' => $recordId,
            'kind' => $kind,
            'status' => CmsCrmAccessRequest::STATUS_PENDING,
        ])->one();
        if ($pending) {
            if ($comment && trim($comment) !== '') {
                $pending->comment = trim(trim((string)$pending->comment)."\n".trim($comment));
                $pending->save(false, ['comment', 'updated_at']);
            }
            if ($leadId && !$pending->cms_lead_id) {
                $pending->cms_lead_id = $leadId;
                $pending->save(false, ['cms_lead_id', 'updated_at']);
            }

            return $pending;
        }

        $request = new CmsCrmAccessRequest([
            'cms_site_id' => \Yii::$app->has('skeeks') && \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null,
            'requester_id' => $requesterId,
            'owner_id' => $this->access->mainResponsibleId($recordCode, $recordId),
            'record_code' => $recordCode,
            'record_id' => $recordId,
            'kind' => $kind,
            'cms_lead_id' => $leadId,
            'comment' => $comment,
        ]);
        if ($request->hasAttribute('level')) {
            $request->level = $level;
        }
        if (!$request->save()) {
            throw new \DomainException('Не удалось отправить запрос: '.implode('; ', $request->getFirstErrors()));
        }
        CrmAccessLog::write(CrmAccessLog::REQUEST, $recordCode, $recordId, [
            'actor_id' => $requesterId, 'user_id' => $requesterId, 'level_to' => $request->hasAttribute('level') ? $request->level : null,
            'cms_lead_id' => $leadId, 'request_id' => (int)$request->id, 'comment' => $comment,
        ]);

        $recipients = $this->access->requestDeciderIds($request);
        if ($recipients) {
            // The text names the client (deciders see it); the request is the bold link line: a click opens
            // its card in the backend window, with the client, the employee and the deal as links there.
            \Yii::$app->notifications->notify($recipients, 'crm.access_request', [
                'name' => $this->userName($requesterId).$this->requestText($request),
                'comment' => $comment,
                'model' => $request,
                'eventKey' => 'crm.access_request:'.(int)$request->id,
            ]);
        }

        return $request;
    }

    /** «Имя: …» — the event without a grammatical gender. */
    private function requestText(CmsCrmAccessRequest $request): string
    {
        $client = '«'.$this->recordName($request).'»';

        return $request->cms_lead_id
            ? ': работа с клиентом '.$client.' по своей сделке, запрос уровня доступа'
            : ': запрос на работу с клиентом '.$client;
    }

    /**
     * @param string|null $level the level given on approval: deal, edit or full (the decider's choice)
     */
    public function decide(CmsCrmAccessRequest $request, bool $approve, int $actorId, ?string $comment = null, ?string $level = null): string
    {
        if ($request->isLegacy) {
            throw new \DomainException('Это запрос старого вида, решать его больше не нужно: доступ к клиентам теперь выдаётся через «Работать с этим клиентом».');
        }
        if (!$this->access->canDecideRequest($actorId, $request)) {
            throw new \DomainException('Запрос уже решён или его решают основной ответственный клиента, руководитель ответственного или администратор.');
        }
        if ($level !== null && $level !== '' && !isset(CrmAccess::levels()[$level])) {
            throw new \DomainException('Неизвестный уровень доступа.');
        }

        $db = CmsCrmAccessRequest::getDb();
        $transaction = $db->beginTransaction();
        try {
            // Only one decision: the pending row is taken in the database, not by the loaded copy.
            $taken = CmsCrmAccessRequest::updateAll(
                ['status' => $approve ? CmsCrmAccessRequest::STATUS_APPROVED : CmsCrmAccessRequest::STATUS_DECLINED, 'decided_by' => $actorId, 'decided_at' => time()],
                ['id' => (int)$request->id, 'status' => CmsCrmAccessRequest::STATUS_PENDING]
            );
            if (!$taken) {
                throw new \DomainException('Запрос уже решён другим сотрудником. Обновите страницу.');
            }
            if ($approve && $request->hasAttribute('level')) {
                $request->level = $level ?: ($request->level ?: CrmAccess::LEVEL_DEAL);
            }
            $message = $approve ? $this->apply($request, $actorId) : $this->decline($request, $actorId, $comment);
            $request->status = $approve ? CmsCrmAccessRequest::STATUS_APPROVED : CmsCrmAccessRequest::STATUS_DECLINED;
            $request->decided_by = $actorId;
            $request->decided_at = time();
            $request->decision_comment = $comment;
            if (!$request->save()) {
                throw new \DomainException(implode('; ', $request->getFirstErrors()));
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
        CrmAccessLog::write($approve ? CrmAccessLog::REQUEST_APPROVED : CrmAccessLog::REQUEST_DECLINED, (string)$request->record_code, (int)$request->record_id, [
            'actor_id' => $actorId, 'user_id' => (int)$request->requester_id, 'level_to' => $approve ? $request->level : null,
            'cms_lead_id' => $request->cms_lead_id ? (int)$request->cms_lead_id : null, 'request_id' => (int)$request->id, 'comment' => $comment,
        ]);

        // The bold link line: the deal of the request, else the client when the requester sees it;
        // a client hidden from the requester stays in the text, masked as in a match.
        $requesterId = (int)$request->requester_id;
        $record = $request->record;
        $lead = $request->cms_lead_id ? CmsLead::findOne((int)$request->cms_lead_id) : null;
        $model = $lead;
        if (!$model && ($record instanceof CmsCompany ? $this->access->canSeeCompany($requesterId, (int)$record->id)
                : ($record instanceof CmsUser && $this->access->canSeeClient($requesterId, (int)$record->id)))) {
            $model = $record;
        }
        $name = ($approve ? 'Одобрен' : 'Отклонён').' запрос: '.mb_strtolower($request->kindName, 'UTF-8')
            .($approve ? ', уровень «'.$request->levelName.'»' : '');
        if (!$model) {
            $name .= ', клиент «'.$this->recordName($request, $requesterId).'»';
        }
        \Yii::$app->notifications->notify($requesterId, 'crm.access_decided', [
            'name' => $name,
            'comment' => $comment,
            'model' => $model,
            'url' => $model instanceof CmsLead ? null : $this->listUrl(),
            'eventKey' => 'crm.access_decided:'.(int)$request->id,
        ]);

        return $message;
    }

    public function cancel(CmsCrmAccessRequest $request, int $actorId): void
    {
        if ((int)$request->requester_id !== $actorId) {
            throw new \DomainException('Отменить можно только свой запрос, который ещё не решён.');
        }
        // As a decision: only a request still pending in the database, never a loaded copy.
        $taken = CmsCrmAccessRequest::updateAll(
            ['status' => CmsCrmAccessRequest::STATUS_CANCELLED, 'updated_at' => time()],
            ['id' => (int)$request->id, 'status' => CmsCrmAccessRequest::STATUS_PENDING]
        );
        if (!$taken) {
            throw new \DomainException('Запрос уже решён. Обновите страницу.');
        }
        $request->refresh();
        CrmAccessLog::write(CrmAccessLog::REQUEST_CANCELLED, (string)$request->record_code, (int)$request->record_id,
            ['actor_id' => $actorId, 'user_id' => $actorId, 'request_id' => (int)$request->id]);
    }

    /**
     * Closes the pending requests of the old kinds ("delegate", "access") as
     * "obsolete" (m261028_150000, also on production): the sender learns how
     * access is given now, with the client as the link when the sender sees
     * it; the notifications of the deciders about requests of the old kinds
     * are marked read so the bell does not keep them. The menu counter counts
     * only "work" (CrmAccess::requestsCondition()).
     *
     * @return int closed requests
     */
    public function closeLegacy(): int
    {
        $legacy = ['<>', 'kind', CmsCrmAccessRequest::KIND_WORK];
        $closed = 0;
        foreach (CmsCrmAccessRequest::find()->andWhere(['status' => CmsCrmAccessRequest::STATUS_PENDING])->andWhere($legacy)->orderBy(['id' => SORT_ASC])->each() as $request) {
            /** @var CmsCrmAccessRequest $request */
            $taken = CmsCrmAccessRequest::updateAll(
                ['status' => CmsCrmAccessRequest::STATUS_OBSOLETE, 'updated_at' => time()],
                ['id' => (int)$request->id, 'status' => CmsCrmAccessRequest::STATUS_PENDING]
            );
            if (!$taken) {
                continue;
            }
            $closed++;
            CrmAccessLog::write(CrmAccessLog::REQUEST_OBSOLETE, (string)$request->record_code, (int)$request->record_id,
                ['actor_id' => null, 'user_id' => (int)$request->requester_id, 'request_id' => (int)$request->id]);
            $requesterId = (int)$request->requester_id;
            $record = $request->record;
            // By the links of the client, not by forManager(): it needs the web user, the migration runs in the console.
            $visible = $record && ($this->access->isAdmin($requesterId) || array_intersect(
                $record instanceof CmsCompany ? $this->access->companyResponsibleIds((int)$record->id, null) : $this->access->clientResponsibleIds((int)$record->id, null),
                array_merge([$requesterId], array_map('intval', \skeeks\cms\models\CmsDepartment::subordinateIds($requesterId)))
            ));
            $name = $record instanceof CmsCompany || $visible ? $this->recordName($request) : 'контакт из базы';
            if (\Yii::$app->has('notifications')) {
                \Yii::$app->notifications->notify($requesterId, 'crm.access_decided', [
                    'name' => 'Запрос №'.(int)$request->id.' закрыт: доступ к клиентам теперь выдаётся по-новому. Откройте клиента и нажмите «Работать с этим клиентом»'
                        .($visible ? '' : '. Клиент «'.$name.'»'),
                    'model' => $visible ? $record : null,
                    'eventKey' => 'crm.access_obsolete:'.(int)$request->id,
                ]);
            }
        }

        $ids = CmsCrmAccessRequest::find()->select('id')->andWhere($legacy)->column();
        if ($ids) {
            $read = ['is_read' => 1];
            if (CmsWebNotify::getTableSchema()->getColumn('is_seen')) {
                $read['is_seen'] = 1;
            }
            CmsWebNotify::updateAll($read, ['event_key' => array_map(static fn($id) => 'crm.access_request:'.(int)$id, $ids)]);
        }

        return $closed;
    }

    private function apply(CmsCrmAccessRequest $request, int $actorId): string
    {
        $requesterId = (int)$request->requester_id;
        $lead = $request->cms_lead_id ? CmsLead::findOne((int)$request->cms_lead_id) : null;

        $level = $request->hasAttribute('level') && $request->level ? (string)$request->level : CrmAccess::LEVEL_DEAL;
        if (CrmAccess::mainReady() && $this->access->mainResponsibleId((string)$request->record_code, (int)$request->record_id) === null && $actorId !== $requesterId) {
            // A client without a main responsible (a protected one left without employees): the one who approves
            // becomes the main one — never the requester, who gets exactly the level chosen.
            $this->access->setMain((string)$request->record_code, (int)$request->record_id, $actorId);
            CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, (string)$request->record_code, (int)$request->record_id, [
                'actor_id' => $actorId, 'user_id' => $actorId, 'level_to' => CrmAccess::LEVEL_FULL, 'request_id' => (int)$request->id,
                'comment' => 'одобрил запрос №'.(int)$request->id.', у клиента не было основного',
            ]);
        }
        $this->access->grantLink((string)$request->record_code, (int)$request->record_id, $requesterId, $level);
        $levelText = mb_strtolower(CrmAccess::levels()[$level] ?? $level, 'UTF-8');
        if ($lead && !$lead->isTerminal && (int)$lead->executor_id !== $requesterId) {
            // A request made for an incoming deal of a client «по согласованию»: the deal goes to the employee.
            $this->giveLead($lead, $requesterId, $actorId, 'Работа с клиентом разрешена, сделка передана сотруднику '.Html::encode($this->userName($requesterId)).'.');
        }
        if (!$lead) {
            // «Доступ только по согласованию»: the deal of the employee is created now.
            $lead = $this->createLead($request, $actorId);
            $request->cms_lead_id = (int)$lead->id;

            return 'Сотруднику '.$this->userName($requesterId).' разрешено работать с клиентом, уровень «'.$levelText.'». Создана сделка №'.(int)$lead->id.'.';
        }

        return 'Сотруднику '.$this->userName($requesterId).' выдан доступ: '.$levelText.'.';
    }

    private function decline(CmsCrmAccessRequest $request, int $actorId, ?string $comment): string
    {
        $lead = $request->cms_lead_id ? CmsLead::findOne((int)$request->cms_lead_id) : null;
        if (!$lead) {
            return 'Запрос отклонён.';
        }
        $reason = $comment ? ': '.Html::encode($comment) : '.';
        if ((int)$lead->executor_id !== (int)$request->requester_id) {
            $lead->addSystemActivity('Запрос сотрудника '.Html::encode($this->userName((int)$request->requester_id))
                .' отклонён'.$reason.' Сделка остаётся у ответственного.', $actorId);

            return 'Запрос отклонён.';
        }
        // «Работать с этим клиентом» declined: the deal stays with the employee, his level stays "Свои сделки".
        $lead->addSystemActivity('Доступ к клиенту не расширен'.$reason
            .' Сделка остаётся у сотрудника '.Html::encode($this->userName((int)$request->requester_id)).' с уровнем «Свои сделки».', $actorId);

        return 'Запрос отклонён. Сделка остаётся у сотрудника с уровнем «Свои сделки».';
    }

    /** The deal of an approved request of a client «по согласованию»: the employee's, linked to the client. */
    private function createLead(CmsCrmAccessRequest $request, int $actorId): CmsLead
    {
        $requesterId = (int)$request->requester_id;
        $record = $request->record;
        $lead = new CmsLead([
            'cms_site_id' => $request->cms_site_id ?: ($record && $record->hasAttribute('cms_site_id') ? $record->cms_site_id : null),
            'name' => mb_substr($record instanceof CmsCompany ? (string)$record->name : 'Сделка по клиенту', 0, 255),
            'description' => $request->comment,
            'executor_id' => $requesterId,
            'status' => CmsLead::STATUS_IN_WORK,
            'source_type' => CmsLead::SOURCE_MANUAL,
            'cms_company_id' => $record instanceof CmsCompany ? (int)$record->id : null,
            'cms_user_id' => $record instanceof CmsUser ? (int)$record->id : null,
        ]);
        if (!$lead->save()) {
            throw new \DomainException('Не удалось создать сделку: '.implode('; ', $lead->getFirstErrors()));
        }
        $lead->addSystemActivity('Сделка создана после одобрения запроса работать с клиентом. Ответственный — '
            .Html::encode($this->userName($requesterId)).'.', $actorId);

        return $lead;
    }

    private function giveLead(CmsLead $lead, int $userId, int $actorId, string $activity): void
    {
        $lead->executor_id = $userId;
        if ($lead->status === CmsLead::STATUS_NEW) {
            $lead->status = CmsLead::STATUS_IN_WORK;
        }
        if (!$lead->save()) {
            throw new \DomainException(implode('; ', $lead->getFirstErrors()));
        }
        $lead->addSystemActivity($activity, $actorId);
    }

    private function listUrl(): ?string
    {
        return \Yii::$app instanceof \yii\web\Application ? \yii\helpers\Url::to(['/cms/admin-cms-crm-access-request/index']) : null;
    }

    /**
     * The name of the client of a request as $viewerId may see it: a company
     * by its name (as in a hidden match), a private person by name only when
     * the viewer sees the person, otherwise "контакт из базы".
     */
    public function recordName(CmsCrmAccessRequest $request, ?int $viewerId = null): string
    {
        $record = $request->record;
        if ($record instanceof CmsCompany) {
            return (string)$record->name;
        }
        if (!$record instanceof CmsUser) {
            return '№'.(int)$request->record_id;
        }
        if ($viewerId !== null && !$this->access->canSeeClient($viewerId, (int)$record->id)) {
            return 'контакт из базы';
        }

        return (string)$record->shortDisplayName;
    }

    private function userName(int $userId): string
    {
        $user = CmsUser::findOne($userId);

        return $user ? (string)$user->shortDisplayName : '—';
    }
}

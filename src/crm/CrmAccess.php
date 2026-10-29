<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsDepartment;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUser2manager;
use skeeks\cms\models\User;
use skeeks\cms\rbac\CmsManager;
use yii\base\Component;

/**
 * Employee rights in the CRM, one place (as DealAccess is for clients).
 *
 * П2а keeps the SQL visibility of lists in the forManager() scopes and puts
 * here the decisions controllers and packages used to repeat themselves:
 * who may sort an unsorted deal, work on it, assign it, what of a client an
 * employee may see ("название + ответственный" only, when it is not his),
 * who decides an access request and who is the default responsible.
 *
 * Usage: CrmAccess::instance() (component `crmAccess` when configured).
 */
class CrmAccess extends Component
{
    /**
     * Levels of an employee's link to a company or a client (П2а, owner's
     * decision 28.10.2026): "deal" — own deals (the card and everything of his
     * deals in it), "edit" — plus the company data (phones, emails, contact
     * persons, addresses, requisites), "full" — everything, accesses included.
     * Exactly one link is the main responsible (`is_main`, always "full").
     * The levels are enforced (П2а+): clientDataLevel(), canEditClientData()
     * and canEditClientRecord() decide every form, contact tab, card button and
     * controller action of the client data.
     */
    public const LEVEL_DEAL = 'deal';
    public const LEVEL_EDIT = 'edit';
    public const LEVEL_FULL = 'full';

    public static function levels(): array
    {
        return [
            self::LEVEL_DEAL => 'Свои сделки',
            self::LEVEL_EDIT => 'Свои сделки и данные компании',
            self::LEVEL_FULL => 'Полный',
        ];
    }

    /** deal < edit < full: a link is raised, never lowered by a grant. */
    public static function levelRank(?string $level): int
    {
        return (int)array_search($level, [null, self::LEVEL_DEAL, self::LEVEL_EDIT, self::LEVEL_FULL], true);
    }

    public static function instance(): self
    {
        if (\Yii::$app->has('crmAccess')) {
            return \Yii::$app->get('crmAccess');
        }
        static $default = null;

        return $default ?? ($default = new self());
    }

    public function isAdmin($user): bool
    {
        $userId = $this->userId($user);

        return $userId > 0 && \Yii::$app->authManager->checkAccess($userId, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS);
    }

    /**
     * An unsorted deal (new, no responsible) is sorted by an employee who sees
     * it: a deal linked to his company or contact (the evidence rule of
     * CmsLeadQuery::forManager), otherwise by an administrator.
     */
    public function canTriage($user, CmsLead $lead): bool
    {
        if ($lead->status !== CmsLead::STATUS_NEW) {
            return false;
        }
        if ($this->isAdmin($user)) {
            return true;
        }
        if ($lead->executor_id) {
            return (int)$lead->executor_id === $this->userId($user);
        }

        return $this->canSeeLead($user, $lead);
    }

    public function canSeeLead($user, CmsLead $lead): bool
    {
        $identity = $this->identity($user);

        return $identity !== null && CmsLead::find()->forManager($identity)
            ->andWhere([CmsLead::tableName().'.id' => (int)$lead->id])->exists();
    }

    /**
     * Who works on a deal — the single rule of every working action (owner's
     * decision 29.10.2026): the stage, comments, editing, "Не дозвонились", the
     * client of the deal, its contacts and requisites, letters and SMS from it,
     * the responsible, reopening. An administrator; the responsible (executor);
     * his leaders (CmsDepartment::leaderIdsOf()); an employee who sees the client
     * of the deal in full (seesWholeClient(): his or a subordinate's link "full",
     * the main responsible). "Свои сделки" and "edit" work only on their own deals.
     * The open/closed state is checked by canWorkLead() and canReopenLead().
     */
    public function isLeadWorker($user, CmsLead $lead): bool
    {
        $userId = $this->userId($user);
        if ($userId <= 0) {
            return false;
        }
        if ($this->isAdmin($user)) {
            return true;
        }
        if ($lead->executor_id && ((int)$lead->executor_id === $userId
                || in_array($userId, CmsDepartment::leaderIdsOf((int)$lead->executor_id), true))) {
            return true;
        }
        if ($lead->cms_company_id
            && self::seesWholeClient($this->wholeClientLevel($user, CmsCrmAccessRequest::RECORD_COMPANY, (int)$lead->cms_company_id))) {
            return true;
        }

        return $lead->cms_user_id
            && self::seesWholeClient($this->wholeClientLevel($user, CmsCrmAccessRequest::RECORD_CLIENT, (int)$lead->cms_user_id));
    }

    /** The refusal of a working action on a deal (isLeadWorker()). */
    public const LEAD_WORK_DENIED = 'С этой сделкой работают её ответственный, его руководитель и сотрудники с полным доступом к клиенту.';

    /** Working actions on an open deal (isLeadWorker()); CmsLead::canBeWorkedBy() is this. */
    public function canWorkLead($user, CmsLead $lead): bool
    {
        return !$lead->isTerminal && $this->isLeadWorker($user, $lead);
    }

    /** Returning a closed deal to work (isLeadWorker()); CmsLead::canBeReopenedBy() is this. */
    public function canReopenLead($user, CmsLead $lead): bool
    {
        return $lead->isTerminal && $this->isLeadWorker($user, $lead);
    }

    /**
     * Who may give a deal to another employee: who works on it (isLeadWorker());
     * for an unsorted deal — whoever may sort it.
     */
    public function canAssignLead($user, CmsLead $lead): bool
    {
        if ($lead->isTerminal) {
            return false;
        }
        if ($this->isAdmin($user)) {
            return true;
        }
        if ($lead->status === CmsLead::STATUS_NEW && !$lead->executor_id) {
            return $this->canTriage($user, $lead);
        }

        return $this->isLeadWorker($user, $lead);
    }

    public function canSeeCompany($user, int $companyId): bool
    {
        $identity = $this->identity($user);

        return $identity !== null && $companyId > 0 && CmsCompany::find()->forManager($identity)
            ->andWhere([CmsCompany::tableName().'.id' => $companyId])->exists();
    }

    public function canSeeClient($user, int $clientId): bool
    {
        $identity = $this->identity($user);

        return $identity !== null && $clientId > 0 && CmsUser::find()->forManager($identity)
            ->andWhere([CmsUser::tableName().'.id' => $clientId])->exists();
    }

    /**
     * Responsible employees of the company: links with the level "full", the
     * main responsible first, then in the order they were added. $level = null —
     * every link (any level).
     */
    public function companyResponsibleIds(int $companyId, ?string $level = self::LEVEL_FULL): array
    {
        return $this->linkedIds(CmsCrmAccessRequest::RECORD_COMPANY, $companyId, $level);
    }

    public function clientResponsibleIds(int $clientId, ?string $level = self::LEVEL_FULL): array
    {
        return $this->linkedIds(CmsCrmAccessRequest::RECORD_CLIENT, $clientId, $level);
    }

    private function linkedIds(string $recordCode, int $recordId, ?string $level): array
    {
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        $order = self::mainReady() ? ['m.is_main' => SORT_DESC, 'm.id' => SORT_ASC] : ['m.id' => SORT_ASC];
        $query = $class::find()->alias('m')
            ->innerJoin(CmsUser::tableName().' u', 'u.id = m.'.$userColumn)
            ->select('m.'.$userColumn)
            ->andWhere(['m.'.$recordColumn => $recordId, 'u.is_active' => 1])
            ->orderBy($order);
        if ($level !== null && self::levelsReady()) {
            $query->andWhere(['m.access_level' => $level]);
        }

        return array_map('intval', $query->column());
    }

    /** @return array [link model class, record column, employee column] */
    public static function linkTable(string $recordCode): array
    {
        return $recordCode === CmsCrmAccessRequest::RECORD_COMPANY
            ? [CmsCompany2manager::class, 'cms_company_id', 'cms_user_id']
            : [CmsUser2manager::class, 'client_id', 'worker_id'];
    }

    /**
     * The main responsible of a company or a client: approves requests, gets
     * the notifications about the client, is shown first. Null — nobody.
     */
    public function mainResponsibleId(string $recordCode, int $recordId): ?int
    {
        if (!self::mainReady()) {
            $ids = $this->linkedIds($recordCode, $recordId, self::LEVEL_FULL);

            return $ids[0] ?? null;
        }
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        $id = $class::find()->alias('m')
            ->innerJoin(CmsUser::tableName().' u', 'u.id = m.'.$userColumn)
            ->andWhere(['m.'.$recordColumn => $recordId, 'm.is_main' => 1, 'u.is_active' => 1])
            ->select('m.'.$userColumn)->scalar();

        return $id ? (int)$id : null;
    }

    /**
     * Makes the employee the main responsible ("full"); the former main keeps
     * his link with the level $previousLevel (null — unchanged).
     */
    public function setMain(string $recordCode, int $recordId, int $userId, ?string $previousLevel = null): void
    {
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        if (!self::mainReady()) {
            $this->grantLink($recordCode, $recordId, $userId, self::LEVEL_FULL);

            return;
        }
        // The link first, then the mark moves; the automatic choice of ensureMain() waits (it would make the
        // acting administrator the main one of a client without a main).
        $this->settingMain = true;
        try {
            $this->grantLink($recordCode, $recordId, $userId, self::LEVEL_FULL);
        } finally {
            $this->settingMain = false;
        }
        $condition = ['and', [$recordColumn => $recordId, 'is_main' => 1], ['<>', $userColumn, $userId]];
        if ($previousLevel !== null) {
            $class::updateAll(['is_main' => 0, 'access_level' => $previousLevel], $condition);
        } else {
            $class::updateAll(['is_main' => 0], $condition);
        }
        $class::updateAll(['is_main' => 1, 'access_level' => self::LEVEL_FULL], [$recordColumn => $recordId, $userColumn => $userId]);
    }

    /** @var bool setMain() is moving the mark: ensureMain() does not choose meanwhile */
    private bool $settingMain = false;

    /**
     * Participants only with the main responsible (owner's decision 29.10.2026):
     * a client with employees always has a main one, a client without a main
     * one is the general pool without participants. Called after the links
     * change (a link model saved or deleted, the managers of a form saved).
     *
     * - A participant with "full" (the responsible of an import, the API, a
     *   campaign, the form).
     * - A participant added with "deal"/"edit" to a client without a main one
     *   (a link saved): an administrator or a head of a department who adds
     *   him becomes the main one himself, the employee keeps the level chosen
     *   («сделать основным» — setMain() of the employee instead).
     * - Otherwise, and when the main one left (his link deleted): a head of the
     *   department of the former main one (or of a participant), else the
     *   administrator by default (CrmProtection::HISTORY_FALLBACK_USER_ID, else
     *   the first one).
     *
     * @param int|null $formerMainId the main one whose link was just deleted
     */
    public function ensureMain(string $recordCode, int $recordId, bool $added = false, ?int $formerMainId = null): void
    {
        if (!self::mainReady() || $recordId <= 0 || $this->settingMain) {
            return;
        }
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        if ($class::find()->andWhere([$recordColumn => $recordId, 'is_main' => 1])->exists()) {
            return;
        }
        $links = $class::find()->andWhere([$recordColumn => $recordId])->orderBy(['id' => SORT_ASC])->all();
        // An employee is not a client: his record has no main responsible.
        if (!$links || ($recordCode === CmsCrmAccessRequest::RECORD_CLIENT
                && (int)CmsUser::find()->select('is_worker')->andWhere(['id' => $recordId])->scalar())) {
            return;
        }
        $mainId = null;
        $source = '';
        $actorId = \Yii::$app->has('user') && \Yii::$app->user instanceof \yii\web\User ? (int)\Yii::$app->user->id : 0;
        // A participant "full" first: the responsible of an import, of the API, of a campaign is meant as the main one.
        if (self::levelsReady()) {
            foreach ($links as $link) {
                if ($link->access_level === self::LEVEL_FULL) {
                    [$mainId, $source] = [(int)$link->{$userColumn}, 'участник с полным доступом'];
                    break;
                }
            }
        }
        if (!$mainId && $added && $actorId && ($this->isAdmin($actorId) || $this->isDepartmentHead($actorId))) {
            [$mainId, $source] = [$actorId, 'добавил первого участника'];
        }
        if (!$mainId) {
            foreach (array_filter([$formerMainId, (int)$links[0]->{$userColumn}]) as $userId) {
                if ($heads = CmsDepartment::leaderIdsOf((int)$userId)) {
                    [$mainId, $source] = [(int)$heads[0], 'руководитель отдела'];
                    break;
                }
            }
        }
        if (!$mainId) {
            $fallback = (int)CrmProtection::HISTORY_FALLBACK_USER_ID;
            $mainId = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $fallback, CmsUser::tableName().'.is_active' => 1])->exists()
                ? $fallback : ($this->adminIds()[0] ?? null);
            $source = 'администратор по умолчанию';
        }
        if (!$mainId) {
            return;
        }
        $this->setMain($recordCode, $recordId, $mainId);
        CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, $recordCode, $recordId, [
            'actor_id' => $actorId ?: null, 'user_id' => $mainId, 'level_to' => self::LEVEL_FULL,
            'comment' => 'основной назначен автоматически: '.$source,
        ]);
    }

    /**
     * A new CRM client always has the main responsible (owner's decision
     * 29.10.2026, п. 13а): the links given with it (a form, an import, the
     * API, a campaign — ensureMain() makes one of them the main) or else the
     * employee who creates it — the author (`created_by`), else the current
     * employee (a web or API session). A company without both (a console job)
     * gets the administrator by default. A person who registered on the site
     * himself (no employee creates him) is a user of the site, not a CRM
     * client (п. 14): he gets nobody.
     */
    public function assignCreatorMain(\yii\db\BaseActiveRecord $record, string $recordCode): void
    {
        if (!self::mainReady() || self::isWorkerRecord($record) || $this->mainResponsibleId($recordCode, (int)$record->id) !== null) {
            return;
        }
        [$class, $recordColumn] = self::linkTable($recordCode);
        if ($class::find()->andWhere([$recordColumn => (int)$record->id])->exists()) {
            $this->ensureMain($recordCode, (int)$record->id);

            return;
        }
        $creatorId = $record->hasAttribute('created_by') ? (int)$record->created_by : 0;
        if (!$creatorId || $creatorId === (int)$record->id) {
            $worker = self::actingWorker();
            $creatorId = $worker ? (int)$worker->id : 0;
        }
        $isWorker = $creatorId && CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $creatorId])->exists();
        if (!$isWorker) {
            if ($recordCode !== CmsCrmAccessRequest::RECORD_COMPANY) {
                // A user of the site (п. 14): not a CRM client until an employee or an order makes him one.
                return;
            }
            $fallback = (int)CrmProtection::HISTORY_FALLBACK_USER_ID;
            $creatorId = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $fallback, CmsUser::tableName().'.is_active' => 1])->exists()
                ? $fallback : (int)($this->adminIds()[0] ?? 0);
            if (!$creatorId) {
                return;
            }
        }
        $this->setMain($recordCode, (int)$record->id, $creatorId);
        // The code that creates the client may give its responsible right after (an import, the API, a campaign,
        // the deal form): that link takes the place of this one (onLinkInserted()).
        self::$autoMain[$recordCode.':'.(int)$record->id] = $creatorId;
        CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, $recordCode, (int)$record->id, [
            'actor_id' => $isWorker ? $creatorId : null, 'user_id' => $creatorId, 'level_to' => self::LEVEL_FULL,
            'comment' => $isWorker ? 'создал клиента' : 'создан без сотрудника — администратор по умолчанию',
        ]);
    }

    /** @var int[] "code:id" => the employee assignCreatorMain() made the main one in this process */
    private static array $autoMain = [];

    /** The link of the employee is the one assignCreatorMain() has just made (a repeated insert merges into it). */
    public static function isAutoCreatorLink(string $recordCode, int $recordId, int $userId): bool
    {
        return (self::$autoMain[$recordCode.':'.$recordId] ?? null) === $userId;
    }

    /** The creating code gave the creator himself (a merged insert): he stays the main one. */
    public static function confirmAutoMain(string $recordCode, int $recordId): void
    {
        unset(self::$autoMain[$recordCode.':'.$recordId]);
    }

    /**
     * A link inserted right after the client was created: the responsible the
     * creating code gives (an import «я / сотрудник», the API `manager_ids`, a
     * campaign, the deal form) — with "full" it becomes the main one instead of
     * the creator, whose automatic link goes; the same employee only confirms it.
     */
    public function onLinkInserted(string $recordCode, int $recordId, int $userId, ?string $level): void
    {
        $key = $recordCode.':'.$recordId;
        // A link setMain() inserts itself (ensureMain(), a transfer) is not the responsible of the creating code.
        if (!isset(self::$autoMain[$key]) || $this->settingMain) {
            return;
        }
        $creatorId = self::$autoMain[$key];
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        if (!$class::find()->andWhere([$recordColumn => $recordId, $userColumn => $creatorId, 'is_main' => 1])->exists()) {
            // The automatic link is gone or is not the main one any more: nothing to replace.
            unset(self::$autoMain[$key]);

            return;
        }
        if ($userId === $creatorId) {
            // The creating code gave the creator himself: he stays the main one, later links are participants.
            unset(self::$autoMain[$key]);

            return;
        }
        if ($level !== null && $level !== self::LEVEL_FULL) {
            return;
        }
        unset(self::$autoMain[$key]);
        $this->setMain($recordCode, $recordId, $userId);
        $this->removeLink($recordCode, $recordId, $creatorId);
        CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, $recordCode, $recordId, [
            'actor_id' => $creatorId, 'user_id' => $userId, 'level_to' => self::LEVEL_FULL, 'comment' => 'ответственный, заданный при создании',
        ]);
    }

    /**
     * The managers field of a company or client form rewrites its links
     * (RelationalBehavior deletes and inserts them): the levels and the main
     * responsible of the employees who stay are kept, a record keeps a main.
     */
    public static function keepLinks(\yii\db\BaseActiveRecord $record, string $recordCode): void
    {
        // A new client of an employee with «Новые клиенты — всегда по согласованию» (CrmProtection::onCreated()).
        $record->on(\yii\db\BaseActiveRecord::EVENT_AFTER_INSERT, static function () use ($record, $recordCode) {
            self::instance()->assignCreatorMain($record, $recordCode);
            CrmProtection::onCreated($record, $recordCode);
        });
        $snapshot = [];
        $record->on(\yii\db\BaseActiveRecord::EVENT_BEFORE_UPDATE, static function () use ($record, $recordCode, &$snapshot) {
            $snapshot = [];
            if (self::mainReady() && !self::isWorkerRecord($record) && $record->isRelationPopulated('managers')) {
                [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
                foreach ($class::find()->andWhere([$recordColumn => (int)$record->id])->asArray()->all() as $row) {
                    $snapshot[(int)$row[$userColumn]] = ['access_level' => $row['access_level'], 'is_main' => (int)$row['is_main']];
                }
            }
        });
        $restore = static function () use ($record, $recordCode, &$snapshot) {
            if (!self::mainReady() || self::isWorkerRecord($record)) {
                return;
            }
            [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
            foreach ($snapshot as $userId => $values) {
                $class::updateAll($values, [$recordColumn => (int)$record->id, $userColumn => $userId]);
            }
            $snapshot = [];
            self::instance()->ensureMain($recordCode, (int)$record->id);
        };
        $record->on(\yii\db\BaseActiveRecord::EVENT_AFTER_UPDATE, $restore);
        $record->on(\yii\db\BaseActiveRecord::EVENT_AFTER_INSERT, $restore);
    }

    /**
     * The access page of a client (canManageAccess()): the level of an employee
     * who is not the main responsible, set as chosen (raised or lowered).
     */
    public function setLevel(string $recordCode, int $recordId, int $userId, string $level): void
    {
        if (!isset(self::levels()[$level])) {
            throw new \DomainException('Неизвестный уровень доступа.');
        }
        if ($this->mainResponsibleId($recordCode, $recordId) === $userId) {
            throw new \DomainException('У основного ответственного всегда полный доступ. Сменить основного — «Передать клиента».');
        }
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        if (!$class::updateAll(['access_level' => $level], [$recordColumn => $recordId, $userColumn => $userId])) {
            throw new \DomainException('Сотрудник не работает с этим клиентом.');
        }
    }

    /** The access page: an employee stops working with the client (never the main responsible). */
    public function removeLink(string $recordCode, int $recordId, int $userId): void
    {
        if ($this->mainResponsibleId($recordCode, $recordId) === $userId) {
            throw new \DomainException('Основного ответственного нельзя убрать. Сначала передайте клиента другому сотруднику.');
        }
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        $link = $class::findOne([$recordColumn => $recordId, $userColumn => $userId]);
        if ($link) {
            $link->delete();
        }
    }

    /** «Доступ только по согласованию» of a company or a client. */
    public function setApprovalRequired(string $recordCode, int $recordId, bool $required): void
    {
        $record = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne($recordId) : CmsUser::findOne($recordId);
        if (!$record || !$record->hasAttribute('crm_access_by_approval')) {
            throw new \DomainException('Клиент не найден.');
        }
        // П2д: «Авто / Всегда по согласованию» of the protection; the old flag follows for a rollback.
        $record->updateAttributes(['crm_access_by_approval' => $required ? 1 : 0]);
        if (CrmProtection::ready()) {
            (new CrmProtection())->setAlways($recordCode, $recordId, $required);
        }
    }

    /**
     * Employees of a client for its access page: the main responsible first.
     *
     * @return array[] id, name, level, main
     */
    public function accessList(string $recordCode, int $recordId): array
    {
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        $order = self::mainReady() ? ['m.is_main' => SORT_DESC, 'm.id' => SORT_ASC] : ['m.id' => SORT_ASC];
        $rows = $class::find()->alias('m')->andWhere(['m.'.$recordColumn => $recordId])->orderBy($order)->asArray()->all();
        $names = array_column($this->names(array_map(static fn($row) => (int)$row[$userColumn], $rows)), 'name', 'id');
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int)$row[$userColumn],
                'name' => $names[(int)$row[$userColumn]] ?? '№'.(int)$row[$userColumn],
                'level' => (string)($row['access_level'] ?? self::LEVEL_FULL),
                'main' => (bool)($row['is_main'] ?? false),
            ];
        }

        return $result;
    }

    /**
     * The rows of «Доступ» and of the card block «Работают с …»: accessList()
     * (the main responsible first) plus where an access comes from —
     * «основной ответственный», «по своей сделке» ("deal" of an executor of a
     * deal of the client, left by «Работать с этим клиентом»), «выдан вручную».
     */
    public function accessRows(string $recordCode, int $recordId): array
    {
        $dealOwners = array_map('intval', CmsLead::find()->select('executor_id')->distinct()
            ->andWhere([$recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id' => $recordId])
            ->andWhere(['not', ['executor_id' => null]])->column());
        $rows = $this->accessList($recordCode, $recordId);
        foreach ($rows as &$row) {
            $row['origin'] = $row['main'] ? 'основной ответственный'
                : ($row['level'] === self::LEVEL_DEAL && in_array($row['id'], $dealOwners, true) ? 'по своей сделке' : 'выдан вручную');
        }
        unset($row);

        return $rows;
    }

    /** One line of explanation of each level (the add window, the hints). */
    public static function levelHints(): array
    {
        return [
            self::LEVEL_DEAL => 'Карточка клиента, звонки и письма, всё по своим сделкам',
            self::LEVEL_EDIT => 'Плюс правка данных: телефоны, e-mail, люди, адреса, реквизиты',
            self::LEVEL_FULL => 'Плюс все сделки, услуги, счета, платежи и документы клиента, удаление',
        ];
    }

    /** Ready filters of the company and client lists by the main responsible. */
    public static function responsibleFilterItems(): array
    {
        $items = self::mainReady() ? ['my_main' => 'Мои клиенты (основной)', 'no_main' => 'Без основного ответственного',
            'no_responsible' => 'Без сотрудников'] : [];
        if (CrmProtection::ready()) {
            // П2д: the protection of the client.
            foreach (CrmProtection::states() as $state => $name) {
                $items['protection_'.$state] = 'Защита: '.mb_strtolower($name, 'UTF-8');
            }
        }

        return $items;
    }

    /**
     * «Мои клиенты (основной)» — the viewer is the main responsible; «Без
     * ответственного» — no employee works with the client.
     *
     * @param string $idColumn the id column of the listed records, e.g. "cms_company.id"
     */
    public function applyResponsibleFilter(\yii\db\Query $query, string $recordCode, string $value, string $idColumn, int $viewerId): void
    {
        if (!self::mainReady() || !isset(self::responsibleFilterItems()[$value])) {
            return;
        }
        if (strpos($value, 'protection_') === 0) {
            $query->andWhere([substr($idColumn, 0, (int)strrpos($idColumn, '.')).'.crm_protection' => substr($value, strlen('protection_'))]);

            return;
        }
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        $links = $class::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($idColumn)]);
        if ($value === 'my_main') {
            $query->andWhere(['exists', $links->andWhere([$userColumn => $viewerId, 'is_main' => 1])]);
        } elseif ($value === 'no_main') {
            $query->andWhere(['not exists', $links->andWhere(['is_main' => 1])]);
        } else {
            $query->andWhere(['not exists', $links]);
        }
    }

    /**
     * The filters «Основной ответственный» and «Сотрудник работает» of the
     * company and client lists: the employee is the main one, or has any link.
     *
     * @param string $idColumn the id column of the listed records, e.g. "cms_company.id"
     */
    public function applyEmployeeFilter(\yii\db\Query $query, string $recordCode, int $userId, bool $mainOnly, string $idColumn): void
    {
        if ($userId <= 0) {
            return;
        }
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        $links = $class::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($idColumn), $userColumn => $userId]);
        if ($mainOnly && self::mainReady()) {
            $links->andWhere(['is_main' => 1]);
        }
        $query->andWhere(['exists', $links]);
    }

    /** An employee is not a client: his own record has no responsible employees. */
    private static function isWorkerRecord(\yii\db\BaseActiveRecord $record): bool
    {
        return $record->hasAttribute('is_worker') && (bool)$record->is_worker;
    }

    /** The column of the main responsible exists (the migration may run after the code update). */
    public static function mainReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = CmsCompany2manager::getDb()->getTableSchema(CmsCompany2manager::tableName(), true);
            $ready = $schema && $schema->getColumn('is_main') !== null;
        }

        return $ready;
    }

    /**
     * The client is «Под защитой» or «Всегда по согласованию» (П2д,
     * CrmProtection): «Работать с этим клиентом» only sends a request, the deal
     * is created after the approval. Before the П2д migration — the old hand
     * flag «Доступ только по согласованию».
     */
    public function approvalRequired(string $recordCode, int $recordId): bool
    {
        if (!self::mainReady()) {
            return false;
        }
        if (CrmProtection::ready()) {
            return CrmProtection::state($recordCode, $recordId) !== CrmProtection::FREE;
        }
        $record = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne($recordId) : CmsUser::findOne($recordId);

        return $record && $record->hasAttribute('crm_access_by_approval') && (bool)$record->crm_access_by_approval;
    }

    /**
     * A client without the main responsible (owner's decision 29.10.2026, п. 13а:
     * there is no general pool, a CRM client always has a main one): old data
     * with no employee at all. Not an employee; a person of a company with
     * employees is its data. Administrators and heads of departments see it
     * (canSeeOrphan()) and give it a main one; `cms/crm/assign-pool-main` assigns
     * the old ones.
     */
    public function isOrphan(string $recordCode, int $recordId): bool
    {
        if (!self::mainReady() || $recordId <= 0) {
            return false;
        }
        [$class, $recordColumn] = self::linkTable($recordCode);
        if ($recordCode === CmsCrmAccessRequest::RECORD_CLIENT && (int)CmsUser::find()->select('is_worker')->andWhere(['id' => $recordId])->scalar()) {
            return false;
        }
        if ($class::find()->andWhere([$recordColumn => $recordId])->exists()) {
            return false;
        }

        return $recordCode !== CmsCrmAccessRequest::RECORD_CLIENT
            || !self::personCompanyLinks((string)(int)$recordId)->exists();
    }

    /**
     * The history of a client (owner's decision 29.10.2026): a payment, a bill,
     * a service or a deal ever — not counting an incoming deal not sorted yet
     * (STATUS_NEW). Of a person — his personal ones (without a company). The
     * general pool holds only clients without a history (an import, a bot);
     * a client with a history always has a main responsible
     * (CrmProtection::historyMainCandidate()).
     *
     * @param string $idColumn the column with the record id, e.g. "cms_company.id"
     * @param int|null $exceptLeadId a deal not counted (the one being created for the client)
     * @return array a where condition: the record has a history
     */
    public static function historyCondition(string $recordCode, string $idColumn, ?int $exceptLeadId = null): array
    {
        $isCompany = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY;
        $column = $isCompany ? 'cms_company_id' : 'cms_user_id';
        $records = static function (string $table, string $alias, array $extra = []) use ($isCompany, $column, $idColumn): \yii\db\Query {
            $query = (new \yii\db\Query())->select(new \yii\db\Expression('1'))->from([$alias => $table])
                ->andWhere($alias.'.'.$column.' = '.$idColumn)->andWhere($extra);
            if (!$isCompany) {
                $query->andWhere([$alias.'.cms_company_id' => null]);
            }

            return $query;
        };
        $condition = ['or',
            ['exists', $records('{{%cms_deal}}', 'historyService')],
            ['exists', $records('{{%cms_lead}}', 'historyLead', ['and', ['<>', 'historyLead.status', CmsLead::STATUS_NEW],
                $exceptLeadId ? ['<>', 'historyLead.id', $exceptLeadId] : []])],
        ];
        $db = \Yii::$app->db;
        foreach (['{{%shop_payment}}' => 'historyPayment', '{{%shop_bill}}' => 'historyBill'] as $table => $alias) {
            if ($db->getTableSchema($table) !== null) {
                $condition[] = ['exists', $records($table, $alias)];
            }
        }

        return $condition;
    }

    public static function hasHistory(string $recordCode, int $recordId, ?int $exceptLeadId = null): bool
    {
        return $recordId > 0 && (new \yii\db\Query())->andWhere(self::historyCondition($recordCode, (string)(int)$recordId, $exceptLeadId))->exists();
    }

    /**
     * The links of the companies of a person (SQL for EXISTS): a person whose
     * company has employees is not in the general pool, even without his own
     * employee.
     *
     * @param string $personColumn the column with the person id
     */
    public static function personCompanyLinks(string $personColumn): \yii\db\Query
    {
        return (new \yii\db\Query())->select(new \yii\db\Expression('1'))->from(['poolPersonCompany' => '{{%cms_company2user}}'])
            ->innerJoin(['poolCompanyLink' => '{{%cms_company2manager}}'], 'poolCompanyLink.cms_company_id = poolPersonCompany.cms_company_id')
            ->andWhere('poolPersonCompany.cms_user_id = '.$personColumn);
    }

    /** The employee heads a department (cms_department.worker_id). */
    public function isDepartmentHead(int $userId): bool
    {
        return $userId > 0 && CmsDepartment::find()->andWhere(['worker_id' => $userId])->exists();
    }

    /**
     * A client without the main responsible (isOrphan(), old data) is seen by an
     * administrator and the heads of departments (with the right to the
     * section): they give it a main one.
     */
    public function canSeeOrphan($user, string $recordCode, int $recordId): bool
    {
        $userId = $this->userId($user);
        $permission = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms/admin-company' : 'cms/admin-user';

        return $userId > 0 && ($this->isAdmin($userId) || $this->isDepartmentHead($userId))
            && \Yii::$app->authManager->checkAccess($userId, $permission) && $this->isOrphan($recordCode, $recordId);
    }

    /**
     * One visibility of the lists and the search of companies and clients (and
     * the API): what the employee sees; a head of a department also the
     * clients without the main responsible (old data, canSeeOrphan()).
     */
    public static function applyClientVisibility(\yii\db\ActiveQuery $query, $user = null): void
    {
        $viewer = $user ?? (\Yii::$app->has('user') && \Yii::$app->user instanceof \yii\web\User ? \Yii::$app->user->identity : null);
        if (method_exists($query, 'forManagerOrOrphans') && self::mainReady() && $viewer && self::instance()->isDepartmentHead((int)$viewer->id)) {
            $query->forManagerOrOrphans($user);
        } elseif (method_exists($query, 'forManager')) {
            $query->forManager($user);
        }
    }

    /**
     * Who manages the accesses of a client (the flag, levels, removing an
     * employee): the main responsible, his leaders, an administrator.
     */
    public function canManageAccess($user, string $recordCode, int $recordId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }
        $userId = $this->userId($user);
        $mainId = $this->mainResponsibleId($recordCode, $recordId);
        if ($mainId === null) {
            // A client without the main one (old data): a head of a department gives it one (ensureMain()).
            return $this->isDepartmentHead($userId) && $this->isOrphan($recordCode, $recordId);
        }

        return $mainId === $userId || in_array($userId, CmsDepartment::leaderIdsOf($mainId), true);
    }

    /**
     * The level of the employee's own link to a company or a person: "deal",
     * "edit", "full" or null (no link: he may still see it through a subordinate,
     * a deal or as an administrator).
     */
    public function linkLevel($user, string $recordCode, int $recordId): ?string
    {
        $userId = $this->userId($user);
        $query = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY
            ? CmsCompany2manager::find()->andWhere(['cms_company_id' => $recordId, 'cms_user_id' => $userId])
            : CmsUser2manager::find()->andWhere(['client_id' => $recordId, 'worker_id' => $userId]);
        $link = $query->one();
        if (!$link) {
            return null;
        }

        return $link->hasAttribute('access_level') ? (string)$link->access_level : self::LEVEL_FULL;
    }

    /**
     * The levels decide (П2а+). False only for a site that wants the old
     * behaviour back: every level then works as "full".
     */
    public bool $enforceLevels = true;

    /**
     * The level the employee works with a company or a person on: null when he
     * does not see it; "full" for an administrator. A leader takes the best
     * level of his own link and of his subordinates' links, never above it
     * (owner's decision 29.10.2026): a subordinate main or "full" — "full",
     * "edit" — "edit", "deal" — "deal" (his "своё" is then the work of the
     * department). The data of a person (the name, phones, emails) take the
     * best level of the person and of the person's companies (people of a
     * company are its data, owner's decision 29.10.2026); the personal orders
     * of a person are personalLevel().
     */
    public function clientDataLevel($user, string $recordCode, int $recordId): ?string
    {
        $visible = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY
            ? $this->canSeeCompany($user, $recordId)
            : $this->canSeeClient($user, $recordId);
        if (!$visible) {
            return null;
        }
        if (!$this->enforceLevels || $this->isAdmin($user) || !self::levelsReady()) {
            return self::LEVEL_FULL;
        }
        $userId = $this->userId($user);
        $records = [[$recordCode, $recordId]];
        if ($recordCode === CmsCrmAccessRequest::RECORD_CLIENT) {
            foreach (CmsCompany2user::find()->select('cms_company_id')->andWhere(['cms_user_id' => $recordId])->column() as $companyId) {
                $records[] = [CmsCrmAccessRequest::RECORD_COMPANY, (int)$companyId];
            }
        }
        // He and his subordinates (as ownerIds()).
        $ownerIds = array_values(array_unique(array_merge([$userId], array_map('intval', CmsDepartment::subordinateIds($userId)))));
        $best = null;
        foreach ($records as [$code, $id]) {
            // His own link and his subordinates' ones: the best of them, not above.
            foreach ($ownerIds as $ownerId) {
                $level = $this->linkLevel($ownerId, $code, $id);
                if (self::levelRank($level) > self::levelRank($best)) {
                    $best = $level;
                }
            }
        }

        // Visible without an own link or a subordinate's one (the evidence of a deal and the like): as "deal".
        return $best ?? self::LEVEL_DEAL;
    }

    /**
     * The level of the personal orders of a person — services, deals, bills,
     * payments, documents, proposals where the person is the customer without
     * a company (owner's decision 29.10.2026): only the access to the person
     * himself — the best link of the employee and his subordinates to the
     * person, "full" for an administrator. A link to the person's company, even
     * "full", does not open them. Null — no link (the person may still be seen
     * through a company: his data, not his orders).
     */
    public function personalLevel($user, int $clientId): ?string
    {
        if (!$this->canSeeClient($user, $clientId)) {
            return null;
        }
        if (!$this->enforceLevels || $this->isAdmin($user) || !self::levelsReady()) {
            return self::LEVEL_FULL;
        }
        $best = null;
        foreach ($this->ownerIds($user) as $ownerId) {
            $level = $this->linkLevel($ownerId, CmsCrmAccessRequest::RECORD_CLIENT, $clientId);
            if (self::levelRank($level) > self::levelRank($best)) {
                $best = $level;
            }
        }

        return $best;
    }

    /**
     * The level that opens the whole client (seesWholeClient()): of a company —
     * clientDataLevel(); of a person — personalLevel() (his own orders).
     */
    public function wholeClientLevel($user, string $recordCode, int $recordId): ?string
    {
        return $recordCode === CmsCrmAccessRequest::RECORD_CLIENT
            ? $this->personalLevel($user, $recordId)
            : $this->clientDataLevel($user, $recordCode, $recordId);
    }

    /**
     * May the employee change the data of a company or a person — the name,
     * requisites, phones, emails, addresses, links, people: "edit" and "full",
     * an administrator, a leader for the clients of his subordinates. "Свои
     * сделки" sees and contacts the client but changes nothing of it.
     * Responsible employees and levels are canManageAccess().
     */
    public function canEditClientData($user, string $recordCode, int $recordId): bool
    {
        return self::levelRank($this->clientDataLevel($user, $recordCode, $recordId)) >= self::levelRank(self::LEVEL_EDIT);
    }

    /**
     * The same for a model of the client data: a company, a person, or a row
     * with `cms_company_id` / `cms_user_id` (phones, emails, addresses, links,
     * people of a company). An employee or the user himself is not client data:
     * true, their own rules decide.
     *
     * @param \yii\base\Model|array $model a model or [record code, record id]
     */
    public function canEditClientRecord($user, $model): bool
    {
        $record = is_array($model) ? $model : self::clientRecordOf($model);
        if ($record === null) {
            return true;
        }
        [$code, $id] = $record;
        if ($id <= 0) {
            return true;
        }
        if ($code === CmsCrmAccessRequest::RECORD_CLIENT) {
            $person = CmsUser::findOne($id);
            if (!$person || $person->is_worker || $id === $this->userId($user)) {
                return true;
            }
        }

        return $this->canEditClientData($user, $code, $id);
    }

    /**
     * Requisites (CmsContractor) are shared data of every client they belong
     * to: changed only when the employee may edit each of them
     * (canEditClientRecord(): "edit" and above; a client he does not see
     * counts as no right — one visible link never opens shared data) and works
     * on each deal they are attached to. The screen and the API ask here.
     * Requisites of nobody stay open, as before.
     *
     * @param \skeeks\cms\models\CmsContractor|int $contractor
     */
    public function canEditContractor($user, $contractor): bool
    {
        $id = (int)(is_object($contractor) ? $contractor->id : $contractor);
        if ($id <= 0 || $this->isAdmin($user)) {
            return true;
        }
        $records = [];
        foreach (\skeeks\cms\models\CmsCompany2contractor::find()->select('cms_company_id')->andWhere(['cms_contractor_id' => $id])->column() as $companyId) {
            $records[] = [CmsCrmAccessRequest::RECORD_COMPANY, (int)$companyId];
        }
        foreach (\skeeks\cms\models\CmsContractorMap::find()->select('cms_user_id')->andWhere(['cms_contractor_id' => $id])->column() as $clientId) {
            $records[] = [CmsCrmAccessRequest::RECORD_CLIENT, (int)$clientId];
        }
        foreach ($records as $record) {
            if (!$this->canEditClientRecord($user, $record)) {
                return false;
            }
        }
        $leadIds = \skeeks\cms\models\CmsLead2contractor::find()->select('cms_lead_id')->andWhere(['cms_contractor_id' => $id])->column();
        foreach ($leadIds ? CmsLead::find()->andWhere(['id' => $leadIds])->all() : [] as $lead) {
            // A closed deal keeps its requisites for bills: its responsible still edits them.
            if (!$lead->isManagedBy($this->userId($user)) && !$this->canWorkLead($user, $lead)) {
                return false;
            }
        }

        return true;
    }

    /**
     * May the employee add a new phone or email to a company or a person.
     * "edit" and above — anywhere. "Свои сделки" — only from his own deal of
     * this client ($leadId: the contact block of the deal, contacts of the deal
     * copied to the card, the API with cms_lead_id): a deal of the client that
     * he works on. Changing and removing existing ones is canEditClientRecord().
     * The duplicate check works as for everyone.
     *
     * @param \yii\base\Model|array $model a model or [record code, record id]
     * @param int|null $leadId the deal the contact is added from
     */
    public function canAddClientContact($user, $model, ?int $leadId = null): bool
    {
        $record = is_array($model) ? $model : self::clientRecordOf($model);
        if ($record === null || $this->canEditClientRecord($user, $record)) {
            return true;
        }
        if (!$leadId || $this->clientDataLevel($user, $record[0], (int)$record[1]) === null) {
            return false;
        }
        $lead = CmsLead::findOne($leadId);
        $column = $record[0] === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id';

        return $lead && (int)$lead->$column === (int)$record[1] && $lead->canBeWorkedBy($this->userId($user));
    }

    /**
     * Sees the whole client — all deals, services, bills, payments, documents,
     * tasks, proposals and the whole feed: the level "full" only (owner's
     * decision 29.10.2026). "edit" is "Свои сделки" plus changing the client
     * data (canEditClientData()), not more.
     */
    public static function seesWholeClient(?string $level): bool
    {
        return $level === self::LEVEL_FULL;
    }

    /**
     * Companies whose deals the employee does not see except his own: the best
     * link of him and his subordinates is "Свои сделки" or "edit" — not
     * "full" (seesWholeClient(); П2а+, a leader is limited as his best
     * subordinate, clientDataLevel()); "своё" are then the deals of him and his
     * subordinates.
     * Lists of deals and of records tied to deals (proposals) exclude the
     * other deals of these companies; the company itself, its data and its
     * feed stay visible. Null — nothing to exclude (levels off, no migration).
     *
     * @param \skeeks\cms\models\User|int $user
     */
    public function dealOnlyCompanyIds($user): ?\yii\db\ActiveQuery
    {
        if (!$this->enforceLevels || !self::levelsReady()) {
            return null;
        }
        $userId = $this->userId($user);
        $ownerIds = array_values(array_unique(array_merge([$userId], array_map('intval', CmsDepartment::subordinateIds($userId)))));

        return CmsCompany2manager::find()->alias('dealOnly')->select('dealOnly.cms_company_id')
            ->andWhere(['dealOnly.cms_user_id' => $ownerIds, 'dealOnly.access_level' => [self::LEVEL_DEAL, self::LEVEL_EDIT]])
            ->andWhere(['not exists', CmsCompany2manager::find()->alias('wider')->select('wider.id')
                ->andWhere('wider.cms_company_id = dealOnly.cms_company_id')
                ->andWhere(['wider.cms_user_id' => $ownerIds, 'wider.access_level' => self::LEVEL_FULL])]);
    }

    /**
     * Who counts as "the employee himself" in the "своё" rule: he and his
     * subordinates (a leader sees the work of his department).
     *
     * @param \skeeks\cms\models\User|int $user
     * @return int[]
     */
    public function ownerIds($user): array
    {
        $userId = $this->userId($user);

        return array_values(array_unique(array_merge([$userId], array_map('intval', CmsDepartment::subordinateIds($userId)))));
    }

    /**
     * One visibility of the client records that are not deals — services
     * (CmsDeal), bills, payments, documents, tasks, proposals (owner's decision
     * 28.10.2026): a record of a company or a person is seen when the client is
     * seen in full (seesWholeClient(): a link "full" of him or a subordinate;
     * a personal order of a person — without a company — by the access to the
     * person only, personalLevel()), or when the record is
     * "своё" — the $own conditions of the
     * caller (created by him or his subordinates, he is the executor or the
     * responsible, it is tied to his deal). With "Свои сделки" and "edit" the
     * other records of the client are hidden. Not for administrators: the
     * forManager() scopes return before.
     *
     * @param \skeeks\cms\models\User|int $user
     * @param string $table the table (or alias) with cms_company_id and cms_user_id
     * @param array $own extra OR conditions of "своё"
     * @return array a where condition
     */
    public function clientRecordCondition($user, string $table, array $own = [], string $companyColumn = 'cms_company_id', string $userColumn = 'cms_user_id'): array
    {
        $identity = $this->identity($user);
        $companies = CmsCompany::find()->forManager($identity)->select(CmsCompany::tableName().'.id');
        $dealOnly = $this->dealOnlyCompanyIds($user);
        if ($dealOnly === null) {
            return array_merge(['or',
                [$table.'.'.$companyColumn => $companies],
                [$table.'.'.$userColumn => CmsUser::find()->forManager($identity)->select(CmsUser::tableName().'.id')],
            ], $own);
        }
        $companies->andWhere(['not in', CmsCompany::tableName().'.id', $dealOnly]);

        return array_merge(['or',
            [$table.'.'.$companyColumn => $companies],
            // A personal order (no company): the access to the person himself, not to his companies.
            ['and', [$table.'.'.$companyColumn => null], $this->fullClientCondition($user, $table.'.'.$userColumn)],
        ], $own);
    }

    /**
     * People whose personal orders are seen in full (seesWholeClient() of
     * personalLevel() in SQL): a link "full" of the employee or a subordinate
     * to the person. The person's companies do not count.
     */
    private function fullClientCondition($user, string $column): array
    {
        return [$column => CmsUser2manager::find()->select('client_id')
            ->andWhere(['worker_id' => $this->ownerIds($user), 'access_level' => self::LEVEL_FULL])];
    }

    /**
     * The feed of a company or a person for the levels "Свои сделки" and "edit" (П2а+,
     * owner's decision 28.10.2026): the records of the client itself (calls,
     * letters, chats, comments — the communication his deal needs), the
     * records of his own deals and of the tasks tied to them (a proposal writes
     * its events to the feed of its deal or client, it has no feed of its own).
     * Hidden: other deals, services (CmsDeal), projects, bills, payments, tasks
     * and proposals not tied to his deals. Null — no limit ("full", a leader
     * through a "full" subordinate, an administrator, an employee card, or the
     * client is not seen).
     *
     * @param \skeeks\cms\models\CmsCompany|User $record
     * @return array|null a where condition for CmsLog::find()
     */
    public function feedCondition($user, $record): ?array
    {
        $code = self::clientRecordOf($record);
        if ($code === null || ($record instanceof User && $record->is_worker)) {
            return null;
        }
        $level = $this->clientDataLevel($user, $code[0], (int)$code[1]);
        if ($level === null && !$this->isAdmin($user) && $this->isOrphan($code[0], (int)$code[1])) {
            // A client without the main one seen by a head: the client's own records only, until it has one.
            $table = \skeeks\cms\models\CmsLog::tableName();

            return $this->clientOwnFeedCondition($user, $record, $table);
        }
        // A person: his data by his companies too, his orders by the access to him only (personalLevel()).
        if ($level === null || self::seesWholeClient($this->wholeClientLevel($user, $code[0], (int)$code[1]))) {
            return null;
        }
        // «Свои сделки» and "edit" work only through their deals (owner's decision 29.10.2026): the records of
        // his deals (letters, calls, chats, notes linked to them) and their tasks; nothing of the client itself.
        $table = \skeeks\cms\models\CmsLog::tableName();
        $ownLeads = $this->ownLeads($user, $record, false)->select(CmsLead::tableName().'.id');
        $condition = ['or',
            ['and', [$table.'.model_code' => (new CmsLead())->skeeksModelCode], [$table.'.model_id' => $ownLeads]],
            ['and', [$table.'.model_code' => (new \skeeks\cms\models\CmsTask())->skeeksModelCode],
                [$table.'.model_id' => \skeeks\cms\models\CmsTask::find()->select('id')->andWhere(['cms_lead_id' => $ownLeads])]],
        ];
        if ($level === self::LEVEL_FULL) {
            // A person of a company the employee has in full: the person is its data — his own records are seen;
            // his personal orders (deals, services, bills) only through the access to him (personalLevel()).
            $condition[] = $this->clientOwnFeedCondition($user, $record, $table, true);
        }

        return $condition;
    }

    /**
     * The deals of the employee by a company or a person (owner's decision
     * 29.10.2026, one rule for the feed, the card actions and the server
     * checks): the deals the deal scope shows him (forManager()) whose
     * executor is he or his subordinate. Open — new or in work.
     *
     * @param CmsCompany|User $record
     */
    public function ownLeads($user, $record, bool $openOnly = true): \yii\db\ActiveQuery
    {
        $table = CmsLead::tableName();
        $query = CmsLead::find()->forManager($this->identity($user))
            ->andWhere([$table.'.'.($record instanceof CmsCompany ? 'cms_company_id' : 'cms_user_id') => (int)$record->id])
            ->andWhere([$table.'.executor_id' => $this->ownerIds($user)]);
        if ($openOnly) {
            $query->andWhere([$table.'.status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]]);
        }

        return $query;
    }

    /** The open deals of the employee by the client (ownLeads()): the card actions choose among them. */
    public function ownOpenLeads($user, $record): \yii\db\ActiveQuery
    {
        return $this->ownLeads($user, $record, true);
    }

    /** The refusal of an action of a client card without his own deal (canActOnClient()). */
    public const OWN_LEAD_REQUIRED = 'С этим клиентом вы работаете только через свои сделки: выберите свою открытую сделку — действие будет привязано к ней.';

    /**
     * An action of a company or a person card — a note, a task, a proposal, a
     * presentation, a letter, a call, a message (owner's decision 29.10.2026):
     * who sees the whole client ("full", the main one, a leader through a
     * subordinate, an administrator) acts on the client itself; «Свои сделки»
     * and "edit" only through one of their open deals ($leadId, ownOpenLeads()).
     * Employees and records that are not clients are not limited.
     *
     * @param CmsCompany|User $record
     */
    public function canActOnClient($user, $record, ?int $leadId = null): bool
    {
        $code = self::clientRecordOf($record);
        if ($code === null || ($record instanceof User && $record->is_worker) || $this->isAdmin($user)) {
            return true;
        }
        if (self::seesWholeClient($this->wholeClientLevel($user, $code[0], (int)$code[1]))) {
            return true;
        }

        return $leadId > 0 && $this->ownOpenLeads($user, $record)->andWhere([CmsLead::tableName().'.id' => $leadId])->exists();
    }

    /**
     * The employee who acts now (a web or API session), null in the console
     * and for system work (receiving, agents): canActOnClient() checks only him.
     */
    public static function actingWorker(): ?User
    {
        if (!\Yii::$app->has('user') || !(\Yii::$app->user instanceof \yii\web\User) || \Yii::$app->user->isGuest) {
            return null;
        }
        $identity = \Yii::$app->user->identity;

        return $identity instanceof User && $identity->is_worker ? $identity : null;
    }

    /**
     * A record about to be written for a client (a task, a proposal…): the
     * error of canActOnClient() for the acting employee, null when allowed.
     *
     * @param int|null $companyId the client company of the record
     * @param int|null $clientId the client person of the record (without a company)
     */
    public function clientActionError(?int $companyId, ?int $clientId, ?int $leadId): ?string
    {
        $worker = self::actingWorker();
        if (!$worker || !self::levelsReady()) {
            return null;
        }
        $record = $companyId ? CmsCompany::findOne($companyId) : ($clientId ? CmsUser::findOne($clientId) : null);
        if (!$record) {
            return null;
        }

        return $this->canActOnClient($worker, $record, $leadId) ? null : self::OWN_LEAD_REQUIRED;
    }

    /**
     * "Своё" of a record tied to a deal (owner's decision 29.10.2026, «Полноценная
     * работа в рамках своих сделок»): $column is a deal of the employee or of his
     * subordinates (the executor among ownerIds(), as ownLeads()). For the $own
     * conditions of clientRecordCondition(): services, bills, documents, and
     * payments through their bill.
     */
    public function ownLeadCondition($user, string $column, ?string $table = null): array
    {
        // $table — the real table when $column is qualified by an alias.
        if (!self::leadLinkReady($table ?? explode('.', $column)[0])) {
            return ['0=1'];
        }
        return [$column => CmsLead::find()->select(CmsLead::tableName().'.id')
            ->andWhere([CmsLead::tableName().'.executor_id' => $this->ownerIds($user)])];
    }

    /**
     * The new responsible of a deal works with its client (owner's decision
     * 29.10.2026): a change of the responsible, «Разобрать», taking a deal give
     * him "Свои сделки" to the company of the deal (a person without a company —
     * to the person) when he has no link yet; an existing level, the main
     * responsible and the links of others stay. Written to the access journal.
     * Administrators see every client and get no link.
     */
    public function grantLeadExecutorLevel(CmsLead $lead, ?int $actorId = null): void
    {
        $executorId = (int)$lead->executor_id;
        if (!$executorId || !self::levelsReady() || $this->isAdmin($executorId)) {
            return;
        }
        if ($lead->cms_company_id) {
            $record = [CmsCrmAccessRequest::RECORD_COMPANY, (int)$lead->cms_company_id];
        } elseif ($lead->cms_user_id && ($person = CmsUser::findOne((int)$lead->cms_user_id)) && !$person->is_worker) {
            $record = [CmsCrmAccessRequest::RECORD_CLIENT, (int)$lead->cms_user_id];
        } else {
            return;
        }
        if ($this->linkLevel($executorId, $record[0], $record[1]) !== null) {
            return;
        }
        $this->grantLink($record[0], $record[1], $executorId, self::LEVEL_DEAL);
        CrmAccessLog::write(CrmAccessLog::GRANT, $record[0], $record[1], [
            'actor_id' => $actorId ?: null, 'user_id' => $executorId, 'level_to' => self::LEVEL_DEAL,
            'cms_lead_id' => (int)$lead->id, 'comment' => 'ответственный сделки №'.(int)$lead->id,
        ]);
    }

    /** @var bool[] table => it has cms_lead_id */
    private static array $leadLinkReady = [];

    /**
     * The table (cms_deal, shop_bill, shop_document) has its deal column: the
     * code may run before the migrations m261029_190000 of skeeks/cms and cms-shop.
     */
    public static function leadLinkReady(string $table): bool
    {
        $table = trim(str_replace(['{{%', '}}', '`'], '', $table));
        if (!array_key_exists($table, self::$leadLinkReady)) {
            $schema = \Yii::$app->db->getTableSchema('{{%'.$table.'}}');
            self::$leadLinkReady[$table] = $schema !== null && $schema->getColumn('cms_lead_id') !== null;
        }

        return self::$leadLinkReady[$table];
    }

    /**
     * The deal of a record written for a client — a service, a bill, a
     * document (owner's decision 29.10.2026): when the deal is chosen or
     * changed it must be available to the acting employee and belong to the
     * client of the record (a record without a client takes the client of the
     * deal); a new record of a client the employee does not see in full
     * («Свои сделки», "edit") needs his own open deal (canActOnClient(),
     * OWN_LEAD_REQUIRED). The console and system work are not limited.
     *
     * @param \yii\db\BaseActiveRecord $model with cms_company_id and cms_user_id
     */
    public function validateLeadLink(\yii\db\BaseActiveRecord $model, string $attribute = 'cms_lead_id'): void
    {
        $leadId = (int)$model->$attribute ?: null;
        $model->$attribute = $leadId;
        $changed = $model->isNewRecord || $model->isAttributeChanged($attribute, false);
        $worker = self::actingWorker();
        if ($leadId && $changed) {
            $query = CmsLead::find()->andWhere([CmsLead::tableName().'.id' => $leadId]);
            if ($worker) {
                $query->forManager($worker);
            }
            $lead = $query->one();
            if (!$lead) {
                $model->addError($attribute, 'Сделка не найдена или недоступна вам.');
                return;
            }
            if (!$model->cms_company_id && !$model->cms_user_id) {
                $model->cms_company_id = $lead->cms_company_id ? (int)$lead->cms_company_id : null;
                $model->cms_user_id = $lead->cms_company_id ? null : ($lead->cms_user_id ? (int)$lead->cms_user_id : null);
            } elseif ($lead->cms_company_id || $lead->cms_user_id) {
                $sameClient = $model->cms_company_id
                    ? (int)$lead->cms_company_id === (int)$model->cms_company_id
                    : !$lead->cms_company_id && (int)$lead->cms_user_id === (int)$model->cms_user_id;
                if (!$sameClient) {
                    $model->addError($attribute, 'Сделка относится к другому клиенту.');
                    return;
                }
            }
        }
        if (!$changed || !$worker || !self::levelsReady() || !($record = self::clientRecordOf($model))) {
            return;
        }
        // Only a client he sees in part («Свои сделки», "edit"): the rule of the record without CRM access
        // (a buyer of an order he does not see) stays as before.
        if ($this->clientDataLevel($worker, $record[0], (int)$record[1]) === null) {
            return;
        }
        $client = $record[0] === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne((int)$record[1]) : CmsUser::findOne((int)$record[1]);
        if ($client && !$this->canActOnClient($worker, $client, $leadId)) {
            $model->addError($attribute, self::OWN_LEAD_REQUIRED);
        }
    }

    /**
     * The records of the client itself in a limited feed. Entries another
     * record writes there (a proposal of the company — ProposalFeed) are not
     * "the client's own": a log type whose renderer implements
     * CmsLogFeedVisibilityInterface shows only the entries of records the
     * employee sees (a proposal without a deal or of a service is "своё" only).
     */
    private function clientOwnFeedCondition($user, $record, string $table, bool $withComments = false): array
    {
        $condition = ['and', [$table.'.model_code' => $record->skeeksModelCode], [$table.'.model_id' => (int)$record->id]];
        if (!$withComments) {
            // Comments and files of employees about the client (passwords, contracts, briefs) belong to the whole
            // client (owner's decision 29.10.2026): the pool shows the client's own communications only.
            $condition[] = ['or', [$table.'.log_type' => null], ['<>', $table.'.log_type', \skeeks\cms\models\CmsLog::LOG_TYPE_COMMENT]];
        }
        if (!\Yii::$app->has('skeeks')) {
            return $condition;
        }
        $skeeks = \Yii::$app->skeeks;
        $identity = $this->identity($user);
        foreach (array_keys((array)$skeeks->logRenderers) as $logType) {
            $renderer = $skeeks->getLogRenderer($logType);
            if ($renderer instanceof \skeeks\cms\log\CmsLogFeedVisibilityInterface && $identity) {
                $condition[] = ['or',
                    ['not', [$table.'.log_type' => (string)$logType]],
                    $renderer->feedVisibleCondition($identity, $table),
                ];
            }
        }

        return $condition;
    }

    /**
     * Pinning a comment changes what everybody sees first in a card: a deal —
     * who works on it (CmsLead::canBeWorkedBy()), a company or a person — who
     * may edit it (canEditClientRecord()). Other records keep their rules.
     */
    public function canPinLog($user, \skeeks\cms\models\CmsLog $log): bool
    {
        $model = $log->model;
        if ($model instanceof CmsLead) {
            return $model->canBeWorkedBy($this->userId($user));
        }
        if ($model instanceof CmsCompany || ($model instanceof User && !$model->is_worker)) {
            return $this->canEditClientRecord($user, $model);
        }

        return true;
    }

    /** [record code, record id] of a client model or of a row of its data; null — not client data. */
    public static function clientRecordOf($model): ?array
    {
        if ($model instanceof CmsCompany) {
            return [CmsCrmAccessRequest::RECORD_COMPANY, (int)$model->id];
        }
        if ($model instanceof User) {
            return [CmsCrmAccessRequest::RECORD_CLIENT, (int)$model->id];
        }
        if ($model instanceof \yii\db\ActiveRecord) {
            if ($model->hasAttribute('cms_company_id') && $model->cms_company_id) {
                return [CmsCrmAccessRequest::RECORD_COMPANY, (int)$model->cms_company_id];
            }
            if ($model->hasAttribute('cms_user_id') && $model->cms_user_id) {
                return [CmsCrmAccessRequest::RECORD_CLIENT, (int)$model->cms_user_id];
            }
        }

        return null;
    }

    /**
     * Gives the employee a link to a company or a person with a level (an
     * existing link is raised, never lowered).
     */
    public function grantLink(string $recordCode, int $recordId, int $userId, string $level): void
    {
        $model = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY
            ? (CmsCompany2manager::findOne(['cms_company_id' => $recordId, 'cms_user_id' => $userId]) ?: new CmsCompany2manager(['cms_company_id' => $recordId, 'cms_user_id' => $userId]))
            : (CmsUser2manager::findOne(['client_id' => $recordId, 'worker_id' => $userId]) ?: new CmsUser2manager(['client_id' => $recordId, 'worker_id' => $userId]));
        if ($model->hasAttribute('access_level')) {
            if (!$model->isNewRecord && self::levelRank($model->access_level) >= self::levelRank($level)) {
                return;
            }
            $model->access_level = $level;
        }
        if (!$model->save()) {
            throw new \DomainException('Не удалось выдать доступ: '.implode('; ', $model->getFirstErrors()));
        }
    }

    /**
     * Rules of `access_level` for the link models (deal, edit, full). Not safe
     * for forms ("!"): levels change only through grantLink(), the access page
     * of the client and the transfer; empty before the migration (a validator
     * reads the attribute before its `when`).
     */
    public static function levelRules(): array
    {
        if (!self::levelsReady()) {
            return [];
        }

        return [
            [['!access_level'], 'default', 'value' => self::LEVEL_FULL],
            [['!access_level'], 'in', 'range' => array_keys(self::levels())],
        ];
    }

    /** The level columns exist (the migration may run after the code update). */
    public static function levelsReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = CmsCompany2manager::getDb()->getTableSchema(CmsCompany2manager::tableName(), true);
            $ready = $schema && $schema->getColumn('access_level') !== null;
        }

        return $ready;
    }

    /** Responsible of the company, else of the person; empty when nobody. */
    public function responsibleIds(?int $companyId, ?int $clientId): array
    {
        $ids = $companyId ? $this->companyResponsibleIds($companyId) : [];

        return $ids ?: ($clientId ? $this->clientResponsibleIds($clientId) : []);
    }

    /**
     * Who gets a new deal by default: a responsible of the client — the actor
     * when he is one of them, else the first one; without a responsible — the
     * actor (null for system sources: the deal stays unsorted).
     */
    public function defaultExecutorId(?int $companyId, ?int $clientId, ?int $actorId): ?int
    {
        $ids = $this->responsibleIds($companyId, $clientId);
        // An employee linked to the client (any level) takes the deal himself.
        $linked = array_merge(
            $companyId ? $this->companyResponsibleIds($companyId, null) : [],
            $clientId ? $this->clientResponsibleIds($clientId, null) : []
        );
        if ($actorId && in_array($actorId, $linked, true)) {
            return $actorId;
        }

        return $ids ? $ids[0] : ($actorId ?: null);
    }

    /**
     * A company as an employee may see it: everything when it is available to
     * him, otherwise only the name and the responsible.
     *
     * @return array visible, id, name, responsible (list of [id, name])
     */
    public function companyBrief($user, CmsCompany $company): array
    {
        return [
            'visible' => $this->canSeeCompany($user, (int)$company->id),
            'id' => (int)$company->id,
            'name' => (string)$company->name,
            'responsible' => $this->names($this->companyResponsibleIds((int)$company->id)),
        ];
    }

    /**
     * A candidate of CrmMatcher as the viewer may see it. A hidden record:
     * its kind, the company name, responsible employees, the evidence (the
     * viewer's own values) and whether open deals exist — no contacts,
     * letters, amounts or ids of deals.
     */
    public function presentCandidate(array $candidate, bool $visible, User $viewer): array
    {
        $type = (string)$candidate['type'];
        $id = (int)$candidate['id'];
        $result = [
            'type' => $type,
            'id' => $visible ? $id : null,
            'ref' => $this->candidateRef($type, $id),
            'visible' => $visible,
            'strong' => (bool)($candidate['strong'] ?? false),
            'reasons' => array_values($candidate['reasons'] ?? []),
            'title' => null,
            'responsible' => [],
            'hasOpenLeads' => false,
            // A protected client: «Работать с этим клиентом» sends a request first.
            'byApproval' => !$visible && in_array($type, [CrmMatcher::TYPE_COMPANY, CrmMatcher::TYPE_CLIENT], true)
                && $this->approvalRequired($type === CrmMatcher::TYPE_COMPANY ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT, $id),
        ];

        if (in_array($type, [CrmMatcher::TYPE_COMPANY, CrmMatcher::TYPE_CLIENT], true) && CrmProtection::ready()
            && ($record = CrmProtection::record($type === CrmMatcher::TYPE_COMPANY ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT, $id))) {
            // The shield of a protected client (CrmProtection::shieldIcon()); the reason only to who sees the client.
            $result['protection'] = (string)$record->crm_protection ?: CrmProtection::FREE;
            $result['protectionText'] = $result['protection'] === CrmProtection::FREE ? ''
                : CrmProtection::describeRecord($record, $visible && self::seesWholeClient($this->wholeClientLevel($viewer,
                    $type === CrmMatcher::TYPE_COMPANY ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT, $id)));
        }
        if ($type === CrmMatcher::TYPE_COMPANY && ($company = CmsCompany::findOne($id))) {
            $result['title'] = (string)$company->name;
            $result['responsible'] = $this->names($this->companyResponsibleIds($id));
            $result['hasOpenLeads'] = CmsLead::find()->andWhere(['cms_company_id' => $id, 'status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]])->exists();
        } elseif ($type === CrmMatcher::TYPE_CLIENT && ($client = CmsUser::findOne($id))) {
            // A private person's name is personal data: only for those who see the card.
            $result['title'] = $visible ? (string)$client->shortDisplayName : null;
            $result['responsible'] = $this->names($this->clientResponsibleIds($id));
            $result['hasOpenLeads'] = CmsLead::find()->andWhere(['cms_user_id' => $id, 'status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]])->exists();
            if ($visible) {
                $result['companies'] = array_map(static fn(CmsCompany $company) => ['id' => (int)$company->id, 'name' => (string)$company->name],
                    CmsCompany::find()->forManager($viewer)->innerJoin('{{%cms_company2user}} cu', 'cu.cms_company_id = '.CmsCompany::tableName().'.id')
                        ->andWhere(['cu.cms_user_id' => $id])->limit(5)->all());
            }
        } elseif ($type === CrmMatcher::TYPE_LEAD && ($lead = CmsLead::findOne($id))) {
            $result['title'] = $visible ? (string)$lead->name : 'Открытая сделка';
            $result['responsible'] = $lead->executor_id ? $this->names([(int)$lead->executor_id]) : [];
            $result['status'] = (string)$lead->status;
            $result['hasOpenLeads'] = true;
            if ($visible && $lead->cms_company_id) {
                $result['companyId'] = (int)$lead->cms_company_id;
            }
        }

        return $result;
    }

    /**
     * An opaque reference to a hidden candidate: the form sends it back to
     * request the deal or access, without the employee learning the id.
     * Authenticated encryption (Yii Security, random IV): the id cannot be
     * read, guessed or changed; a reference lives one day.
     */
    public function candidateRef(string $type, int $id): string
    {
        $payload = $type.':'.$id.':'.time();
        $encrypted = \Yii::$app->security->encryptByKey($payload, $this->refKey());

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    /** @return array|null [type, id] of a reference made by candidateRef() */
    public function parseCandidateRef(string $ref): ?array
    {
        $binary = base64_decode(strtr($ref, '-_', '+/'), true);
        if ($binary === false || $binary === '') {
            return null;
        }
        $payload = \Yii::$app->security->decryptByKey($binary, $this->refKey());
        if (!is_string($payload)) {
            return null;
        }
        $parts = explode(':', $payload);
        if (count($parts) !== 3 || !in_array($parts[0], [CrmMatcher::TYPE_COMPANY, CrmMatcher::TYPE_CLIENT, CrmMatcher::TYPE_LEAD], true)
            || (int)$parts[2] < time() - 86400) {
            return null;
        }

        return [$parts[0], (int)$parts[1]];
    }

    /**
     * Who decides a request: an administrator, the main responsible of the
     * client and his leaders (isRequestDecider()). Requests of the old kinds
     * are never decided (CmsCrmAccessRequest::getIsLegacy()).
     */
    public function canDecideRequest($user, CmsCrmAccessRequest $request): bool
    {
        return $request->isPending && !$request->isLegacy && $this->isRequestDecider($user, $request);
    }

    /**
     * Who decides a request (also sees decisions of it): an administrator, the
     * main responsible of the client and his leaders. One decision is enough.
     */
    public function isRequestDecider($user, CmsCrmAccessRequest $request): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return in_array($this->userId($user), $this->deciderIds($request), true);
    }

    /** A request is seen by those who decide it and by the one who sent it. */
    public function canSeeRequest($user, CmsCrmAccessRequest $request): bool
    {
        return (int)$request->requester_id === $this->userId($user) || $this->isRequestDecider($user, $request);
    }

    /**
     * The section «Запросы доступа» (its menu item): administrators, main
     * responsible employees and leaders — those who may decide requests.
     */
    public function canUseRequests($user): bool
    {
        if (!self::mainReady()) {
            return false;
        }
        if ($this->isAdmin($user)) {
            return true;
        }
        $userId = $this->userId($user);
        if ($userId <= 0) {
            return false;
        }

        return CmsCompany2manager::find()->andWhere(['cms_user_id' => $userId, 'is_main' => 1])->exists()
            || CmsUser2manager::find()->andWhere(['worker_id' => $userId, 'is_main' => 1])->exists()
            || CmsDepartment::subordinateIds($userId)
            || CmsCrmAccessRequest::find()->andWhere(['requester_id' => $userId])->exists();
    }

    public const REQUESTS_TO_ME = 'to_me';
    public const REQUESTS_MINE = 'mine';
    public const REQUESTS_ALL = 'all';

    /**
     * The condition of the requests list (the same rule as isRequestDecider()):
     * "to_me" — pending requests the user decides (the main responsible of the
     * client is he or his subordinate; an administrator — all), "mine" — sent by
     * him, "all" — both.
     *
     * @return array a where condition for CmsCrmAccessRequest::find()
     */
    public function requestsCondition($user, string $scope): array
    {
        $userId = $this->userId($user);
        $table = CmsCrmAccessRequest::tableName();
        $mine = [$table.'.requester_id' => $userId];
        if ($scope === self::REQUESTS_MINE) {
            return $mine;
        }
        if ($this->isAdmin($user)) {
            $decides = ['not', [$table.'.id' => null]];
        } else {
            $ids = array_values(array_unique(array_merge([$userId], array_map('intval', CmsDepartment::subordinateIds($userId)))));
            // deciderIds() in SQL: he or his subordinate is the current main one, or — a record without a main
            // one — has the level "full" on it.
            $main = static function (string $class, string $recordColumn, string $userColumn, string $code) use ($table, $ids) {
                $links = static fn() => $class::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($table.'.record_id')]);

                return ['and', [$table.'.record_code' => $code], ['or',
                    ['exists', $links()->andWhere(['is_main' => 1, $userColumn => $ids])],
                    ['and', ['not exists', $links()->andWhere(['is_main' => 1])],
                        ['exists', $links()->andWhere([$userColumn => $ids, 'access_level' => self::LEVEL_FULL])]],
                ]];
            };
            $decides = ['or',
                $main(CmsCompany2manager::class, 'cms_company_id', 'cms_user_id', CmsCrmAccessRequest::RECORD_COMPANY),
                $main(CmsUser2manager::class, 'client_id', 'worker_id', CmsCrmAccessRequest::RECORD_CLIENT),
            ];
        }
        if ($scope === self::REQUESTS_TO_ME) {
            return ['and', [$table.'.status' => CmsCrmAccessRequest::STATUS_PENDING, $table.'.kind' => CmsCrmAccessRequest::KIND_WORK],
                ['<>', $table.'.requester_id', $userId], $decides];
        }

        return ['or', $mine, $decides];
    }

    /** The counter of the menu item: pending requests the user decides. */
    public function pendingRequestsCount($user): int
    {
        if (!self::mainReady()) {
            return 0;
        }

        return (int)CmsCrmAccessRequest::find()->andWhere($this->requestsCondition($user, self::REQUESTS_TO_ME))->count();
    }

    /** Recipients of a new request: the main responsible of the client and his leaders. */
    public function requestDeciderIds(CmsCrmAccessRequest $request): array
    {
        $ids = $this->deciderIds($request);
        if (!$ids) {
            // Nobody to decide below: administrators of the site.
            $ids = $this->adminIds();
        }

        return array_values(array_diff($ids, [(int)$request->requester_id]));
    }

    private function deciderIds(CmsCrmAccessRequest $request): array
    {
        // The current main responsible decides; a record without one — its "full" employees. Their leaders
        // too, not the requester's ones. `owner_id` (the main one when the request was sent) does not count:
        // the main one may have changed, and it may be the requester himself (owner's decision 29.10.2026).
        $mainId = $this->mainResponsibleId((string)$request->record_code, (int)$request->record_id);
        $owners = $mainId ? [$mainId] : ($request->record_code === CmsCrmAccessRequest::RECORD_COMPANY
            ? $this->companyResponsibleIds((int)$request->record_id)
            : $this->clientResponsibleIds((int)$request->record_id));
        $ids = $owners;
        foreach (array_unique($owners) as $ownerId) {
            $ids = array_merge($ids, CmsDepartment::leaderIdsOf($ownerId));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Who changes «Новые компании и клиенты, которые я создаю, — „Всегда по
     * согласованию“» of an employee (CrmProtection::onCreated()): he himself,
     * his head, an administrator. The same people see the block «Клиенты в
     * CRM» of his profile.
     */
    public function canEditCrmSettings($actor, int $workerId): bool
    {
        $actorId = $this->userId($actor);

        return $actorId > 0 && $workerId > 0 && ($actorId === $workerId || $this->isAdmin($actorId)
            || in_array($workerId, array_map('intval', CmsDepartment::subordinateIds($actorId)), true));
    }

    /**
     * The nearest head of an employee: the head of his own department, else
     * the first of CmsDepartment::leaderIdsOf() (the default receiver of his
     * clients).
     */
    public function nearestHeadId(int $workerId): ?int
    {
        $own = CmsDepartment::find()->alias('d')
            ->innerJoin(['dw' => \skeeks\cms\models\CmsDepartment2worker::tableName()], 'dw.cms_department_id = d.id')
            ->andWhere(['dw.worker_id' => $workerId])->andWhere(['not', ['d.worker_id' => null]])->andWhere(['<>', 'd.worker_id', $workerId])
            ->select('d.worker_id')->scalar();
        if ($own) {
            return (int)$own;
        }
        $heads = CmsDepartment::leaderIdsOf($workerId);

        return $heads ? (int)$heads[0] : null;
    }

    /**
     * «Передать всех клиентов» of an employee (owner's decision 29.10.2026,
     * п. 13а): an administrator or his head.
     */
    public function canTransferClientsOf($actor, int $workerId): bool
    {
        $actorId = $this->userId($actor);

        return $actorId > 0 && $workerId > 0 && $actorId !== $workerId
            && ($this->isAdmin($actorId) || in_array($workerId, array_map('intval', CmsDepartment::subordinateIds($actorId)), true));
    }

    /** How many clients the employee is the main responsible of. */
    public function mainClientsCount(int $userId): int
    {
        if (!self::mainReady() || $userId <= 0) {
            return 0;
        }

        return (int)CmsCompany2manager::find()->andWhere(['cms_user_id' => $userId, 'is_main' => 1])->count()
            + (int)CmsUser2manager::find()->andWhere(['worker_id' => $userId, 'is_main' => 1])->count();
    }

    /**
     * An employee with clients is not deactivated, made a non-employee or
     * deleted until his clients are given away (owner's decision 29.10.2026,
     * п. 13а): the refusal, null when allowed. User::init() checks every save
     * and delete of an employee record.
     */
    public function deactivationError(int $userId): ?string
    {
        $count = $this->mainClientsCount($userId);

        return $count ? 'У сотрудника есть клиенты, он их основной ответственный ('.$count.'). Сначала передайте их: «Передать всех клиентов» в карточке сотрудника.' : null;
    }

    /**
     * «Передать клиента» — a change of the main responsible, not a request: an
     * administrator or a leader of an employee of the client does it in the
     * company or client card.
     */
    public function canTransferClient($user, string $recordCode, int $recordId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }
        $userId = $this->userId($user);
        $owners = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY
            ? $this->companyResponsibleIds($recordId, null)
            : $this->clientResponsibleIds($recordId, null);
        foreach ($owners as $ownerId) {
            if (in_array($userId, CmsDepartment::leaderIdsOf($ownerId), true)) {
                return true;
            }
        }

        return false;
    }

    public function adminIds(): array
    {
        $ids = [];
        foreach (CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.is_active' => 1])->cmsSite()->select(CmsUser::tableName().'.id')->column() as $id) {
            if (\Yii::$app->authManager->checkAccess((int)$id, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
                $ids[] = (int)$id;
            }
        }

        return $ids;
    }

    /** @return array[] [id, name] */
    public function names(array $userIds): array
    {
        if (!$userIds) {
            return [];
        }
        $users = CmsUser::find()->andWhere(['id' => $userIds])->indexBy('id')->all();
        $result = [];
        foreach ($userIds as $id) {
            if (isset($users[$id])) {
                $result[] = ['id' => (int)$id, 'name' => (string)$users[$id]->shortDisplayName];
            }
        }

        return $result;
    }

    private function refKey(): string
    {
        $key = \Yii::$app->has('request') && \Yii::$app->request instanceof \yii\web\Request
            ? (string)\Yii::$app->request->cookieValidationKey
            : '';

        return 'crm-candidate|'.($key !== '' ? $key : (string)\Yii::$app->id);
    }

    private function userId($user): int
    {
        if ($user instanceof \yii\db\BaseActiveRecord || $user instanceof \yii\web\IdentityInterface) {
            return (int)$user->id;
        }

        return (int)$user;
    }

    private function identity($user): ?User
    {
        if ($user instanceof User) {
            return $user;
        }
        $id = $this->userId($user);

        return $id ? CmsUser::findOne($id) : null;
    }
}

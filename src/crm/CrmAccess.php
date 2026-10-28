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

    /** Working actions: the responsible of an open deal or an administrator. */
    public function canWorkLead($user, CmsLead $lead): bool
    {
        return $lead->canBeWorkedBy($this->userId($user));
    }

    /**
     * Who may give a deal to another employee: an administrator, the
     * responsible, his leaders, a responsible of the deal's company; for an
     * unsorted deal — whoever may sort it.
     */
    public function canAssignLead($user, CmsLead $lead): bool
    {
        if ($lead->isTerminal) {
            return false;
        }
        $userId = $this->userId($user);
        if ($this->isAdmin($user)) {
            return true;
        }
        if ($lead->status === CmsLead::STATUS_NEW && !$lead->executor_id) {
            return $this->canTriage($user, $lead);
        }
        if ($lead->executor_id && ((int)$lead->executor_id === $userId
                || in_array($userId, CmsDepartment::leaderIdsOf((int)$lead->executor_id), true))) {
            return true;
        }

        return $lead->cms_company_id && in_array($userId, $this->companyResponsibleIds((int)$lead->cms_company_id), true);
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
        // The link first (its save keeps the current main, ensureMain()), then the mark moves.
        $this->grantLink($recordCode, $recordId, $userId, self::LEVEL_FULL);
        $condition = ['and', [$recordColumn => $recordId, 'is_main' => 1], ['<>', $userColumn, $userId]];
        if ($previousLevel !== null) {
            $class::updateAll(['is_main' => 0, 'access_level' => $previousLevel], $condition);
        } else {
            $class::updateAll(['is_main' => 0], $condition);
        }
        $class::updateAll(['is_main' => 1, 'access_level' => self::LEVEL_FULL], [$recordColumn => $recordId, $userColumn => $userId]);
    }

    /**
     * A record with links always has a main responsible: the author of the
     * record when he is linked, otherwise the earliest link. Called after the
     * links change (a link model saved or deleted, the managers of a form saved).
     */
    public function ensureMain(string $recordCode, int $recordId): void
    {
        if (!self::mainReady() || $recordId <= 0) {
            return;
        }
        [$class, $recordColumn, $userColumn] = self::linkTable($recordCode);
        if ($class::find()->andWhere([$recordColumn => $recordId, 'is_main' => 1])->exists()) {
            return;
        }
        $record = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne($recordId) : CmsUser::findOne($recordId);
        $link = null;
        if ($record && $record->created_by) {
            $link = $class::find()->andWhere([$recordColumn => $recordId, $userColumn => (int)$record->created_by])->one();
        }
        $link = $link ?: $class::find()->andWhere([$recordColumn => $recordId])->orderBy(['id' => SORT_ASC])->one();
        if ($link) {
            $class::updateAll(['is_main' => 1, 'access_level' => self::LEVEL_FULL], ['id' => (int)$link->id]);
        }
    }

    /**
     * The managers field of a company or client form rewrites its links
     * (RelationalBehavior deletes and inserts them): the levels and the main
     * responsible of the employees who stay are kept, a record keeps a main.
     */
    public static function keepLinks(\yii\db\BaseActiveRecord $record, string $recordCode): void
    {
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
            self::LEVEL_FULL => 'Всё, включая удаление клиента',
        ];
    }

    /** Ready filters of the company and client lists by the main responsible. */
    public static function responsibleFilterItems(): array
    {
        $items = self::mainReady() ? ['my_main' => 'Мои клиенты (основной)', 'no_responsible' => 'Без ответственного'] : [];
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
        } else {
            $query->andWhere(['not exists', $links]);
        }
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
     * «Взять в работу» (П2д): a free client without a main responsible — the
     * general pool; the first employee who takes it becomes the main one
     * (`full`), no request and no notification.
     */
    public function canTakeIntoWork(string $recordCode, int $recordId): bool
    {
        return self::mainReady() && $recordId > 0 && $this->mainResponsibleId($recordCode, $recordId) === null
            && !$this->approvalRequired($recordCode, $recordId);
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

        return $mainId !== null && ($mainId === $userId || in_array($userId, CmsDepartment::leaderIdsOf($mainId), true));
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
     * does not see it; "full" for an administrator and for a leader who sees it
     * through a subordinate. A person without an own link takes the best level
     * of the employee's links to the person's companies (people of a company
     * are its data).
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
        if ($recordCode === CmsCrmAccessRequest::RECORD_CLIENT && $this->linkLevel($userId, $recordCode, $recordId) === null) {
            foreach (CmsCompany2user::find()->select('cms_company_id')->andWhere(['cms_user_id' => $recordId])->column() as $companyId) {
                $records[] = [CmsCrmAccessRequest::RECORD_COMPANY, (int)$companyId];
            }
        }
        $subordinates = array_map('intval', CmsDepartment::subordinateIds($userId));
        $best = null;
        foreach ($records as [$code, $id]) {
            // A client of a subordinate: the leader works on it without limits.
            if ($subordinates && array_intersect($this->linkedIds($code, $id, null), $subordinates)) {
                return self::LEVEL_FULL;
            }
            $level = $this->linkLevel($userId, $code, $id);
            if (self::levelRank($level) > self::levelRank($best)) {
                $best = $level;
            }
        }

        // Visible without an own link or a subordinate's one (the evidence of a deal and the like): as "deal".
        return $best ?? self::LEVEL_DEAL;
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
     * Companies whose deals the employee does not see except his own: his link
     * is "Свои сделки" and no subordinate of his works on the company (П2а+).
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
        $query = CmsCompany2manager::find()->alias('dealOnly')->select('dealOnly.cms_company_id')
            ->andWhere(['dealOnly.cms_user_id' => $userId, 'dealOnly.access_level' => self::LEVEL_DEAL]);
        $subordinates = array_map('intval', CmsDepartment::subordinateIds($userId));
        if ($subordinates) {
            $query->andWhere(['not exists', CmsCompany2manager::find()->alias('bySubordinate')->select('bySubordinate.id')
                ->andWhere('bySubordinate.cms_company_id = dealOnly.cms_company_id')
                ->andWhere(['bySubordinate.cms_user_id' => $subordinates])]);
        }

        return $query;
    }

    /**
     * The feed of a company or a person for the level "Свои сделки" (П2а+,
     * owner's decision 28.10.2026): the records of the client itself (calls,
     * letters, chats, comments — the communication his deal needs), the
     * records of his own deals and of the tasks tied to them (a proposal writes
     * its events to the feed of its deal or client, it has no feed of its own).
     * Hidden: other deals, services (CmsDeal), projects, bills, payments, tasks
     * and proposals not tied to his deals. Null — no limit ("edit", "full", a
     * leader, an administrator, an employee card, or the client is not seen).
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
        if ($level === null || self::levelRank($level) >= self::levelRank(self::LEVEL_EDIT)) {
            return null;
        }
        $table = \skeeks\cms\models\CmsLog::tableName();
        $identity = $this->identity($user);
        $ownLeads = CmsLead::find()->forManager($identity)->select(CmsLead::tableName().'.id')
            ->andWhere([CmsLead::tableName().($record instanceof CmsCompany ? '.cms_company_id' : '.cms_user_id') => (int)$record->id]);
        $condition = ['or',
            ['and', [$table.'.model_code' => $record->skeeksModelCode], [$table.'.model_id' => (int)$record->id]],
            ['and', [$table.'.model_code' => (new CmsLead())->skeeksModelCode], [$table.'.model_id' => $ownLeads]],
            ['and', [$table.'.model_code' => (new \skeeks\cms\models\CmsTask())->skeeksModelCode],
                [$table.'.model_id' => \skeeks\cms\models\CmsTask::find()->select('id')->andWhere(['cms_lead_id' => $ownLeads])]],
        ];
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
            // A free client without a main responsible (the general pool): «Взять в работу».
            'canTake' => !$visible && in_array($type, [CrmMatcher::TYPE_COMPANY, CrmMatcher::TYPE_CLIENT], true)
                && $this->canTakeIntoWork($type === CrmMatcher::TYPE_COMPANY ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT, $id),
        ];

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
            $main = static function (string $class, string $recordColumn, string $userColumn, string $code) use ($table, $ids) {
                return ['and', [$table.'.record_code' => $code], ['exists', $class::find()->select('id')->andWhere([
                    $recordColumn => new \yii\db\Expression($table.'.record_id'), 'is_main' => 1, $userColumn => $ids,
                ])]];
            };
            $decides = ['or',
                $main(CmsCompany2manager::class, 'cms_company_id', 'cms_user_id', CmsCrmAccessRequest::RECORD_COMPANY),
                $main(CmsUser2manager::class, 'client_id', 'worker_id', CmsCrmAccessRequest::RECORD_CLIENT),
                [$table.'.owner_id' => $ids],
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
        // The main responsible decides; a record without one — its "full" employees.
        $mainId = $this->mainResponsibleId((string)$request->record_code, (int)$request->record_id);
        $owners = $mainId ? [$mainId] : ($request->record_code === CmsCrmAccessRequest::RECORD_COMPANY
            ? $this->companyResponsibleIds((int)$request->record_id)
            : $this->clientResponsibleIds((int)$request->record_id));
        if ($request->owner_id) {
            $owners[] = (int)$request->owner_id;
        }
        $ids = $owners;
        foreach (array_unique($owners) as $ownerId) {
            $ids = array_merge($ids, CmsDepartment::leaderIdsOf($ownerId));
        }

        return array_values(array_unique(array_map('intval', $ids)));
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

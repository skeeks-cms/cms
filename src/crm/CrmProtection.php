<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUser2manager;

/**
 * П2д, owner's decision 28.10.2026 (platform-plan crm/links-contract.md п. 13):
 * the protection of a company or a client person instead of the hand flag
 * «Доступ только по согласованию».
 *
 * - «Свободная» — no payment for the last N months (crmSettings, 12 by
 *   default) and no active service: without a main responsible — «Взять в
 *   работу» (the employee becomes the main one); with one — «Работать с этим
 *   клиентом» (the deal at once, "Свои сделки", a notification);
 * - «Под защитой» — such a payment or an active service (CmsDeal): a request
 *   to the main responsible, the deal after the approval;
 * - «Всегда по согласованию» — by hand, never expires.
 *
 * Kept on the record (`crm_protection`, `crm_protected_until`,
 * `crm_protection_reason`), recalculated on a payment and on a change of a
 * service (Skeeks::bootstrap()), and once a day by the agent
 * `cms/crm/protection` (the expired ones). An open deal of another employee
 * never protects. The first payment of a company without a main responsible
 * makes the responsible of its bill (else who registered the payment) the main
 * one: a paying company always has one.
 */
class CrmProtection
{
    public const FREE = 'free';
    public const PROTECTED = 'protected';
    public const ALWAYS = 'always';

    public static function states(): array
    {
        return [
            self::FREE => 'Свободная',
            self::PROTECTED => 'Под защитой',
            self::ALWAYS => 'Всегда по согласованию',
        ];
    }

    /** The columns exist (the migration may run after the code update). */
    public static function ready(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = CmsCompany::getDb()->getTableSchema(CmsCompany::tableName(), true);
            $ready = $schema && $schema->getColumn('crm_protection') !== null;
        }

        return $ready;
    }

    /** Months a payment protects the client (crmSettings::$protectionMonths, 12 by default). */
    public static function months(): int
    {
        $months = \Yii::$app->has('crmSettings') ? (int)(\Yii::$app->crmSettings->protectionMonths ?? 12) : 12;

        return $months > 0 ? $months : 12;
    }

    /** @return CmsCompany|CmsUser|null */
    public static function record(string $recordCode, int $recordId)
    {
        return $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne($recordId) : CmsUser::findOne($recordId);
    }

    public static function state(string $recordCode, int $recordId): string
    {
        if (!self::ready()) {
            return self::FREE;
        }
        $record = self::record($recordCode, $recordId);

        return $record ? ((string)$record->crm_protection ?: self::FREE) : self::FREE;
    }

    /**
     * What the payments and services say now: [state, until, reason]; "always"
     * set by hand stays.
     */
    public function compute(string $recordCode, int $recordId, bool $keepAlways = true): array
    {
        $record = self::record($recordCode, $recordId);
        if (!$record) {
            return [self::FREE, null, null];
        }
        if ($keepAlways && (string)$record->crm_protection === self::ALWAYS) {
            return [self::ALWAYS, null, 'Включено вручную'];
        }
        $column = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id';
        // A person is protected by his personal services and payments (without a company), never by the company's.
        $personal = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? [] : ['cms_company_id' => null];
        $now = time();

        $deal = CmsDeal::find()->andWhere([$column => $recordId, 'is_active' => 1])->andWhere($personal)
            ->andWhere(['or', ['end_at' => null], ['>', 'end_at', $now]])
            ->orderBy(['end_at' => SORT_DESC, 'id' => SORT_DESC])->one();
        if ($deal) {
            return [self::PROTECTED, $deal->end_at ? (int)$deal->end_at : null, 'Действующая услуга №'.(int)$deal->id.' «'.$deal->name.'»'];
        }

        $payment = $this->lastPaymentAt($column, $recordId, $personal);
        $until = $payment ? strtotime('+'.self::months().' months', $payment) : null;
        if ($until && $until > $now) {
            return [self::PROTECTED, $until, 'Последний платёж '.date('d.m.Y', $payment)];
        }

        return [self::FREE, null, $payment ? 'Последний платёж '.date('d.m.Y', $payment).', защита истекла' : null];
    }

    /** Recalculates and stores the protection of a record; returns the state. */
    public function recalc(string $recordCode, int $recordId): string
    {
        if (!self::ready()) {
            return self::FREE;
        }
        [$state, $until, $reason] = $this->compute($recordCode, $recordId);
        $class = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::class : CmsUser::class;
        $class::updateAll(['crm_protection' => $state, 'crm_protected_until' => $until, 'crm_protection_reason' => $reason], ['id' => $recordId]);

        return $state;
    }

    /**
     * «Авто» or «Всегда по согласованию» (the switch of «Доступ»). Auto
     * recalculates from the payments and services at once.
     */
    public function setAlways(string $recordCode, int $recordId, bool $always): string
    {
        if (!self::ready()) {
            return self::FREE;
        }
        $class = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::class : CmsUser::class;
        if ($always) {
            $class::updateAll(['crm_protection' => self::ALWAYS, 'crm_protected_until' => null, 'crm_protection_reason' => 'Включено вручную'], ['id' => $recordId]);

            return self::ALWAYS;
        }
        [$state, $until, $reason] = $this->compute($recordCode, $recordId, false);
        $class::updateAll(['crm_protection' => $state, 'crm_protected_until' => $until, 'crm_protection_reason' => $reason], ['id' => $recordId]);

        return $state;
    }

    /** The column of the employee setting «Новые клиенты — всегда по согласованию» exists. */
    public static function newAlwaysReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = CmsUser::getDb()->getTableSchema(CmsUser::tableName(), true);
            $ready = $schema && $schema->getColumn('crm_new_always') !== null;
        }

        return $ready;
    }

    /**
     * A company or a person was just created (owner's decision 29.10.2026):
     * when the employee who creates it — the author (`created_by`), else the
     * current user — has the setting «Новые компании и клиенты, которые я
     * создаю, — „Всегда по согласованию“», the record gets `always` at once.
     * Every way of creating goes through here: the forms, the deal form, an
     * import, REST and MCP (they act as the authorized employee). The setting
     * of the creator decides, not of the main responsible he assigns.
     *
     * @param CmsCompany|CmsUser $record
     */
    public static function onCreated($record, string $recordCode): void
    {
        if (!self::ready() || !self::newAlwaysReady() || !$record->hasAttribute('crm_protection')
            || ($record->hasAttribute('is_worker') && $record->is_worker)) {
            return;
        }
        $creatorId = $record->hasAttribute('created_by') ? (int)$record->created_by : 0;
        if (!$creatorId && \Yii::$app->has('user') && \Yii::$app->user instanceof \yii\web\User) {
            $creatorId = (int)\Yii::$app->user->id;
        }
        if (!$creatorId || $creatorId === (int)$record->id
            || !CmsUser::find()->andWhere(['id' => $creatorId, 'is_worker' => 1, 'crm_new_always' => 1])->exists()) {
            return;
        }
        $class = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::class : CmsUser::class;
        $reason = 'Включено настройкой сотрудника для новых клиентов';
        $class::updateAll(['crm_protection' => self::ALWAYS, 'crm_protected_until' => null, 'crm_protection_reason' => $reason], ['id' => (int)$record->id]);
        foreach (['crm_protection' => self::ALWAYS, 'crm_protected_until' => null, 'crm_protection_reason' => $reason] as $attribute => $value) {
            $record->setAttribute($attribute, $value);
            $record->setOldAttribute($attribute, $value);
        }
        CrmAccessLog::write(CrmAccessLog::PROTECTION, $recordCode, (int)$record->id, ['actor_id' => $creatorId,
            'comment' => self::states()[self::FREE].' → '.self::states()[self::ALWAYS].', настройка сотрудника для новых клиентов']);
    }

    /**
     * The daily agent and the migration: protected records whose protection may
     * have expired, or (with $all) every record that has payments or services.
     *
     * @return int records recalculated
     */
    public function recalcMany(bool $all = false): int
    {
        if (!self::ready()) {
            return 0;
        }
        $count = 0;
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            $column = $code === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id';
            $ids = $class::find()->select('id')->andWhere(['crm_protection' => self::PROTECTED])->column();
            if ($all) {
                $ids = array_merge($ids, CmsDeal::find()->select($column)->distinct()->andWhere(['not', [$column => null]])->column());
                $payment = '\skeeks\cms\shop\models\ShopPayment';
                if (class_exists($payment)) {
                    $ids = array_merge($ids, $payment::find()->select($column)->distinct()->andWhere(['not', [$column => null]])->andWhere(['is_debit' => 1])->column());
                }
            }
            foreach (array_unique(array_map('intval', $ids)) as $id) {
                if ($id > 0) {
                    $this->recalc($code, $id);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * An incoming payment: a company (or a person) without a main responsible
     * gets one — the responsible of the bill paid by it, else who registered the
     * payment; then the protection is recalculated.
     *
     * @param \skeeks\cms\shop\models\ShopPayment $payment
     */
    public function onPayment($payment): void
    {
        if (!(int)$payment->is_debit) {
            return;
        }
        $record = $payment->cms_company_id ? [CmsCrmAccessRequest::RECORD_COMPANY, (int)$payment->cms_company_id]
            : ($payment->cms_user_id ? [CmsCrmAccessRequest::RECORD_CLIENT, (int)$payment->cms_user_id] : null);
        if (!$record) {
            return;
        }
        [$code, $id] = $record;
        if ($code === CmsCrmAccessRequest::RECORD_CLIENT && ($user = CmsUser::findOne($id)) && $user->is_worker) {
            return;
        }
        $access = CrmAccess::instance();
        if (CrmAccess::mainReady() && $access->mainResponsibleId($code, $id) === null) {
            $billClass = '\skeeks\cms\shop\models\ShopBill';
            $billBy = class_exists($billClass) ? (int)$billClass::find()->select('created_by')->andWhere(['shop_payment_id' => (int)$payment->id])->scalar() : 0;
            foreach ([$billBy, (int)$payment->created_by] as $candidate) {
                if ($candidate > 0 && CmsUser::find()->andWhere(['id' => $candidate, 'is_worker' => 1, 'is_active' => 1])->exists()) {
                    $access->setMain($code, $id, $candidate);
                    CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, $code, $id, ['actor_id' => null, 'user_id' => $candidate,
                        'level_to' => CrmAccess::LEVEL_FULL, 'shop_payment_id' => (int)$payment->id, 'comment' => 'первый платёж клиента без основного']);
                    break;
                }
            }
        }
        $this->recalc($code, $id);
    }

    /**
     * Protected clients without a main responsible and who becomes it (a paying
     * client always has one): the creator of the bill of its last incoming
     * payment, else who registered that payment, else the creator of its last
     * active service, else the first administrator. Only active employees.
     *
     * @return array[] code, id, name, user_id, user, source
     */
    public function mainPlan(): array
    {
        if (!self::ready() || !CrmAccess::mainReady()) {
            return [];
        }
        [$fallbackId, $fallbackSource] = $this->fallback();
        $plan = [];
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            [$linkClass, $recordColumn] = CrmAccess::linkTable($code);
            $records = $class::find()->andWhere(['crm_protection' => [self::PROTECTED, self::ALWAYS]])
                ->andWhere(['not exists', $linkClass::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($class::tableName().'.id')])->andWhere(['is_main' => 1])])
                ->orderBy(['id' => SORT_ASC])->all();
            foreach ($records as $record) {
                [$userId, $source] = $this->mainCandidate($code, (int)$record->id, $fallbackId, $fallbackSource);
                $user = $userId ? CmsUser::findOne($userId) : null;
                $plan[] = [
                    'code' => $code,
                    'id' => (int)$record->id,
                    'name' => $record instanceof CmsCompany ? (string)$record->name : (string)$record->shortDisplayName,
                    'user_id' => $userId,
                    'user' => $user ? (string)$user->shortDisplayName : '—',
                    'source' => $source ?: 'никого нет',
                ];
            }
        }

        return $plan;
    }

    public const OLD_BASE_COMMENT = 'Назначен основным: старая база без ответственного';

    /**
     * @deprecated a fixed employee id; the code no longer reads it. The default
     * main responsible is the setting «Ответственный по умолчанию»
     * (CrmAccess::defaultResponsible(): the setting, else the first administrator).
     */
    public const HISTORY_FALLBACK_USER_ID = 1;

    /**
     * The employee who takes a client nobody else takes, and why: $fallbackId
     * chosen by the caller (a console `--user`), else CrmAccess::defaultResponsible().
     *
     * @return array [int|null the employee, string the ground in words]
     */
    public function fallback(?int $fallbackId = null): array
    {
        if ($fallbackId) {
            return [$fallbackId, 'выбран в команде'];
        }
        $default = CrmAccess::instance()->defaultResponsible();

        return $default ? [(int)$default[0], 'ответственный по умолчанию ('.$default[1].')'] : [null, 'никого нет'];
    }

    /**
     * Who becomes the main responsible of a client by its history: the creator
     * of the bill of its last incoming payment, else who registered that
     * payment, else the creator of its last service (an active one first),
     * else $fallbackId. Only active employees. Of a person — his personal
     * payments and services.
     *
     * @return array [user id or null, the ground in words]
     */
    public function mainCandidate(string $code, int $id, ?int $fallbackId, string $fallbackSource): array
    {
        $worker = static fn($userId) => $userId && CmsUser::find()->andWhere(['id' => (int)$userId, 'is_worker' => 1, 'is_active' => 1])->exists() ? (int)$userId : null;
        $column = $code === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id';
        $personal = $code === CmsCrmAccessRequest::RECORD_COMPANY ? [] : ['cms_company_id' => null];
        $payment = '\skeeks\cms\shop\models\ShopPayment';
        $bill = '\skeeks\cms\shop\models\ShopBill';
        $last = class_exists($payment) ? $payment::find()->andWhere([$column => $id, 'is_debit' => 1])->andWhere($personal)
            ->orderBy(new \yii\db\Expression('COALESCE(operation_at, created_at) DESC'))->one() : null;
        if ($last && class_exists($bill) && ($userId = $worker($bill::find()->select('created_by')->andWhere(['shop_payment_id' => (int)$last->id])->scalar()))) {
            return [$userId, 'счёт последнего платежа'];
        }
        if ($last && ($userId = $worker($last->created_by))) {
            return [$userId, 'последний платёж'];
        }
        $service = CmsDeal::find()->select('created_by')->andWhere([$column => $id])->andWhere($personal)->orderBy(['is_active' => SORT_DESC, 'id' => SORT_DESC])->scalar();
        if ($userId = $worker($service)) {
            return [$userId, 'последняя услуга'];
        }

        return [$worker($fallbackId), $fallbackSource];
    }

    /**
     * Clients with a history and without a main responsible (owner's decision
     * 29.10.2026: such a client always has one) and who becomes it,
     * mainCandidate() with the fallback employee.
     *
     * @return array[] code, id, name, user_id, user, source
     */
    public function historyMainPlan(?int $fallbackId = null): array
    {
        [$fallbackId, $fallbackSource] = $this->fallback($fallbackId);
        if (!CrmAccess::mainReady()) {
            return [];
        }
        $plan = [];
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            [$linkClass, $recordColumn] = CrmAccess::linkTable($code);
            $table = $class::tableName();
            $query = $class::find()->andWhere(CrmAccess::historyCondition($code, $table.'.id'))
                ->andWhere(['not exists', $linkClass::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($table.'.id'), 'is_main' => 1])])
                ->orderBy([$table.'.id' => SORT_ASC]);
            if ($code === CmsCrmAccessRequest::RECORD_CLIENT) {
                $query->andWhere([$table.'.is_worker' => 0]);
            }
            foreach ($query->each(200) as $record) {
                [$userId, $source] = $this->mainCandidate($code, (int)$record->id, $fallbackId, $fallbackSource);
                $user = $userId ? CmsUser::findOne($userId) : null;
                $plan[] = [
                    'code' => $code,
                    'id' => (int)$record->id,
                    'name' => $record instanceof CmsCompany ? (string)$record->name : (string)$record->shortDisplayName,
                    'user_id' => $userId,
                    'user' => $user ? (string)$user->shortDisplayName : '—',
                    'source' => $source,
                ];
            }
        }

        return $plan;
    }

    /**
     * Clients with participants and without a main responsible (owner's
     * decision 29.10.2026: participants only with the main one) and who
     * becomes it: an active participant with "full", else by the history
     * (mainCandidate()), else $fallbackId. Applied by applyHistoryMainPlan().
     *
     * @return array[] code, id, name, user_id, user, source
     */
    public function orphanMainPlan(?int $fallbackId = null): array
    {
        [$fallbackId, $fallbackSource] = $this->fallback($fallbackId);
        if (!CrmAccess::mainReady()) {
            return [];
        }
        $plan = [];
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            [$linkClass, $recordColumn, $userColumn] = CrmAccess::linkTable($code);
            $table = $class::tableName();
            $links = static fn() => $linkClass::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($table.'.id')]);
            $query = $class::find()->andWhere(['exists', $links()])->andWhere(['not exists', $links()->andWhere(['is_main' => 1])])
                ->orderBy([$table.'.id' => SORT_ASC]);
            if ($code === CmsCrmAccessRequest::RECORD_CLIENT) {
                $query->andWhere([$table.'.is_worker' => 0]);
            }
            foreach ($query->each(200) as $record) {
                $full = CrmAccess::levelsReady() ? $linkClass::find()->alias('l')->select('l.'.$userColumn)
                    ->innerJoin(['w' => CmsUser::tableName()], 'w.id = l.'.$userColumn.' AND w.is_active = 1 AND w.is_worker = 1')
                    ->andWhere(['l.'.$recordColumn => (int)$record->id, 'l.access_level' => CrmAccess::LEVEL_FULL])
                    ->orderBy(['l.id' => SORT_ASC])->scalar() : null;
                [$userId, $source] = $full ? [(int)$full, 'участник с полным доступом']
                    : $this->mainCandidate($code, (int)$record->id, $fallbackId, $fallbackSource);
                $user = $userId ? CmsUser::findOne($userId) : null;
                $plan[] = [
                    'code' => $code,
                    'id' => (int)$record->id,
                    'name' => $record instanceof CmsCompany ? (string)$record->name : (string)$record->shortDisplayName,
                    'user_id' => $userId,
                    'user' => $user ? (string)$user->shortDisplayName : '—',
                    'source' => $source,
                ];
            }
        }

        return $plan;
    }

    /**
     * Clients without any employee — the former general pool (owner's decision
     * 29.10.2026, п. 13а: a CRM client always has a main responsible) — and who
     * becomes the main one: by the history (mainCandidate()), else $fallbackId.
     * Companies; persons that are CRM clients — created by an employee or with
     * a history, not of a company with employees (a person who registered on
     * the site himself and ordered nothing is a user of the site, п. 14).
     * Applied by applyHistoryMainPlan().
     *
     * @return array[] code, id, name, user_id, user, source
     */
    public function poolMainPlan(?int $fallbackId = null): array
    {
        [$fallbackId, $fallbackSource] = $this->fallback($fallbackId);
        if (!CrmAccess::mainReady()) {
            return [];
        }
        $plan = [];
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            [$linkClass, $recordColumn] = CrmAccess::linkTable($code);
            $table = $class::tableName();
            $query = $class::find()
                ->andWhere(['not exists', $linkClass::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($table.'.id')])])
                ->orderBy([$table.'.id' => SORT_ASC]);
            if ($code === CmsCrmAccessRequest::RECORD_CLIENT) {
                $query->andWhere([$table.'.is_worker' => 0])
                    ->andWhere(['not exists', CrmAccess::personCompanyLinks($table.'.id')])
                    ->andWhere(['or',
                        ['and', ['not', [$table.'.created_by' => null]], ['<>', $table.'.created_by', new \yii\db\Expression($table.'.id')]],
                        CrmAccess::historyCondition($code, $table.'.id'),
                    ]);
            }
            foreach ($query->each(200) as $record) {
                [$userId, $source] = $this->mainCandidate($code, (int)$record->id, $fallbackId, $fallbackSource);
                $user = $userId ? CmsUser::findOne($userId) : null;
                $plan[] = [
                    'code' => $code,
                    'id' => (int)$record->id,
                    'name' => $record instanceof CmsCompany ? (string)$record->name : (string)$record->shortDisplayName,
                    'user_id' => $userId,
                    'user' => $user ? (string)$user->shortDisplayName : '—',
                    'source' => $source,
                ];
            }
        }

        return $plan;
    }

    /** Applies historyMainPlan(): the main responsible with the ground in the journal of the access. */
    public function applyHistoryMainPlan(array $plan): int
    {
        $count = 0;
        foreach ($plan as $row) {
            if ($row['user_id'] && CrmAccess::instance()->mainResponsibleId($row['code'], (int)$row['id']) === null) {
                $this->setHistoryMain($row['code'], (int)$row['id'], (int)$row['user_id'], (string)$row['source']);
                $count++;
            }
        }

        return $count;
    }

    /** One client with a history and no main one (a deal of «Работать с этим клиентом» comes first). */
    public function assignHistoryMain(string $code, int $id): ?int
    {
        [$userId, $source] = $this->mainCandidate($code, $id, ...$this->fallback());
        if ($userId && CrmAccess::instance()->mainResponsibleId($code, $id) === null) {
            $this->setHistoryMain($code, $id, $userId, $source);
        }

        return $userId;
    }

    private function setHistoryMain(string $code, int $id, int $userId, string $source): void
    {
        $levelBefore = CrmAccess::instance()->linkLevel($userId, $code, $id);
        CrmAccess::instance()->setMain($code, $id, $userId);
        CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, $code, $id, ['actor_id' => null, 'user_id' => $userId, 'level_from' => $levelBefore,
            'level_to' => CrmAccess::LEVEL_FULL, 'comment' => 'клиент с историей без основного: '.$source]);
    }

    /**
     * Client persons of the old base in the general pool (owner's decision
     * 29.10.2026): no company, no employee of their own, created before the
     * given time. The cutoff is explicit, so people who come later from an
     * import or a bot without a responsible stay in the pool.
     *
     * @return CmsUser[]
     */
    public function oldBasePeople(int $before, ?int $siteId = null): array
    {
        if (!CrmAccess::mainReady() || $before <= 0) {
            return [];
        }
        $table = CmsUser::tableName();
        $query = CmsUser::find()->andWhere([$table.'.is_worker' => 0])->andWhere(['<', $table.'.created_at', $before])
            ->andWhere(['not exists', \skeeks\cms\models\CmsUser2manager::find()->select('id')->andWhere(['client_id' => new \yii\db\Expression($table.'.id')])])
            ->andWhere(['not exists', \skeeks\cms\models\CmsCompany2user::find()->select('id')->andWhere(['cms_user_id' => new \yii\db\Expression($table.'.id')])])
            ->orderBy([$table.'.id' => SORT_ASC]);
        if ($siteId) {
            $query->andWhere([$table.'.cms_site_id' => $siteId]);
        }

        return $query->all();
    }

    /** Makes the employee the main responsible of oldBasePeople(), each written to the journal. */
    public function applyOldBase(array $people, int $userId): int
    {
        $count = 0;
        foreach ($people as $person) {
            if (CrmAccess::instance()->isOrphan(CmsCrmAccessRequest::RECORD_CLIENT, (int)$person->id)) {
                CrmAccess::instance()->setMain(CmsCrmAccessRequest::RECORD_CLIENT, (int)$person->id, $userId);
                CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, CmsCrmAccessRequest::RECORD_CLIENT, (int)$person->id, ['actor_id' => null, 'user_id' => $userId,
                    'level_to' => CrmAccess::LEVEL_FULL, 'comment' => self::OLD_BASE_COMMENT]);
                $count++;
            }
        }

        return $count;
    }

    /** Applies mainPlan(): the chosen employee becomes the main responsible ("full"). */
    public function applyMainPlan(array $plan): int
    {
        $count = 0;
        foreach ($plan as $row) {
            if ($row['user_id'] && CrmAccess::instance()->mainResponsibleId($row['code'], (int)$row['id']) === null) {
                CrmAccess::instance()->setMain($row['code'], (int)$row['id'], (int)$row['user_id']);
                CrmAccessLog::write(CrmAccessLog::MAIN_AUTO, $row['code'], (int)$row['id'], ['actor_id' => null, 'user_id' => (int)$row['user_id'],
                    'level_to' => CrmAccess::LEVEL_FULL, 'comment' => 'защищённый клиент без основного: '.$row['source']]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * «Под защитой до 15.03.2027: последний платёж 15.03.2026» and the like.
     * $withReason = false — the state only: the reason names payments and
     * services, hidden from an employee with "deal".
     */
    public static function describe(string $recordCode, int $recordId, bool $withReason = true): string
    {
        return self::describeRecord(self::ready() ? self::record($recordCode, $recordId) : null, $withReason);
    }

    /** describe() of a loaded company or person (a list row, no query). */
    public static function describeRecord($record, bool $withReason = true): string
    {
        if (!$record || !self::ready() || !$record->hasAttribute('crm_protection')) {
            return 'Свободная';
        }
        $state = (string)$record->crm_protection ?: self::FREE;
        $reason = $withReason ? (string)$record->crm_protection_reason : '';
        if ($state === self::ALWAYS) {
            // Who switched it on (by hand, by the setting of the employee who created the client) is no secret.
            $why = trim((string)$record->crm_protection_reason) ?: 'Включено вручную';

            return 'Всегда по согласованию: '.mb_strtolower(mb_substr($why, 0, 1)).mb_substr($why, 1);
        }
        if ($state === self::PROTECTED) {
            return 'Под защитой'.($record->crm_protected_until ? ' до '.date('d.m.Y', (int)$record->crm_protected_until) : '')
                .($reason !== '' ? ': '.mb_strtolower(mb_substr($reason, 0, 1)).mb_substr($reason, 1) : '');
        }

        return 'Свободная'.($reason !== '' ? ': '.mb_strtolower(mb_substr($reason, 0, 1)).mb_substr($reason, 1) : '');
    }

    /**
     * The shield at the name of a protected client (owner's decision
     * 29.10.2026): «Под защитой» and «Всегда по согласованию» stand out
     * everywhere, a free client shows nothing. The hint — the state, the term
     * and, with $withReason, the reason (it names payments and services: only
     * for who sees the whole client or manages the access).
     *
     * @param CmsCompany|CmsUser|null $record
     */
    public static function shield($record, bool $withReason = false): string
    {
        if (!$record || !self::ready() || !$record->hasAttribute('crm_protection')
            || ((string)$record->crm_protection ?: self::FREE) === self::FREE) {
            return '';
        }

        return self::shieldIcon((string)$record->crm_protection, self::describeRecord($record, $withReason));
    }

    /**
     * The shield by a state and its description (a match of the duplicate
     * search carries them, CrmAccess::presentCandidate()).
     */
    public static function shieldIcon(string $state, string $description): string
    {
        if ($state === '' || $state === self::FREE || !isset(self::states()[$state])) {
            return '';
        }
        if (\Yii::$app->has('view') && \Yii::$app->view instanceof \yii\web\View) {
            \skeeks\cms\assets\admin\CrmShieldAsset::register(\Yii::$app->view);
        }
        $title = ($description !== '' ? $description : self::states()[$state]).'. Работа с клиентом — по согласованию с основным ответственным.';

        return \yii\helpers\Html::tag('i', '', [
            'class' => 'fas fa-shield-alt sx-crm-shield'.($state === self::ALWAYS ? ' sx-crm-shield--always' : ''),
            'title' => $title,
            'aria-label' => $title,
            'role' => 'img',
            'data-toggle' => 'tooltip',
        ]);
    }

    /**
     * The shield for the current employee: the reason only when he sees the
     * whole client or manages its access (as «Доступ», CrmAccess::seesWholeClient()).
     */
    public static function shieldFor($record, $user = null): string
    {
        if (!$record || !self::ready() || !$record->hasAttribute('crm_protection')
            || ((string)$record->crm_protection ?: self::FREE) === self::FREE) {
            return '';
        }
        $code = $record instanceof CmsCompany ? CmsCrmAccessRequest::RECORD_COMPANY : CmsCrmAccessRequest::RECORD_CLIENT;
        $user = $user ?? (\Yii::$app->has('user') && \Yii::$app->user instanceof \yii\web\User ? (int)\Yii::$app->user->id : 0);
        $access = CrmAccess::instance();
        $withReason = $user && ($access->canManageAccess($user, $code, (int)$record->id)
            || CrmAccess::seesWholeClient($access->wholeClientLevel($user, $code, (int)$record->id)));

        return self::shield($record, $withReason);
    }

    /** @return int|null the time of the last incoming payment */
    private function lastPaymentAt(string $column, int $recordId, array $personal = []): ?int
    {
        $payment = '\skeeks\cms\shop\models\ShopPayment';
        if (!class_exists($payment)) {
            return null;
        }
        $at = $payment::find()->andWhere([$column => $recordId, 'is_debit' => 1])->andWhere($personal)
            ->select(new \yii\db\Expression('MAX(COALESCE(operation_at, created_at))'))->scalar();

        return $at ? (int)$at : null;
    }
}

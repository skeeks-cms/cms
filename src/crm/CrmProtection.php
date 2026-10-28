<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2manager;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUser2manager;

/**
 * П2д, owner's decision 28.10.2026 (cms-communication ARCHITECTURE.md п. 13):
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
     * «Вернуть в общий пул» (the main responsible, his leaders, an
     * administrator): the client has no employees any more, the next one who
     * finds it takes it into work. A protected client is not returned.
     */
    public function returnToPool(string $recordCode, int $recordId, int $actorId): void
    {
        if (!CrmAccess::instance()->canManageAccess($actorId, $recordCode, $recordId)) {
            throw new \DomainException('Вернуть клиента в общий пул может основной ответственный, его руководитель или администратор.');
        }
        $state = $this->recalc($recordCode, $recordId);
        if ($state !== self::FREE) {
            throw new \DomainException('Клиент под защитой — его нельзя вернуть в общий пул. Сначала переключите защиту на «Авто», и она должна истечь.');
        }
        $mainBefore = CrmAccess::instance()->mainResponsibleId($recordCode, $recordId);
        if ($recordCode === CmsCrmAccessRequest::RECORD_COMPANY) {
            CmsCompany2manager::deleteAll(['cms_company_id' => $recordId]);
        } else {
            CmsUser2manager::deleteAll(['client_id' => $recordId]);
        }
        CrmAccessLog::write(CrmAccessLog::POOL, $recordCode, $recordId, ['actor_id' => $actorId, 'user_id' => $mainBefore]);
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
        $access = CrmAccess::instance();
        $admins = $access->adminIds();
        $worker = static fn($id) => $id && CmsUser::find()->andWhere(['id' => (int)$id, 'is_worker' => 1, 'is_active' => 1])->exists() ? (int)$id : null;
        $payment = '\skeeks\cms\shop\models\ShopPayment';
        $bill = '\skeeks\cms\shop\models\ShopBill';
        $plan = [];
        foreach ([CmsCrmAccessRequest::RECORD_COMPANY => CmsCompany::class, CmsCrmAccessRequest::RECORD_CLIENT => CmsUser::class] as $code => $class) {
            [$linkClass, $recordColumn] = CrmAccess::linkTable($code);
            $column = $code === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id';
            $records = $class::find()->andWhere(['crm_protection' => [self::PROTECTED, self::ALWAYS]])
                ->andWhere(['not exists', $linkClass::find()->select('id')->andWhere([$recordColumn => new \yii\db\Expression($class::tableName().'.id')])->andWhere(['is_main' => 1])])
                ->orderBy(['id' => SORT_ASC])->all();
            foreach ($records as $record) {
                $userId = null;
                $source = '';
                $last = class_exists($payment) ? $payment::find()->andWhere([$column => (int)$record->id, 'is_debit' => 1])
                    ->orderBy(new \yii\db\Expression('COALESCE(operation_at, created_at) DESC'))->one() : null;
                if ($last && class_exists($bill) && ($userId = $worker($bill::find()->select('created_by')->andWhere(['shop_payment_id' => (int)$last->id])->scalar()))) {
                    $source = 'счёт последнего платежа';
                } elseif ($last && ($userId = $worker($last->created_by))) {
                    $source = 'последний платёж';
                } elseif ($userId = $worker(CmsDeal::find()->select('created_by')->andWhere([$column => (int)$record->id, 'is_active' => 1])->orderBy(['id' => SORT_DESC])->scalar())) {
                    $source = 'действующая услуга';
                } elseif ($admins) {
                    $userId = $admins[0];
                    $source = 'администратор по умолчанию';
                }
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
            if (CrmAccess::instance()->inPool(CmsCrmAccessRequest::RECORD_CLIENT, (int)$person->id)) {
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
        $record = self::ready() ? self::record($recordCode, $recordId) : null;
        if (!$record) {
            return 'Свободная';
        }
        $state = (string)$record->crm_protection ?: self::FREE;
        $reason = $withReason ? (string)$record->crm_protection_reason : '';
        if ($state === self::ALWAYS) {
            return 'Всегда по согласованию: включено вручную';
        }
        if ($state === self::PROTECTED) {
            return 'Под защитой'.($record->crm_protected_until ? ' до '.date('d.m.Y', (int)$record->crm_protected_until) : '')
                .($reason !== '' ? ': '.mb_strtolower(mb_substr($reason, 0, 1)).mb_substr($reason, 1) : '');
        }

        return 'Свободная'.($reason !== '' ? ': '.mb_strtolower(mb_substr($reason, 0, 1)).mb_substr($reason, 1) : '');
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

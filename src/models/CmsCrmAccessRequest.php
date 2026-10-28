<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * A request of an employee about a client of another main responsible
 * (П2а, owner's decision 28.10.2026): «Работать с этим клиентом» (work) — the
 * employee already works on his deal with the level "Свои сделки", the main
 * responsible may raise the level or decline (the deal stays with the
 * employee); for a client with «Доступ только по согласованию» the deal is
 * created only after the approval. "delegate" and "access" are requests made
 * before that decision: read only, the pending ones were closed as
 * "obsolete" (m261028_150000, CrmAccessRequests::closeLegacy()) and can be
 * neither created nor decided any more. A repeated request of the same
 * employee joins the pending one. «Передать клиента» is not a request:
 * CrmClientTransfer, by a leader or an administrator.
 *
 * Decisions and permissions: CrmAccess::canDecideRequest(), the service
 * skeeks\cms\crm\CrmAccessRequests.
 *
 * @property int         $id
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int|null    $cms_site_id
 * @property int         $requester_id
 * @property int|null    $owner_id
 * @property string      $record_code company|client
 * @property int         $record_id
 * @property int|null    $cms_lead_id
 * @property string      $kind work (delegate|access — before 28.10.2026)
 * @property string|null $level the level of the link: deal, edit, full (crm\CrmAccess::levels())
 * @property string|null $comment
 * @property string      $status pending|approved|declined|cancelled|obsolete
 * @property int|null    $decided_by
 * @property int|null    $decided_at
 * @property string|null $decision_comment
 *
 * @property CmsUser     $requester
 * @property CmsUser|null $owner
 * @property CmsUser|null $decidedBy
 * @property CmsLead|null $lead
 */
class CmsCrmAccessRequest extends ActiveRecord
{
    public const RECORD_COMPANY = 'company';
    public const RECORD_CLIENT = 'client';

    /** «Работать с этим клиентом». */
    public const KIND_WORK = 'work';
    /** Before 28.10.2026: «Запросить сделку себе» and «Запросить доступ» — history only (isLegacy()). */
    public const KIND_DELEGATE = 'delegate';
    public const KIND_ACCESS = 'access';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';
    /** A pending request of an old kind closed when the kinds were reduced to one. */
    public const STATUS_OBSOLETE = 'obsolete';

    public static function tableName()
    {
        return '{{%cms_crm_access_request}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['comment', 'decision_comment'], 'filter', 'filter' => static fn($value) => is_string($value) ? (trim($value) ?: null) : $value],
            [['requester_id', 'record_code', 'record_id', 'kind', 'status'], 'required'],
            [['cms_site_id', 'requester_id', 'owner_id', 'record_id', 'cms_lead_id', 'decided_by', 'decided_at'], 'integer'],
            [['comment', 'decision_comment'], 'string'],
            [['record_code'], 'in', 'range' => [self::RECORD_COMPANY, self::RECORD_CLIENT]],
            [['kind'], 'in', 'range' => array_keys(self::kinds())],
            [['status'], 'in', 'range' => array_keys(self::statuses())],
        ], \skeeks\cms\crm\CrmAccess::levelsReady()
            ? [[['level'], 'in', 'range' => array_keys(\skeeks\cms\crm\CrmAccess::levels())]]
            : []);
    }

    public static function kinds(): array
    {
        return [
            self::KIND_WORK => 'Работа с клиентом',
            self::KIND_DELEGATE => 'Сделка себе',
            self::KIND_ACCESS => 'Доступ к клиенту',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Ждёт решения',
            self::STATUS_APPROVED => 'Одобрен',
            self::STATUS_DECLINED => 'Отклонён',
            self::STATUS_CANCELLED => 'Отменён',
            self::STATUS_OBSOLETE => 'Устарел',
        ];
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'requester_id' => 'Кто просит',
            'owner_id' => 'Ответственный',
            'record_id' => 'Клиент',
            'cms_lead_id' => 'Сделка',
            'kind' => 'Что просят',
            'comment' => 'Комментарий',
            'status' => 'Статус',
            'decided_by' => 'Кто решил',
            'decided_at' => 'Когда решено',
            'decision_comment' => 'Ответ',
            'created_at' => 'Создан',
        ]);
    }

    public function getIsPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** A request of an old kind ("delegate", "access"): shown, never decided. */
    public function getIsLegacy(): bool
    {
        return $this->kind !== self::KIND_WORK;
    }

    public function getKindName(): string
    {
        return self::kinds()[$this->kind] ?? $this->kind;
    }

    public function getLevelName(): string
    {
        $level = $this->hasAttribute('level') ? (string)$this->level : '';

        return \skeeks\cms\crm\CrmAccess::levels()[$level] ?? '';
    }

    public function getStatusName(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    /** The company or the client the request is about, read without a visibility scope. */
    public function getRecord(): ?\yii\db\ActiveRecord
    {
        return $this->record_code === self::RECORD_COMPANY
            ? CmsCompany::findOne((int)$this->record_id)
            : CmsUser::findOne((int)$this->record_id);
    }

    public function getRequester() { return $this->hasOne(CmsUser::class, ['id' => 'requester_id']); }
    public function getOwner() { return $this->hasOne(CmsUser::class, ['id' => 'owner_id']); }
    public function getDecidedBy() { return $this->hasOne(CmsUser::class, ['id' => 'decided_by']); }
    public function getLead() { return $this->hasOne(CmsLead::class, ['id' => 'cms_lead_id']); }

    public function asText()
    {
        return $this->kindName.' №'.(int)$this->id;
    }
}

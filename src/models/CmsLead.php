<?php

namespace skeeks\cms\models;

use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\cms\models\queries\CmsLeadQuery;
use skeeks\cms\rbac\CmsManager;
use yii\behaviors\BlameableBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class CmsLead extends Core
{
    use HasLogTrait;
    public const EVENT_PARTNER_SUCCESS = 'partnerSuccess';

    /** @var CmsLog|null Guarantees exactly one creation entry per lead instance. */
    private $_creationActivityLog = null;

    /** @var float|null Passed to the installed partner-finance extension on success. */
    public $partner_reward_value;
    /** Source ingestion persists contacts before selecting notification recipients. */
    public $deferManagerNotifications = false;
    /**
     * Batch writers (imports) send one notification for the whole batch: the
     * lead lifecycle then sends neither "assigned" nor "new" notifications.
     */
    public $suppressNotifications = false;
    /** An empty name is filled from the client and the source (the deal form). */
    public $autoName = false;
    public const STATUS_NEW = 'new';
    public const STATUS_IN_WORK = 'in_work';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_REJECTED = 'rejected';

    /**
     * How sorting an unsorted deal ended when it was not a sale (П2а). The deal
     * is closed as rejected, but is not a lost sale and stays out of the report.
     */
    public const TRIAGE_MERGED = 'merged';
    public const TRIAGE_SUPPORT = 'support';
    public const TRIAGE_SPAM = 'spam';

    /** Fired after a deal is sorted: TriageEvent (result, target lead, task). */
    public const EVENT_TRIAGED = 'triaged';

    public const UTM_ATTRIBUTES = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_PARTNER = 'partner';
    public const SOURCE_FORM = 'form';
    public const SOURCE_VK = 'vk';
    public const SOURCE_TELEGRAM = 'telegram';
    public const SOURCE_PHONE = 'phone';
    public const SOURCE_IMPORT = 'import';
    public const SOURCE_API = 'api';
    public const SOURCE_EMAIL = 'email';
    public const SOURCE_MAX = 'max';
    /** Recipients of mailings imported into leads (skeeks/cms-communication). */
    public const SOURCE_MAILING = 'mailing';

    public static function tableName()
    {
        return '{{%cms_lead}}';
    }

    public function init()
    {
        parent::init();

        if ($this->isNewRecord) {
            $this->status = self::STATUS_NEW;
            $this->source_type = self::SOURCE_MANUAL;
        }
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'json' => [
                'class' => HasJsonFieldsBehavior::class,
                'fields' => ['source_data'],
            ],
            'log' => [
                'class' => CmsLogBehavior::class,
                'attribute_value_maps' => [
                    'status' => self::statuses(),
                    'source_type' => self::sources(),
                ],
                'relation_map' => [
                    'cms_lead_stage_id' => 'stage',
                    'cms_lead_pipeline_id' => 'pipeline',
                    'cms_lead_reject_reason_id' => 'rejectReasonItem',
                ],
            ],
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), $this->triageRules(), [
            [['source_type'], 'default', 'value' => self::SOURCE_MANUAL],
            [['status'], 'default', 'value' => self::STATUS_NEW],
            [['lock_version'], 'default', 'value' => 0],
            [['cms_site_id'], 'default', 'value' => static function () {
                return \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
            }],
            [['name'], 'filter', 'filter' => 'trim'],
            [['description', 'source_ref', 'source_name', 'source_url'], 'default', 'value' => null],
            [['source_type', 'status'], 'required'],
            // The deal form may leave the name empty: «Компания — источник» or the number (LeadTriage::suggestName()).
            [['name'], 'required', 'when' => static fn(self $model) => !$model->autoName],
            [['cms_site_id', 'submitted_by_id', 'partner_id', 'executor_id', 'cms_company_id', 'cms_user_id', 'processed_at', 'lock_version'], 'integer'],
            [['description', 'reject_reason', 'result_comment'], 'string'],
            [['partner_reward_value'], 'number', 'min' => 0.01, 'skipOnEmpty' => true],
            [['deal_amount'], 'filter', 'filter' => static function ($value) {
                return is_string($value) ? (trim(str_replace([' ', ','], ['', '.'], $value)) ?: null) : $value;
            }],
            [['deal_amount'], 'number', 'min' => 0.01, 'max' => 9999999999999999, 'skipOnEmpty' => true],
            // Required when a lead becomes successful; earlier successes stay as they were.
            [['deal_amount'], 'required', 'message' => 'Укажите сумму сделки',
                'when' => static function (self $model) {
                    return $model->status === self::STATUS_SUCCESS && $model->getOldAttribute('status') !== self::STATUS_SUCCESS;
                },
                'whenClient' => 'function (attribute, value) { return $("[data-sx-lead-status]").val() === "'.self::STATUS_SUCCESS.'"; }',
            ],
            [['source_data'], 'safe'],
            [['name', 'source_name', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'], 'string', 'max' => 255],
            [['source_type', 'status'], 'string', 'max' => 32],
            [['source_ref'], 'string', 'max' => 190],
            [['source_url'], 'string', 'max' => 1000],
            [['source_type'], 'in', 'range' => array_keys(self::sources())],
            [['status'], 'in', 'range' => array_keys(self::statuses())],
            [['cms_company_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsCompany::class, 'targetAttribute' => 'id'],
            [['cms_lead_stage_id'], 'filter', 'filter' => static function ($value) {
                return $value === '' ? null : $value;
            }],
            [['cms_lead_stage_id'], 'integer'],
            [['cms_lead_stage_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsLeadStage::class,
                'targetAttribute' => ['cms_lead_stage_id' => 'id', 'cms_site_id' => 'cms_site_id'],
                'message' => 'Этап не найден на этом сайте.'],
            [['cms_user_id', 'submitted_by_id', 'partner_id', 'executor_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsUser::class, 'targetAttribute' => 'id'],
            [['executor_id'], 'required', 'when' => static function (self $model) {
                return $model->status !== self::STATUS_NEW;
            }],
            // With a site reason list the reason is chosen from it and the text is an optional comment.
            [['reject_reason'], 'required', 'message' => 'Укажите причину отклонения', 'when' => static function (self $model) {
                return $model->status === self::STATUS_REJECTED && !$model->getTriageResult() && !CmsLeadRejectReason::siteHasReasons($model->cms_site_id);
            }],
            [['cms_lead_reject_reason_id'], 'filter', 'filter' => static function ($value) {
                return $value === '' ? null : $value;
            }],
            [['cms_lead_reject_reason_id'], 'integer'],
            [['cms_lead_reject_reason_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsLeadRejectReason::class,
                'targetAttribute' => ['cms_lead_reject_reason_id' => 'id', 'cms_site_id' => 'cms_site_id'],
                'message' => 'Причина не найдена на этом сайте.'],
            [['cms_lead_reject_reason_id'], 'required', 'message' => 'Выберите причину отказа',
                'when' => static function (self $model) {
                    return $model->status === self::STATUS_REJECTED && !$model->getTriageResult() && CmsLeadRejectReason::siteHasReasons($model->cms_site_id);
                },
                'whenClient' => 'function (attribute, value) { return $("[data-sx-lead-status]").val() === "'.self::STATUS_REJECTED.'"; }',
            ],
            [['result_comment'], 'required', 'message' => 'Укажите результат обработки сделки', 'when' => static function (self $model) {
                return $model->status === self::STATUS_SUCCESS;
            }],
            [['partner_reward_value'], 'required', 'message' => 'Укажите вознаграждение партнёру', 'when' => static function (self $model) {
                return $model->partner_id
                    && $model->status === self::STATUS_SUCCESS
                    && $model->getOldAttribute('status') !== self::STATUS_SUCCESS;
            }],
            [['status'], function ($attribute) {
                $oldStatus = (string)$this->getOldAttribute('status');
                if (!$this->isNewRecord && !in_array($this->status, $this->allowedNextStatuses(), true)) {
                    $this->addError($attribute, 'Недопустимый переход статуса из «'.(self::statuses()[$oldStatus] ?? $oldStatus).'»');
                }
            }],
        ]);
    }

    /**
     * Sorting columns are written only by crm\LeadTriage, never by a form
     * ("!" — validated but not safe for load()/setAttributes()), and their
     * rules exist only once the migration added the columns.
     */
    protected function triageRules(): array
    {
        $rules = [];
        if (static::triageReady()) {
            $rules[] = [['!merged_into_id', '!triaged_by', '!triaged_at'], 'integer'];
            $rules[] = [['!triage_result'], 'in', 'range' => array_keys(self::triageResults())];
        }
        if (static::amountReady()) {
            // «Сумма» of an open deal (m261028_120000); the success form proposes it as deal_amount.
            $rules[] = [['amount'], 'filter', 'filter' => static function ($value) {
                return is_string($value) ? (trim(str_replace([' ', ','], ['', '.'], $value)) ?: null) : $value;
            }];
            $rules[] = [['amount'], 'number', 'min' => 0, 'max' => 9999999999999999, 'skipOnEmpty' => true];
            $rules[] = [['deal_amount'], 'default', 'value' => static fn(self $model) => $model->amount ?: null,
                'when' => static fn(self $model) => $model->status === self::STATUS_SUCCESS];
        }
        if (static::pipelineReady()) {
            // П2б: the pipeline of the deal; a stage must belong to it.
            $rules[] = [['cms_lead_pipeline_id'], 'filter', 'filter' => static fn($value) => $value === '' ? null : $value];
            $rules[] = [['cms_lead_pipeline_id'], 'integer'];
            $rules[] = [['cms_lead_pipeline_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsLeadPipeline::class,
                'targetAttribute' => ['cms_lead_pipeline_id' => 'id', 'cms_site_id' => 'cms_site_id'],
                'message' => 'Воронка не найдена на этом сайте.'];
            $rules[] = [['cms_lead_stage_id'], function ($attribute) {
                $stage = $this->cms_lead_stage_id ? CmsLeadStage::findOne((int)$this->cms_lead_stage_id) : null;
                if ($stage && !$this->stageBelongsToPipeline($stage)) {
                    $this->addError($attribute, 'Этап «'.$stage->name.'» из другой воронки.');
                }
            }];
            $rules[] = [['!stage_changed_at'], 'integer'];
        }

        return $rules;
    }

    /** The pipeline columns and the history exist (П2б migration may run after the code update). */
    public static function pipelineReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = static::getDb()->getTableSchema(static::tableName(), true);
            $ready = $schema && $schema->getColumn('cms_lead_pipeline_id') !== null && CmsLeadStageHistory::ready();
        }

        return $ready;
    }

    /**
     * The pipeline the deal is in: its own, else the default one of the site
     * (deals without a pipeline belong to it); null on a site without pipelines.
     */
    public function getEffectivePipeline(): ?CmsLeadPipeline
    {
        if (!static::pipelineReady()) {
            return null;
        }
        if ($this->cms_lead_pipeline_id) {
            foreach (CmsLeadPipeline::forSite((int)$this->cms_site_id) as $pipeline) {
                if ((int)$pipeline->id === (int)$this->cms_lead_pipeline_id) {
                    return $pipeline;
                }
            }
        }

        return CmsLeadPipeline::defaultFor((int)$this->cms_site_id);
    }

    /** A stage without a pipeline belongs to the default one. */
    public function stageBelongsToPipeline(CmsLeadStage $stage): bool
    {
        if ((int)$stage->cms_site_id !== (int)$this->cms_site_id) {
            return false;
        }
        $pipeline = $this->getEffectivePipeline();
        if (!$pipeline) {
            return true;
        }
        if ($stage->cms_lead_pipeline_id) {
            return (int)$stage->cms_lead_pipeline_id === (int)$pipeline->id;
        }

        return $pipeline->isMain;
    }

    /** @return CmsLeadStage[] stages of the deal's pipeline in board order */
    public function getPipelineStages(): array
    {
        return CmsLeadStage::forPipeline($this->getEffectivePipeline(), (int)$this->cms_site_id);
    }

    /** The column «Сумма» exists (the migration may run after the code update). */
    public static function amountReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = static::getDb()->getTableSchema(static::tableName(), true);
            $ready = $schema && $schema->getColumn('amount') !== null;
        }

        return $ready;
    }

    public function transactions()
    {
        return [self::SCENARIO_DEFAULT => self::OP_INSERT | self::OP_UPDATE];
    }

    public function optimisticLock()
    {
        return 'lock_version';
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->recordStageHistory($insert, $changedAttributes);

        if (!$insert && $this->partner_id
            && $this->status === self::STATUS_SUCCESS
            && array_key_exists('status', $changedAttributes)
            && $changedAttributes['status'] !== self::STATUS_SUCCESS
        ) {
            $this->trigger(self::EVENT_PARTNER_SUCCESS);
        }

        if ($insert) {
            // A Form2 lead still has no contacts here: its creation entry is
            // recorded by the ingestion service once phones and emails exist.
            if ($this->source_type !== self::SOURCE_FORM) {
                $this->recordCreationActivity();
            }

            if ($this->suppressNotifications) {
                // One notification for the whole batch is the writer's job.
            } elseif ($this->executor_id) {
                $currentUserId = \Yii::$app->has('user') && !\Yii::$app->user->isGuest
                    ? (int)\Yii::$app->user->id
                    : 0;
                if ((int)$this->executor_id !== $currentUserId) {
                    $this->sendWebNotify(
                        (int)$this->executor_id,
                        'Вам назначена новая сделка',
                        $this->sourceNameAsText,
                        null,
                        'lead.assigned'
                    );
                }
            } elseif (!$this->deferManagerNotifications) {
                $this->notifyAvailableManagers();
            }
        }

        if (!$insert && $this->partner_id && array_key_exists('status', $changedAttributes)
            && $changedAttributes['status'] !== $this->status) {
            $this->sendWebNotify(
                (int)$this->partner_id,
                'Изменился статус партнёрской заявки',
                'Новый статус: '.$this->statusName,
                $this->getPartnerViewUrl(),
                'lead.partner_update'
            );
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if (in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_REJECTED], true) && !$this->processed_at) {
            $this->processed_at = time();
        }
        if ($this->status !== self::STATUS_REJECTED) {
            $this->reject_reason = null;
            $this->cms_lead_reject_reason_id = null;
            // A reopened sorted deal is an ordinary deal again; source events come back to it.
            if ($this->hasAttribute('triage_result')) {
                $this->triage_result = null;
                $this->merged_into_id = null;
            }
        } elseif ($this->cms_lead_reject_reason_id && trim((string)$this->reject_reason) === '') {
            // Partner cabinets, notifications and API read the text: keep it filled.
            $reason = CmsLeadRejectReason::findOne((int)$this->cms_lead_reject_reason_id);
            $this->reject_reason = $reason ? $reason->name : $this->reject_reason;
        }
        if ($this->status !== self::STATUS_SUCCESS) {
            $this->result_comment = null;
            $this->deal_amount = null;
        }
        if (static::pipelineReady()) {
            $this->preparePipeline();
        }
        $this->assignFirstStageOnStart($insert);
        if (static::pipelineReady() && ($insert || $this->isAttributeChanged('status', false)
            || $this->isAttributeChanged('cms_lead_stage_id', false) || $this->isAttributeChanged('cms_lead_pipeline_id', false))) {
            $this->stage_changed_at = time();
        }

        return true;
    }

    /**
     * A new deal goes to the default pipeline (a form, a letter, the API) unless
     * the caller chose one; a site without pipelines keeps none. A stage of
     * another pipeline is dropped when the deal moves (assignFirstStageOnStart()
     * then takes the first stage of the new one). An unsorted deal has no stage.
     */
    protected function preparePipeline(): void
    {
        if (!$this->cms_lead_pipeline_id && ($default = CmsLeadPipeline::defaultFor((int)$this->cms_site_id))) {
            $this->cms_lead_pipeline_id = (int)$default->id;
        }
        if ($this->status === self::STATUS_NEW) {
            $this->cms_lead_stage_id = null;
        } elseif ($this->cms_lead_stage_id && $this->isAttributeChanged('cms_lead_pipeline_id', false)) {
            $stage = CmsLeadStage::findOne((int)$this->cms_lead_stage_id);
            if (!$stage || !$this->stageBelongsToPipeline($stage)) {
                $this->cms_lead_stage_id = null;
            }
        }
    }

    /**
     * One history row for every change of status, pipeline or stage, in the
     * transaction of the save. The author is the signed-in employee, else the
     * creator of a new deal, else the system (console, imports).
     */
    protected function recordStageHistory(bool $insert, array $changedAttributes): void
    {
        if (!static::pipelineReady()) {
            return;
        }
        $from = [];
        foreach (['status', 'cms_lead_pipeline_id', 'cms_lead_stage_id'] as $attribute) {
            $from[$attribute] = $insert ? null
                : (array_key_exists($attribute, $changedAttributes) ? $changedAttributes[$attribute] : $this->getAttribute($attribute));
        }
        if (!$insert && (string)$from['status'] === (string)$this->status
            && (int)$from['cms_lead_pipeline_id'] === (int)$this->cms_lead_pipeline_id
            && (int)$from['cms_lead_stage_id'] === (int)$this->cms_lead_stage_id) {
            return;
        }

        $kind = CmsLeadStageHistory::KIND_STAGE;
        if ($insert) {
            $kind = CmsLeadStageHistory::KIND_CREATE;
        } elseif ($this->status === self::STATUS_SUCCESS && $from['status'] !== self::STATUS_SUCCESS) {
            $kind = CmsLeadStageHistory::KIND_SUCCESS;
        } elseif ($this->status === self::STATUS_REJECTED && $from['status'] !== self::STATUS_REJECTED) {
            $kind = CmsLeadStageHistory::KIND_REJECT;
        } elseif (in_array($from['status'], [self::STATUS_SUCCESS, self::STATUS_REJECTED], true)) {
            $kind = CmsLeadStageHistory::KIND_REOPEN;
        } elseif ($from['cms_lead_pipeline_id'] && (int)$from['cms_lead_pipeline_id'] !== (int)$this->cms_lead_pipeline_id) {
            $kind = CmsLeadStageHistory::KIND_PIPELINE;
        } elseif ($from['status'] === self::STATUS_NEW && $this->status === self::STATUS_IN_WORK) {
            $kind = CmsLeadStageHistory::KIND_TAKE;
        }

        $stageName = static function ($stageId): ?string {
            if (!$stageId) {
                return null;
            }
            $name = CmsLeadStage::find()->select(['name'])->andWhere(['id' => (int)$stageId])->scalar();

            return $name === false || $name === null ? null : (string)$name;
        };
        $actorId = \Yii::$app->has('user', true) && !\Yii::$app->user->isGuest ? (int)\Yii::$app->user->id : null;
        if (!$actorId && $insert && (int)$this->created_by > 0) {
            $actorId = (int)$this->created_by;
        }

        static::getDb()->createCommand()->insert(CmsLeadStageHistory::tableName(), [
            'cms_site_id' => (int)$this->cms_site_id,
            'cms_lead_id' => (int)$this->id,
            'created_at' => (int)$this->stage_changed_at ?: time(),
            'created_by' => $actorId,
            'kind' => $kind,
            'from_status' => $from['status'],
            'to_status' => (string)$this->status,
            'from_pipeline_id' => $from['cms_lead_pipeline_id'] ? (int)$from['cms_lead_pipeline_id'] : null,
            'to_pipeline_id' => $this->cms_lead_pipeline_id ? (int)$this->cms_lead_pipeline_id : null,
            'from_stage_id' => $from['cms_lead_stage_id'] ? (int)$from['cms_lead_stage_id'] : null,
            'to_stage_id' => $this->cms_lead_stage_id ? (int)$this->cms_lead_stage_id : null,
            'from_stage_name' => $stageName($from['cms_lead_stage_id']),
            'to_stage_name' => $stageName($this->cms_lead_stage_id),
            'is_migrated' => 0,
        ])->execute();
    }

    /** @return \yii\db\ActiveQuery transitions of the deal, oldest first */
    public function getStageHistory()
    {
        return $this->hasMany(CmsLeadStageHistory::class, ['cms_lead_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    /**
     * A lead entering "in work" (claim, manual creation with a manager,
     * reopening) without a stage starts at the first stage of its site.
     * Sites without stages keep the plain "in work" status.
     */
    protected function assignFirstStageOnStart(bool $insert): void
    {
        if ($this->status !== self::STATUS_IN_WORK || $this->cms_lead_stage_id) {
            return;
        }
        $movedPipeline = static::pipelineReady() && $this->isAttributeChanged('cms_lead_pipeline_id', false);
        if (!$insert && $this->getOldAttribute('status') === self::STATUS_IN_WORK && !$movedPipeline) {
            return;
        }
        try {
            if (static::pipelineReady()) {
                $stages = $this->getPipelineStages();
                $stageId = $stages ? (int)$stages[0]->id : null;
            } else {
                $stageId = CmsLeadStage::find()
                    ->cmsSite((int)$this->cms_site_id)
                    ->select(['id'])
                    ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
                    ->scalar();
            }
        } catch (\yii\db\Exception $e) {
            // The stage table appears with a migration that may run after the code update.
            return;
        }
        if ($stageId) {
            $this->cms_lead_stage_id = (int)$stageId;
        }
    }

    /**
     * Readable creation entry of the lead activity stream. It is written
     * exactly once per lead: directly from the lifecycle for sources that
     * persist a complete lead in one save, and explicitly from the ingestion
     * service for Form2 leads whose contacts are stored after the lead row.
     */
    public function recordCreationActivity(): CmsLog
    {
        if ($this->_creationActivityLog !== null) {
            return $this->_creationActivityLog;
        }

        return $this->_creationActivityLog = $this->addSystemActivity(
            $this->creationActivityMessage(),
            $this->creationActivityActorId()
        );
    }

    /**
     * Stores one system entry in the shared lead activity stream. The caller
     * owns the surrounding transaction: a rejected entry aborts it instead of
     * leaving the activity stream out of sync with the domain change.
     */
    public function addSystemActivity(string $message, ?int $actorId = null): CmsLog
    {
        $log = new CmsLog([
            'log_type' => CmsLog::LOG_TYPE_COMMENT,
            'model_code' => $this->skeeksModelCode,
            'model_id' => (int)$this->id,
            'model_as_text' => $this->asText,
            'cms_company_id' => $this->cms_company_id ? (int)$this->cms_company_id : null,
            'cms_user_id' => $this->cms_user_id ? (int)$this->cms_user_id : null,
            'comment' => $message,
        ]);

        if ($actorId) {
            // BlameableBehavior evaluates the author on insert and would
            // replace an explicit value with the current session user.
            $blameable = $log->getBehavior(BlameableBehavior::class);
            if ($blameable instanceof BlameableBehavior) {
                $blameable->value = $actorId;
            }
            $log->created_by = $actorId;
        }

        if (!$log->save()) {
            throw new \RuntimeException('Не удалось сохранить запись активности сделки: '.implode('; ', $log->getFirstErrors()));
        }

        return $log;
    }

    protected function creationActivityMessage(): string
    {
        $name = Html::encode((string)$this->name);

        if ($this->source_type === self::SOURCE_FORM) {
            return $this->formCreationActivityMessage($name);
        }

        $actor = $this->creationActivityActorName();

        if ($this->source_type === self::SOURCE_PARTNER) {
            return $actor === null
                ? 'Добавлена сделка «'.$name.'»'
                : $actor.' добавил сделку «'.$name.'»';
        }

        return $actor === null
            ? 'Создана сделка «'.$name.'»'
            : $actor.' создал сделку «'.$name.'»';
    }

    /**
     * The Form2 entry names the submitted form, its submission number and the
     * contact identity that the ingestion service has just persisted.
     */
    private function formCreationActivityMessage(string $encodedName): string
    {
        $message = 'Отправлена форма';

        $formName = trim((string)$this->source_name);
        if ($formName !== '') {
            $message .= ' «'.Html::encode($formName).'»';
        }

        $sourceData = is_array($this->source_data) ? $this->source_data : [];
        $sendId = (int)ArrayHelper::getValue($sourceData, 'form_send_id', 0);
        if ($sendId <= 0) {
            $sendId = (int)$this->source_ref;
        }
        if ($sendId > 0) {
            $message .= ' №'.$sendId;
        }

        $mainPhone = $this->mainPhone;
        $contacts = array_values(array_filter([
            $encodedName,
            $mainPhone ? Html::encode((string)$mainPhone->value) : '',
        ], static function ($part) {
            return $part !== '';
        }));

        return $contacts ? $message.': '.implode(', ', $contacts) : $message;
    }

    private function creationActivityActorId(): ?int
    {
        foreach ([$this->created_by, $this->submitted_by_id, $this->partner_id] as $candidate) {
            if ((int)$candidate > 0) {
                return (int)$candidate;
            }
        }

        return null;
    }

    private function creationActivityActorName(): ?string
    {
        $actorId = $this->creationActivityActorId();
        if (!$actorId) {
            return null;
        }

        $actor = CmsUser::findOne($actorId);

        return $actor ? Html::encode((string)$actor->shortDisplayName) : null;
    }

    public function notifyPartnerAboutComment(?int $logId = null): void
    {
        if ($this->partner_id) {
            $this->sendWebNotify(
                (int)$this->partner_id,
                'Новый комментарий по партнёрской заявке',
                null,
                $this->getPartnerViewUrl($logId),
                'lead.partner_update'
            );
        }
    }

    public function notifyManagersAboutPartnerComment(): void
    {
        if ($this->executor_id) {
            $this->sendWebNotify((int)$this->executor_id, 'Новый комментарий партнёра по сделке', null, null, 'lead.partner_comment');
            return;
        }

        foreach ($this->availableManagerIds() as $userId) {
            $this->sendWebNotify($userId, 'Новый комментарий партнёра по нераспределённой сделке', null, null, 'lead.partner_comment');
        }
    }

    public function notifyAvailableManagers(): void
    {
        foreach ($this->availableManagerIds() as $userId) {
            if (CmsWebNotify::find()->andWhere([
                'cms_user_id' => $userId,
                'model_code' => $this->skeeksModelCode,
                'model_id' => (int)$this->id,
                'name' => 'Новая сделка в Неразобранном',
            ])->exists()) {
                continue;
            }
            $this->sendWebNotify($userId, 'Новая сделка в Неразобранном', $this->sourceNameAsText);
        }
    }

    /** Active employees with lead permission and the same scope as the lead grid. */
    public function availableManagerIds(): array
    {
        $query = CmsUser::find()->isWorker()
            ->andWhere([CmsUser::tableName().'.is_active' => 1]);
        if ($this->cms_site_id) {
            $query->cmsSite((int)$this->cms_site_id);
        }

        $userIds = [];
        foreach ($query->all() as $worker) {
            if (!\Yii::$app->authManager->checkAccess($worker->id, 'cms/admin-lead')) {
                continue;
            }
            if (self::find()->forManager($worker)
                ->andWhere([self::tableName().'.id' => (int)$this->id])->exists()) {
                $userIds[] = (int)$worker->id;
            }
        }
        return array_values(array_unique($userIds));
    }

    protected function sendWebNotify(
        int $userId,
        string $name,
        ?string $comment = null,
        ?string $url = null,
        string $kind = 'lead.new'
    ): void
    {
        \Yii::$app->notifications->notify($userId, $kind, [
            'name'    => $name,
            'comment' => $comment,
            'url'     => $url,
            'model'   => $this,
        ]);
    }

    /**
     * Builds a link to the partner-facing lead card independently of the
     * backend surface in which the notification was created.
     */
    public function getPartnerViewUrl(?int $logId = null): string
    {
        $urlPrefix = '~upa';
        if (\Yii::$app->has('upa')) {
            $urlPrefix = (string)ArrayHelper::getValue(
                \Yii::$app->upa->urlRule,
                'urlPrefix',
                $urlPrefix
            );
        }

        $baseUrl = \Yii::$app->has('request') ? (string)\Yii::$app->request->baseUrl : '';
        $url = rtrim($baseUrl, '/').'/'.trim($urlPrefix, '/')
            .'/shop/upa-partner-lead/view?'.http_build_query(['pk' => (int)$this->id]);

        if ($logId) {
            $url .= '&'.http_build_query(['sx-log-id' => $logId]).'#sx-log-'.$logId;
        }

        return $url;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'Новый',
            self::STATUS_IN_WORK => 'В работе',
            self::STATUS_SUCCESS => 'Успешный',
            self::STATUS_REJECTED => 'Отклонён',
        ];
    }

    /**
     * A lead is first explicitly claimed and only then completed or rejected.
     * A completed lead may be explicitly returned to work from its card.
     */
    public function allowedNextStatuses(): array
    {
        $oldStatus = (string)$this->getOldAttribute('status');
        return match ($oldStatus) {
            // An unsorted deal closes without work only by sorting (spam, added elsewhere, support).
            self::STATUS_NEW => $this->getTriageResult()
                ? [self::STATUS_NEW, self::STATUS_IN_WORK, self::STATUS_REJECTED]
                : [self::STATUS_NEW, self::STATUS_IN_WORK],
            self::STATUS_IN_WORK => [self::STATUS_IN_WORK, self::STATUS_SUCCESS, self::STATUS_REJECTED],
            self::STATUS_SUCCESS => [self::STATUS_SUCCESS, self::STATUS_IN_WORK],
            self::STATUS_REJECTED => [self::STATUS_REJECTED, self::STATUS_IN_WORK],
            default => array_keys(self::statuses()),
        };
    }

    /**
     * Status names for employees: a new deal is "Неразобранное" (as in amoCRM),
     * the outcomes are «Успешно» and «Отказ».
     * Partners and clients keep statuses().
     */
    public static function staffStatuses(): array
    {
        // П2б: the outcomes as employees know them from amoCRM and Bitrix24.
        return array_replace(self::statuses(), [
            self::STATUS_NEW => 'Неразобранное',
            self::STATUS_SUCCESS => 'Успешно',
            self::STATUS_REJECTED => 'Отказ',
        ]);
    }

    public static function triageResults(): array
    {
        return [
            self::TRIAGE_MERGED => 'Добавлено в другую сделку',
            self::TRIAGE_SUPPORT => 'Передано в поддержку',
            self::TRIAGE_SPAM => 'Спам',
        ];
    }

    /** Employee name of the state: the sorting result of a sorted deal, else the status. */
    public function getStaffStatusName(): string
    {
        if ($this->status === self::STATUS_REJECTED && ($result = $this->getTriageResult())) {
            return self::triageResults()[$result] ?? $result;
        }

        return self::staffStatuses()[$this->status] ?? $this->status;
    }

    /** The sorting columns exist (the migration may run after the code update). */
    public static function triageReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = static::getDb()->getTableSchema(static::tableName(), true);
            $ready = $schema && $schema->getColumn('triage_result') !== null;
        }

        return $ready;
    }

        /**
     * Where the work on this request goes on: the deal it was added to (the
     * chain is followed), else the deal itself. Repeated source events land there.
     */
    public function resolveMerged(): self
    {
        $lead = $this;
        $seen = [];
        while ($lead->hasAttribute('merged_into_id') && $lead->merged_into_id && !isset($seen[(int)$lead->id]) && count($seen) < 10) {
            $seen[(int)$lead->id] = true;
            $next = self::findOne((int)$lead->merged_into_id);
            if (!$next) {
                break;
            }
            $lead = $next;
        }

        return $lead;
    }

        /** Sorting result or null; null also before the sorting migration. */
    public function getTriageResult(): ?string
    {
        return $this->hasAttribute('triage_result') && $this->getAttribute('triage_result')
            ? (string)$this->getAttribute('triage_result')
            : null;
    }

        public function getIsUnsorted(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    public static function statusDescriptions(): array
    {
        return [
            self::STATUS_NEW => 'Сделка получена и ожидает ответственного менеджера.',
            self::STATUS_IN_WORK => 'Ответственный менеджер уже работает с обращением.',
            self::STATUS_SUCCESS => 'Обращение успешно обработано.',
            self::STATUS_REJECTED => 'Обращение закрыто без результата.',
        ];
    }

    public static function sources(): array
    {
        return [
            self::SOURCE_MANUAL => 'Создан вручную',
            self::SOURCE_PARTNER => 'Партнёрская программа',
            self::SOURCE_FORM => 'Конструктор форм',
            self::SOURCE_VK => 'VK',
            self::SOURCE_TELEGRAM => 'Telegram',
            self::SOURCE_MAX => 'MAX',
            self::SOURCE_EMAIL => 'Электронная почта',
            self::SOURCE_PHONE => 'Телефония',
            self::SOURCE_IMPORT => 'Импорт',
            self::SOURCE_MAILING => 'Рассылка',
            self::SOURCE_API => 'API',
        ];
    }

    public static function utmLabels(): array
    {
        return [
            'utm_source' => 'UTM source',
            'utm_medium' => 'UTM medium',
            'utm_campaign' => 'UTM campaign',
            'utm_content' => 'UTM content',
            'utm_term' => 'UTM term',
        ];
    }

    public static function statusCssClass(string $status): string
    {
        return [
            self::STATUS_NEW => 'sx-status',
            self::STATUS_IN_WORK => 'sx-status sx-status--accent',
            self::STATUS_SUCCESS => 'sx-status sx-status--success',
            self::STATUS_REJECTED => 'sx-status sx-status--danger',
        ][$status] ?? 'sx-status';
    }

    public static function statusIconClass(string $status): string
    {
        return [
            self::STATUS_NEW => 'fas fa-plus',
            self::STATUS_IN_WORK => 'fas fa-play',
            self::STATUS_SUCCESS => 'fas fa-check',
            self::STATUS_REJECTED => 'fas fa-times-circle',
        ][$status] ?? 'fas fa-circle';
    }

    public function getStatusName(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function getSourceNameAsText(): string
    {
        return $this->source_name ?: (self::sources()[$this->source_type] ?? $this->source_type);
    }

    /** Deal amount for employees, e.g. «50 000 ₽»; empty when not set. */
    public function getDealAmountAsText(): string
    {
        return self::moneyText($this->deal_amount);
    }

    /** «Сумма» (expected) of the deal, e.g. «50 000 ₽»; empty when not set or before the migration. */
    public function getAmountAsText(): string
    {
        return $this->hasAttribute('amount') ? self::moneyText($this->getAttribute('amount')) : '';
    }

    /**
     * The one amount an employee sees (owner's decision П2а): a successful deal
     * shows «Сумма сделки» (the result), every other one «Сумма» (expected).
     *
     * @return array [label, text]; text '' when there is no amount to show
     */
    public function getShownAmount(): array
    {
        return $this->status === self::STATUS_SUCCESS
            ? ['Сумма сделки', $this->dealAmountAsText]
            : ['Сумма', $this->amountAsText];
    }

    /** "ожидали …" under the result of a successful deal when «Сумма» was different. */
    public function getExpectedAmountNote(): string
    {
        if ($this->status !== self::STATUS_SUCCESS || !$this->hasAttribute('amount')) {
            return '';
        }
        $expected = $this->getAttribute('amount');
        if ($expected === null || $expected === '' || (float)$expected === (float)$this->deal_amount) {
            return '';
        }

        return 'ожидали '.$this->amountAsText;
    }

    private static function moneyText($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $amount = (float)$value;

        return \Yii::$app->formatter->asDecimal($amount, floor($amount) == $amount ? 0 : 2).' ₽';
    }

    public function getDisplayName(): string
    {
        return (string)$this->name;
    }

    public function getCanBeClaimed(): bool
    {
        return $this->status === self::STATUS_NEW && !$this->executor_id;
    }

    public function isManagedBy(int $userId): bool
    {
        return (int)$this->executor_id === $userId;
    }

    public function canBeWorkedBy(int $userId): bool
    {
        if ($this->isTerminal) {
            return false;
        }
        if (\Yii::$app->authManager->checkAccess($userId, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            return true;
        }
        return $this->isManagedBy($userId);
    }

    public function canBeReopenedBy(int $userId): bool
    {
        if (!$this->isTerminal) {
            return false;
        }
        if (\Yii::$app->authManager->checkAccess($userId, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)) {
            return true;
        }
        return $this->isManagedBy($userId);
    }

    public function getIsTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_REJECTED], true);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name' => 'Название сделки',
            'description' => 'Описание',
            'status' => 'Статус',
            'source_type' => 'Источник',
            'source_name' => 'Название источника',
            'source_url' => 'Страница обращения',
            'utm_source' => 'UTM source',
            'utm_medium' => 'UTM medium',
            'utm_campaign' => 'UTM campaign',
            'utm_content' => 'UTM content',
            'utm_term' => 'UTM term',
            'executor_id' => 'Ответственный менеджер',
            'partner_id' => 'Партнёр',
            'cms_company_id' => 'Компания',
            'cms_user_id' => 'Клиент',
            'partner_reward_value' => 'Вознаграждение партнёру, бонусов',
            'result_comment' => 'Результат обработки',
            'deal_amount' => 'Сумма сделки, ₽',
            'amount' => 'Сумма, ₽',
            'reject_reason' => 'Причина отклонения',
            'no_answer_count' => 'Неудачных попыток дозвониться',
            'cms_lead_stage_id' => 'Этап',
            'cms_lead_pipeline_id' => 'Воронка',
            'stage_changed_at' => 'На этапе с',
            'cms_lead_reject_reason_id' => 'Причина отказа',
            'triage_result' => 'Итог разбора',
            'merged_into_id' => 'Добавлено в сделку',
            'triaged_by' => 'Кто разобрал',
            'triaged_at' => 'Когда разобрана',
        ]);
    }

    public function getExecutor() { return $this->hasOne(CmsUser::class, ['id' => 'executor_id']); }
    public function getPartner() { return $this->hasOne(CmsUser::class, ['id' => 'partner_id']); }
    public function getSubmittedBy() { return $this->hasOne(CmsUser::class, ['id' => 'submitted_by_id']); }
    public function getCompany() { return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']); }
    public function getClient() { return $this->hasOne(CmsUser::class, ['id' => 'cms_user_id']); }
    public function getStage() { return $this->hasOne(CmsLeadStage::class, ['id' => 'cms_lead_stage_id']); }
    public function getPipeline() { return $this->hasOne(CmsLeadPipeline::class, ['id' => 'cms_lead_pipeline_id']); }
    public function getMergedInto() { return $this->hasOne(self::class, ['id' => 'merged_into_id']); }
    public function getTriagedBy() { return $this->hasOne(CmsUser::class, ['id' => 'triaged_by']); }
    public function getRejectReasonItem() { return $this->hasOne(CmsLeadRejectReason::class, ['id' => 'cms_lead_reject_reason_id']); }
    public function getPhones() { return $this->hasMany(CmsLeadPhone::class, ['cms_lead_id' => 'id'])->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]); }
    public function getEmails() { return $this->hasMany(CmsLeadEmail::class, ['cms_lead_id' => 'id'])->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]); }
    public function getAddresses() { return $this->hasMany(CmsLeadAddress::class, ['cms_lead_id' => 'id'])->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]); }
    public function getLinks() { return $this->hasMany(CmsLeadLink::class, ['cms_lead_id' => 'id'])->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]); }
    public function getLead2contractors() { return $this->hasMany(CmsLead2contractor::class, ['cms_lead_id' => 'id'])->orderBy(['id' => SORT_ASC]); }
    public function getContractors() { return $this->hasMany(CmsContractor::class, ['id' => 'cms_contractor_id'])->via('lead2contractors'); }
    /** @return \skeeks\cms\models\queries\CmsTaskQuery */
    public function getTasks() { return $this->hasMany(CmsTask::class, ['cms_lead_id' => 'id']); }

    /**
     * Open tasks, nearest first; tasks without a fixed start come last.
     *
     * @return \skeeks\cms\models\queries\CmsTaskQuery
     */
    public function getActiveTasks()
    {
        $table = CmsTask::tableName();

        return $this->getTasks()
            ->andWhere([$table.'.status' => CmsTask::activeStatuses()])
            ->orderBy(new \yii\db\Expression("{$table}.plan_start_at IS NULL, {$table}.plan_start_at ASC, {$table}.id ASC"));
    }

    /** The planned next step of a lead in work, if any. */
    public function getNextTask(): ?CmsTask
    {
        return $this->activeTasks[0] ?? null;
    }

    /** A lead in work must have an open task that says when to act next. */
    public function getIsNextStepMissing(): bool
    {
        return $this->status === self::STATUS_IN_WORK && !$this->nextTask;
    }

    public function getIsNextStepOverdue(): bool
    {
        $task = $this->nextTask;

        return $task && $task->plan_start_at && (int)$task->plan_start_at < time();
    }

    public function getMainPhone(): ?CmsLeadPhone
    {
        return $this->phones[0] ?? null;
    }

    public function getMainEmail(): ?CmsLeadEmail
    {
        return $this->emails[0] ?? null;
    }

    public static function find()
    {
        return new CmsLeadQuery(static::class);
    }
}

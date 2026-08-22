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
    public const STATUS_NEW = 'new';
    public const STATUS_IN_WORK = 'in_work';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_REJECTED = 'rejected';

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
            ],
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['source_type'], 'default', 'value' => self::SOURCE_MANUAL],
            [['status'], 'default', 'value' => self::STATUS_NEW],
            [['lock_version'], 'default', 'value' => 0],
            [['cms_site_id'], 'default', 'value' => static function () {
                return \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
            }],
            [['name'], 'filter', 'filter' => 'trim'],
            [['description', 'source_ref', 'source_name', 'source_url'], 'default', 'value' => null],
            [['name', 'source_type', 'status'], 'required'],
            [['cms_site_id', 'submitted_by_id', 'partner_id', 'executor_id', 'cms_company_id', 'cms_user_id', 'processed_at', 'lock_version'], 'integer'],
            [['description', 'reject_reason', 'result_comment'], 'string'],
            [['partner_reward_value'], 'number', 'min' => 0.01, 'skipOnEmpty' => true],
            [['source_data'], 'safe'],
            [['name', 'source_name', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'], 'string', 'max' => 255],
            [['source_type', 'status'], 'string', 'max' => 32],
            [['source_ref'], 'string', 'max' => 190],
            [['source_url'], 'string', 'max' => 1000],
            [['source_type'], 'in', 'range' => array_keys(self::sources())],
            [['status'], 'in', 'range' => array_keys(self::statuses())],
            [['cms_company_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsCompany::class, 'targetAttribute' => 'id'],
            [['cms_user_id', 'submitted_by_id', 'partner_id', 'executor_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsUser::class, 'targetAttribute' => 'id'],
            [['executor_id'], 'required', 'when' => static function (self $model) {
                return $model->status !== self::STATUS_NEW;
            }],
            [['reject_reason'], 'required', 'message' => 'Укажите причину отклонения', 'when' => static function (self $model) {
                return $model->status === self::STATUS_REJECTED;
            }],
            [['result_comment'], 'required', 'message' => 'Укажите результат обработки лида', 'when' => static function (self $model) {
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

            if ($this->executor_id) {
                $currentUserId = \Yii::$app->has('user') && !\Yii::$app->user->isGuest
                    ? (int)\Yii::$app->user->id
                    : 0;
                if ((int)$this->executor_id !== $currentUserId) {
                    $this->sendWebNotify(
                        (int)$this->executor_id,
                        'Вам назначен новый лид',
                        $this->sourceNameAsText
                    );
                }
            } else {
                $this->notifyAvailableManagers();
            }
        }

        if (!$insert && $this->partner_id && array_key_exists('status', $changedAttributes)
            && $changedAttributes['status'] !== $this->status) {
            $this->sendWebNotify(
                (int)$this->partner_id,
                'Изменился статус партнёрской заявки',
                'Новый статус: '.$this->statusName,
                $this->getPartnerViewUrl()
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
        }
        if ($this->status !== self::STATUS_SUCCESS) {
            $this->result_comment = null;
        }

        return true;
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
            throw new \RuntimeException('Не удалось сохранить запись активности лида: '.implode('; ', $log->getFirstErrors()));
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
                ? 'Добавлен лид «'.$name.'»'
                : $actor.' добавил лид «'.$name.'»';
        }

        return $actor === null
            ? 'Создан лид «'.$name.'»'
            : $actor.' создал лид «'.$name.'»';
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
                $this->getPartnerViewUrl($logId)
            );
        }
    }

    public function notifyManagersAboutPartnerComment(): void
    {
        if ($this->executor_id) {
            $this->sendWebNotify((int)$this->executor_id, 'Новый комментарий партнёра по лиду');
            return;
        }

        foreach ($this->availableManagerIds() as $userId) {
            $this->sendWebNotify($userId, 'Новый комментарий партнёра по нераспределённому лиду');
        }
    }

    protected function notifyAvailableManagers(): void
    {
        foreach ($this->availableManagerIds() as $userId) {
            $this->sendWebNotify($userId, 'Появился новый лид', $this->sourceNameAsText);
        }
    }

    /**
     * Notify only employees who own the submitter/partner directly or through
     * one of their companies. If a known identity has no eligible responsible
     * manager, active administrators become the narrow triage fallback.
     * Anonymous leads remain available to the common queue of employees with
     * lead access.
     */
    public function availableManagerIds(): array
    {
        $contactUserIds = array_filter([(int)$this->submitted_by_id, (int)$this->partner_id]);
        $userIds = [];
        if ($contactUserIds) {
            $contactUserIds = array_values(array_unique($contactUserIds));
            $userIds = CmsUser2manager::find()
                ->select('worker_id')
                ->andWhere(['client_id' => $contactUserIds])
                ->column();
            $companyIds = CmsCompany2user::find()
                ->select('cms_company_id')
                ->andWhere(['cms_user_id' => $contactUserIds])
                ->column();
            if ($companyIds) {
                $userIds = array_merge($userIds, CmsCompany2manager::find()
                    ->select('cms_user_id')
                    ->andWhere(['cms_company_id' => array_values(array_unique(array_map('intval', $companyIds)))])
                    ->column());
            }
        } else {
            $query = CmsUser::find()
                ->select(CmsUser::tableName().'.id')
                ->isWorker()
                ->andWhere([CmsUser::tableName().'.is_active' => 1]);
            if ($this->cms_site_id) {
                $query->cmsSite((int)$this->cms_site_id);
            }
            $userIds = $query->column();
        }

        if ($this->cms_site_id && $userIds) {
            $userIds = CmsUser::find()
                ->cmsSite((int)$this->cms_site_id)
                ->andWhere([CmsUser::tableName().'.id' => array_values(array_unique(array_map('intval', $userIds)))])
                ->select(CmsUser::tableName().'.id')
                ->column();
        }

        $userIds = array_values(array_filter(array_unique(array_map('intval', $userIds)), static function ($userId) {
            return \Yii::$app->authManager->checkAccess($userId, 'cms/admin-lead');
        }));

        if (!$userIds && $contactUserIds) {
            $query = CmsUser::find()
                ->select(CmsUser::tableName().'.id')
                ->isWorker()
                ->andWhere([CmsUser::tableName().'.is_active' => 1]);
            if ($this->cms_site_id) {
                $query->cmsSite((int)$this->cms_site_id);
            }

            $userIds = array_values(array_filter(array_map('intval', $query->column()), static function ($userId) {
                return \Yii::$app->authManager->checkAccess($userId, CmsManager::PERMISSION_ROLE_ADMIN_ACCESS)
                    && \Yii::$app->authManager->checkAccess($userId, 'cms/admin-lead');
            }));
        }

        return $userIds;
    }

    protected function sendWebNotify(
        int $userId,
        string $name,
        ?string $comment = null,
        ?string $url = null
    ): void
    {
        $notify = new CmsWebNotify();
        $notify->cms_user_id = $userId;
        $notify->name = $name;
        $notify->comment = $comment;
        $notify->model_id = (int)$this->id;
        $notify->model_code = $this->skeeksModelCode;
        $notify->url = $url;
        $notify->save();
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
     */
    public function allowedNextStatuses(): array
    {
        $oldStatus = (string)$this->getOldAttribute('status');
        return match ($oldStatus) {
            self::STATUS_NEW => [self::STATUS_NEW, self::STATUS_IN_WORK],
            self::STATUS_IN_WORK => [self::STATUS_IN_WORK, self::STATUS_SUCCESS, self::STATUS_REJECTED],
            self::STATUS_SUCCESS => [self::STATUS_SUCCESS],
            self::STATUS_REJECTED => [self::STATUS_REJECTED],
            default => array_keys(self::statuses()),
        };
    }

    public static function statusDescriptions(): array
    {
        return [
            self::STATUS_NEW => 'Лид получен и ожидает ответственного менеджера.',
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
            self::SOURCE_PHONE => 'Телефония',
            self::SOURCE_IMPORT => 'Импорт',
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

    public function getIsTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_REJECTED], true);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name' => 'Название лида',
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
            'reject_reason' => 'Причина отклонения',
        ]);
    }

    public function getExecutor() { return $this->hasOne(CmsUser::class, ['id' => 'executor_id']); }
    public function getPartner() { return $this->hasOne(CmsUser::class, ['id' => 'partner_id']); }
    public function getSubmittedBy() { return $this->hasOne(CmsUser::class, ['id' => 'submitted_by_id']); }
    public function getCompany() { return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']); }
    public function getClient() { return $this->hasOne(CmsUser::class, ['id' => 'cms_user_id']); }
    public function getPhones() { return $this->hasMany(CmsLeadPhone::class, ['cms_lead_id' => 'id'])->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]); }
    public function getEmails() { return $this->hasMany(CmsLeadEmail::class, ['cms_lead_id' => 'id'])->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]); }

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

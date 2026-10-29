<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\query\CmsActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * A sales pipeline (воронка) of deals (CmsLead) with its own stages (П2б).
 *
 * A site without pipelines has one implicit pipeline: stages and deals
 * without `cms_lead_pipeline_id`. Once pipelines exist, such stages and
 * deals belong to the default pipeline (`is_default`), so a site may start
 * using pipelines without a data migration.
 *
 * The four system statuses of CmsLead stay authoritative: «Неразобранное» is
 * status `new`, the stages split `in_work`, «Успешно»/«Отказ» are the
 * terminal statuses. `success_rule` only suggests closing; a person closes.
 *
 * The queue of «Неразобранное» (п. 13б, crm\InboundQueue): the sources whose
 * new requests land in the pipeline (`inbound_source_types`, forSource()) and
 * who sorts them — employees, departments and the mode; without them the
 * CRM settings «Входящие заявки» are the default.
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_site_id
 * @property string      $name
 * @property string|null $code
 * @property int         $sort
 * @property int         $is_default
 * @property string      $success_rule
 * @property string|null $inbound_source_types JSON of CmsLead::sources() keys
 * @property string|null $inbound_worker_ids JSON
 * @property string|null $inbound_department_ids JSON
 * @property string|null $inbound_mode crm\InboundQueue::MODE_ALL | MODE_ROUND; null — the CRM settings
 *
 * @property int[]    $inboundWorkerIds
 * @property int[]    $inboundDepartmentIds
 * @property string[] $inboundSourceTypes
 *
 * @property CmsLeadStage[] $stages
 */
class CmsLeadPipeline extends ActiveRecord
{
    public const SUCCESS_MANUAL = 'manual';
    public const SUCCESS_ORDER = 'order';
    public const SUCCESS_PAYMENT = 'payment';

    /** Codes of the default pipelines (cms/lead-pipeline/init). */
    public const CODE_INBOUND = 'inbound';
    public const CODE_OUTBOUND = 'outbound';

    /** @var array site id => pipelines of the request; cleared by every save and delete */
    private static array $_sites = [];

    /** Forgets the pipelines read in this request (after changes made outside the model). */
    public static function clearCache(): void
    {
        static::$_sites = [];
    }

    public function afterDelete()
    {
        parent::afterDelete();
        static::$_sites = [];
    }

    public static function tableName()
    {
        return '{{%cms_lead_pipeline}}';
    }

    public static function successRules(): array
    {
        return [
            self::SUCCESS_MANUAL => 'Вручную',
            self::SUCCESS_ORDER => 'Подтверждённый заказ',
            self::SUCCESS_PAYMENT => 'Первая оплата',
        ];
    }

    public static function successRuleHints(): array
    {
        return [
            self::SUCCESS_MANUAL => 'Менеджер сам решает, когда сделка успешна.',
            self::SUCCESS_ORDER => 'Когда по сделке есть подтверждённый заказ, в карточке появится предложение закрыть её успешно.',
            self::SUCCESS_PAYMENT => 'Когда клиент сделки впервые оплатил счёт, в карточке появится предложение закрыть её успешно.',
        ];
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_site_id'], 'default', 'value' => static function () {
                return \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
            }],
            [['name'], 'filter', 'filter' => 'trim'],
            [['code'], 'filter', 'filter' => static fn($value) => trim((string)$value) === '' ? null : trim((string)$value)],
            [['sort'], 'default', 'value' => 100],
            [['is_default'], 'default', 'value' => 0],
            [['success_rule'], 'default', 'value' => self::SUCCESS_MANUAL],
            [['name', 'cms_site_id', 'success_rule'], 'required'],
            [['cms_site_id', 'sort'], 'integer'],
            [['is_default'], 'boolean'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 32],
            [['code'], 'match', 'pattern' => '/^[a-z0-9_-]+$/', 'message' => 'Код — латинские буквы, цифры, «-» и «_».'],
            [['success_rule'], 'in', 'range' => array_keys(self::successRules())],
            [['name'], 'unique', 'targetAttribute' => ['cms_site_id', 'name'], 'message' => 'Такая воронка уже есть.'],
            [['code'], 'unique', 'targetAttribute' => ['cms_site_id', 'code'], 'skipOnEmpty' => true],
        ], static::inboundReady() ? [
            [['inboundWorkerIds', 'inboundDepartmentIds', 'inboundSourceTypes'], 'safe'],
            [['inbound_mode'], 'filter', 'filter' => static fn($value) => $value === '' ? null : $value],
            [['inbound_mode'], 'in', 'range' => array_keys(\skeeks\cms\crm\InboundQueue::modes())],
        ] : []);
    }

    /** The columns of the queue exist (the migration may run after the code update). */
    public static function inboundReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = static::ready() ? static::getDb()->getTableSchema(static::tableName(), true) : null;
            $ready = $schema !== null && $schema->getColumn('inbound_worker_ids') !== null;
        }

        return $ready;
    }

    private function jsonList(string $column, bool $ints): array
    {
        if (!$this->hasAttribute($column) || !$this->{$column}) {
            return [];
        }
        $values = (array)json_decode((string)$this->{$column}, true);

        return array_values(array_filter($ints ? array_map('intval', $values) : array_map('strval', $values)));
    }

    private function setJsonList(string $column, $values, bool $ints): void
    {
        if (!$this->hasAttribute($column)) {
            return;
        }
        $values = array_values(array_unique(array_filter($ints ? array_map('intval', (array)$values) : array_map('strval', (array)$values))));
        $this->{$column} = $values ? json_encode($values) : null;
    }

    public function getInboundWorkerIds(): array
    {
        return $this->jsonList('inbound_worker_ids', true);
    }

    public function setInboundWorkerIds($values): void
    {
        $this->setJsonList('inbound_worker_ids', $values, true);
    }

    public function getInboundDepartmentIds(): array
    {
        return $this->jsonList('inbound_department_ids', true);
    }

    public function setInboundDepartmentIds($values): void
    {
        $this->setJsonList('inbound_department_ids', $values, true);
    }

    public function getInboundSourceTypes(): array
    {
        return $this->jsonList('inbound_source_types', false);
    }

    public function setInboundSourceTypes($values): void
    {
        $this->setJsonList('inbound_source_types', $values, false);
    }

    /** The pipeline has its own queue (employees or departments); otherwise the CRM settings decide. */
    public function getHasOwnQueue(): bool
    {
        return (bool)($this->inboundWorkerIds || $this->inboundDepartmentIds);
    }

    /**
     * The pipeline a new request of the source lands in: the first one (switcher order) whose sources
     * include it, else the default one; null on a site without pipelines.
     */
    public static function forSource(?int $siteId, ?string $sourceType): ?self
    {
        if ($sourceType !== null && $sourceType !== '' && static::inboundReady()) {
            foreach (static::forSite($siteId) as $pipeline) {
                if (in_array($sourceType, $pipeline->inboundSourceTypes, true)) {
                    return $pipeline;
                }
            }
        }

        return static::defaultFor($siteId);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name' => 'Название',
            'code' => 'Код',
            'sort' => 'Порядок',
            'is_default' => 'Основная',
            'success_rule' => 'Критерий успеха',
            'inboundSourceTypes' => 'Заявки из источников',
            'inboundWorkerIds' => 'Кто разбирает: сотрудники',
            'inboundDepartmentIds' => 'Кто разбирает: отделы',
            'inbound_mode' => 'Как распределять',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'name' => 'Например: «Входящие», «Исходящие продажи», «Разработка сайтов».',
            'is_default' => 'В основную воронку попадают заявки с форм, письма и другие новые обращения. Основная воронка одна.',
            'success_rule' => 'Автоматический критерий только предлагает закрыть сделку, закрывает её менеджер.',
            'sort' => 'Воронки идут в переключателе по возрастанию числа.',
            'code' => 'Необязательно. Нужен скриптам и интеграциям.',
            'inboundSourceTypes' => 'Новые заявки из этих источников попадают в эту воронку. Остальные — в основную.',
            'inboundWorkerIds' => 'Кто разбирает «Неразобранное» этой воронки. Если никого не выбрать, действуют настройки CRM «Входящие заявки».',
            'inboundDepartmentIds' => 'Все сотрудники выбранных отделов тоже разбирают заявки этой воронки.',
            'inbound_mode' => '«Видят все из списка» — каждый видит новые заявки и получает уведомление. «По кругу» — заявка сразу назначается следующему сотруднику очереди.',
        ]);
    }

    /** The default pipeline is unique on a site: setting it clears the others. */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        static::$_sites = [];
        if ($this->is_default) {
            static::updateAll(['is_default' => 0], ['and',
                ['cms_site_id' => (int)$this->cms_site_id],
                ['!=', 'id', (int)$this->id],
            ]);
        }
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        if ($this->is_default) {
            $this->addError('name', 'Основную воронку удалить нельзя. Сначала сделайте основной другую.');
            return false;
        }
        if (CmsLead::find()->andWhere(['cms_lead_pipeline_id' => (int)$this->id])->exists()) {
            $this->addError('name', 'В этой воронке есть сделки. Сначала перенесите их в другую воронку.');
            return false;
        }

        return true;
    }

    /**
     * Stages in board order. Stages without a pipeline belong to the default one.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStages()
    {
        $query = CmsLeadStage::find()->andWhere(['cms_site_id' => (int)$this->cms_site_id]);
        $query->andWhere($this->isMain
            ? ['or', ['cms_lead_pipeline_id' => (int)$this->id], ['cms_lead_pipeline_id' => null]]
            : ['cms_lead_pipeline_id' => (int)$this->id]);
        $query->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]);
        $query->multiple = true;

        return $query;
    }

    /**
     * SQL condition "the deal is in this pipeline" for a deal table alias;
     * deals without a pipeline belong to the default one.
     */
    public function leadCondition(string $leadTable = null): array
    {
        $column = ($leadTable ?: CmsLead::tableName()).'.cms_lead_pipeline_id';

        return $this->isMain
            ? ['or', [$column => (int)$this->id], [$column => null]]
            : [$column => (int)$this->id];
    }

    /**
     * The pipeline of new requests and of stages and deals without a pipeline:
     * the marked one, else the first (see defaultFor()).
     */
    public function getIsMain(): bool
    {
        $default = static::defaultFor((int)$this->cms_site_id);

        return $default && (int)$default->id === (int)$this->id;
    }

    public function getSuccessRuleName(): string
    {
        return self::successRules()[$this->success_rule] ?? (string)$this->success_rule;
    }

    /**
     * Pipelines of a site in switcher order; empty on a site without them
     * (and before the migration).
     *
     * @return static[]
     */
    public static function forSite(?int $siteId = null): array
    {
        if (!static::ready()) {
            return [];
        }
        $siteId = $siteId ?: (\Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : 0);
        if (!array_key_exists($siteId, static::$_sites)) {
            static::$_sites[$siteId] = static::find()
                ->andWhere(['cms_site_id' => $siteId])
                ->orderBy(['is_default' => SORT_DESC, 'sort' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        }

        return static::$_sites[$siteId];
    }

    /** The pipeline new requests go to: the marked one, else the first. */
    public static function defaultFor(?int $siteId = null): ?self
    {
        $pipelines = static::forSite($siteId);
        foreach ($pipelines as $pipeline) {
            if ($pipeline->is_default) {
                return $pipeline;
            }
        }

        return $pipelines[0] ?? null;
    }

    /** The pipeline tables exist (the migration may run after the code update). */
    public static function ready(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $ready = static::getDb()->getTableSchema(static::tableName(), true) !== null;
        }

        return $ready;
    }

    /** @return CmsActiveQuery */
    public static function find()
    {
        return new CmsActiveQuery(static::class);
    }
}

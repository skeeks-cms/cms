<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\query\CmsActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * A site-defined step inside the system status "in work".
 *
 * Stages only label and order leads for people (list, card, kanban). Business
 * rules keep using the four system statuses of CmsLead.
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_site_id
 * @property int|null    $cms_lead_pipeline_id the pipeline; empty — the default (or the only) pipeline
 * @property string      $name
 * @property string|null $color
 * @property int         $sort
 *
 * @property CmsLead[]   $leads
 * @property CmsLeadPipeline|null $pipeline
 */
class CmsLeadStage extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_lead_stage}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_site_id'], 'default', 'value' => static function () {
                return \Yii::$app->skeeks->site ? (int)\Yii::$app->skeeks->site->id : null;
            }],
            [['name'], 'filter', 'filter' => 'trim'],
            [['color'], 'filter', 'filter' => static function ($value) {
                $value = trim((string)$value);
                return $value === '' ? null : strtolower($value);
            }],
            [['sort'], 'default', 'value' => 100],
            [['name', 'cms_site_id'], 'required'],
            [['cms_site_id', 'sort'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['color'], 'match', 'pattern' => '/^#[0-9a-f]{6}$/', 'message' => 'Укажите цвет в формате #RRGGBB.'],
            [['name'], 'unique', 'targetAttribute' => static::pipelineReady() ? ['cms_site_id', 'cms_lead_pipeline_id', 'name'] : ['cms_site_id', 'name'],
                'message' => 'Такой этап уже есть.'],
        ], static::pipelineReady() ? [
            [['cms_lead_pipeline_id'], 'filter', 'filter' => static fn($value) => $value === '' ? null : $value],
            [['cms_lead_pipeline_id'], 'integer'],
            [['cms_lead_pipeline_id'], 'exist', 'skipOnEmpty' => true, 'targetClass' => CmsLeadPipeline::class,
                'targetAttribute' => ['cms_lead_pipeline_id' => 'id', 'cms_site_id' => 'cms_site_id'],
                'message' => 'Воронка не найдена на этом сайте.'],
        ] : []);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name' => 'Название',
            'color' => 'Цвет',
            'sort' => 'Порядок',
            'cms_lead_pipeline_id' => 'Воронка',
        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'name' => 'Например: «Пытаемся связаться», «КП отправлено», «Думает».',
            'sort' => 'Этапы идут слева направо по возрастанию числа.',
            'color' => 'Необязательно. Цвет метки этапа в карточке и на канбане.',
        ]);
    }

    /** The stage column exists (П2б migration may run after the code update). */
    public static function pipelineReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $schema = static::getDb()->getTableSchema(static::tableName(), true);
            $ready = $schema && $schema->getColumn('cms_lead_pipeline_id') !== null;
        }

        return $ready;
    }

    public function getPipeline()
    {
        return $this->hasOne(CmsLeadPipeline::class, ['id' => 'cms_lead_pipeline_id']);
    }

    /**
     * Stages of a pipeline in board order. Without a pipeline (a site that has
     * none) — every stage of the site, as before pipelines.
     *
     * @return CmsLeadStage[]
     */
    public static function forPipeline(?CmsLeadPipeline $pipeline, ?int $siteId = null): array
    {
        if ($pipeline) {
            return $pipeline->stages;
        }

        return static::find()
            ->cmsSite($siteId ?: null)
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /** «Воронка: этап» when the site has several pipelines, else the stage name. */
    public function getFullName(): string
    {
        $pipelines = CmsLeadPipeline::forSite((int)$this->cms_site_id);
        if (count($pipelines) < 2) {
            return (string)$this->name;
        }
        $pipeline = $this->pipeline ?: CmsLeadPipeline::defaultFor((int)$this->cms_site_id);

        return ($pipeline ? $pipeline->name.': ' : '').$this->name;
    }

    public function getLeads()
    {
        return $this->hasMany(CmsLead::class, ['cms_lead_stage_id' => 'id']);
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        if ($this->getLeads()->exists()) {
            $this->addError('name', 'На этом этапе есть сделки. Сначала переведите их на другой этап.');
            return false;
        }

        return true;
    }

    /**
     * Stages of the current site in board order.
     *
     * @return CmsLeadStage[]
     */
    public static function forCurrentSite(): array
    {
        return static::find()
            ->cmsSite()
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /** @return CmsActiveQuery */
    public static function find()
    {
        return new CmsActiveQuery(static::class);
    }
}

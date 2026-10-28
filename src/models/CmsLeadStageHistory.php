<?php

namespace skeeks\cms\models;

use yii\db\ActiveRecord;

/**
 * One transition of a deal (CmsLead): its status, pipeline and stage before
 * and after, when and who (П2б). Written by CmsLead::afterSave() in the same
 * transaction for every path that saves a deal (card, kanban, sorting,
 * imports through save(), API). Reports count conversion and time on stages
 * from these rows, never from the current column.
 *
 * `is_migrated` rows are the state of an old deal on the day of the data
 * migration: not a real transition, the reliability boundary of reports.
 *
 * @property int         $id
 * @property int         $cms_site_id
 * @property int         $cms_lead_id
 * @property int         $created_at
 * @property int|null    $created_by
 * @property string      $kind
 * @property string|null $from_status
 * @property string      $to_status
 * @property int|null    $from_pipeline_id
 * @property int|null    $to_pipeline_id
 * @property int|null    $from_stage_id
 * @property int|null    $to_stage_id
 * @property string|null $from_stage_name
 * @property string|null $to_stage_name
 * @property int         $is_migrated
 *
 * @property CmsLead     $lead
 * @property CmsUser|null $createdBy
 */
class CmsLeadStageHistory extends ActiveRecord
{
    public const KIND_CREATE = 'create';
    public const KIND_TAKE = 'take';
    public const KIND_STAGE = 'stage';
    public const KIND_PIPELINE = 'pipeline';
    public const KIND_SUCCESS = 'success';
    public const KIND_REJECT = 'reject';
    public const KIND_REOPEN = 'reopen';
    public const KIND_MIGRATED = 'migrated';

    public static function tableName()
    {
        return '{{%cms_lead_stage_history}}';
    }

    public static function kinds(): array
    {
        return [
            self::KIND_CREATE => 'Создана',
            self::KIND_TAKE => 'Взята в работу',
            self::KIND_STAGE => 'Смена этапа',
            self::KIND_PIPELINE => 'Перенос в другую воронку',
            self::KIND_SUCCESS => 'Успешно',
            self::KIND_REJECT => 'Отказ',
            self::KIND_REOPEN => 'Возвращена в работу',
            self::KIND_MIGRATED => 'Состояние на дату перехода на воронки',
        ];
    }

    /** The history table exists (the migration may run after the code update). */
    public static function ready(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $ready = static::getDb()->getTableSchema(static::tableName(), true) !== null;
        }

        return $ready;
    }

    /**
     * What the deal looked like on this side of the transition, for people:
     * «Неразобранное», a stage name, «В работе», «Успешно», «Отказ».
     */
    public static function placeName(?string $status, ?string $stageName): string
    {
        if ($status === null) {
            return '—';
        }
        if ($status === CmsLead::STATUS_IN_WORK && $stageName) {
            return $stageName;
        }

        return CmsLead::staffStatuses()[$status] ?? $status;
    }

    public function getFromName(): string
    {
        return self::placeName($this->from_status, $this->from_stage_name);
    }

    public function getToName(): string
    {
        return self::placeName($this->to_status, $this->to_stage_name);
    }

    public function getKindName(): string
    {
        return self::kinds()[$this->kind] ?? $this->kind;
    }

    public function getLead()
    {
        return $this->hasOne(CmsLead::class, ['id' => 'cms_lead_id']);
    }

    public function getCreatedBy()
    {
        return $this->hasOne(CmsUser::class, ['id' => 'created_by']);
    }
}

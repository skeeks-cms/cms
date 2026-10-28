<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * "Это другая компания": an employee created a record although the CRM
 * found a similar one, and said why. The pair stays marked as a possible
 * duplicate for an administrator; nothing is merged automatically.
 *
 * @property int         $id
 * @property int|null    $created_at
 * @property int|null    $created_by
 * @property string      $record_code company|client — the new record
 * @property int         $record_id
 * @property string      $other_code company|client — the record it resembles
 * @property int         $other_id
 * @property string      $reason
 * @property string      $status open|dismissed
 * @property int|null    $resolved_by
 * @property int|null    $resolved_at
 */
class CmsCrmDuplicateMark extends ActiveRecord
{
    public const STATUS_OPEN = 'open';
    public const STATUS_DISMISSED = 'dismissed';

    public static function tableName()
    {
        return '{{%cms_crm_duplicate_mark}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['status'], 'default', 'value' => self::STATUS_OPEN],
            [['reason'], 'filter', 'filter' => 'trim'],
            [['record_code', 'record_id', 'other_code', 'other_id', 'reason', 'status'], 'required'],
            [['record_id', 'other_id', 'resolved_by', 'resolved_at'], 'integer'],
            [['reason'], 'string', 'max' => 2000],
            [['record_code', 'other_code'], 'in', 'range' => [CmsCrmAccessRequest::RECORD_COMPANY, CmsCrmAccessRequest::RECORD_CLIENT]],
            [['status'], 'in', 'range' => [self::STATUS_OPEN, self::STATUS_DISMISSED]],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'reason' => 'Почему это другая запись',
        ]);
    }
}

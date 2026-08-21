<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use yii\helpers\ArrayHelper;

/**
 * @property int $id
 * @property int $cms_lead_id
 * @property string $value
 * @property string|null $name
 * @property int $sort
 * @property CmsLead $cmsLead
 */
class CmsLeadEmail extends ActiveRecord
{
    use HasLogTrait;

    public static function tableName()
    {
        return '{{%cms_lead_email}}';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'log' => [
                'class' => CmsLogBehavior::class,
                'parent_relation' => 'cmsLead',
            ],
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['cms_lead_id', 'sort'], 'integer'],
            [['cms_lead_id', 'value'], 'required'],
            [['value', 'name'], 'string', 'max' => 255],
            [['name'], 'default', 'value' => null],
            [['value'], 'filter', 'filter' => static fn($value) => mb_strtolower(trim((string)$value), 'UTF-8')],
            [['value'], 'email', 'enableIDN' => true],
            [['cms_lead_id'], 'exist', 'targetClass' => CmsLead::class, 'targetAttribute' => 'id'],
            [['cms_lead_id', 'value'], 'unique', 'targetAttribute' => ['cms_lead_id', 'value']],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_lead_id' => 'Лид',
            'value' => 'Email',
            'name' => 'Описание',
            'sort' => 'Сортировка',
        ]);
    }

    public function getCmsLead()
    {
        return $this->hasOne(CmsLead::class, ['id' => 'cms_lead_id']);
    }

    public function asText()
    {
        return $this->value;
    }
}

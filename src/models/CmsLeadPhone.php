<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\helpers\PhoneHelper;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\cms\validators\PhoneValidator;
use yii\helpers\ArrayHelper;

/**
 * @property int $id
 * @property int $cms_lead_id
 * @property string $value
 * @property string|null $name
 * @property int $sort
 * @property CmsLead $cmsLead
 */
class CmsLeadPhone extends ActiveRecord
{
    use HasLogTrait;

    public static function tableName()
    {
        return '{{%cms_lead_phone}}';
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
            [['value'], 'filter', 'filter' => 'trim'],
            [['value'], PhoneValidator::class],
            [['value'], 'validateUniquePhone'],
            [['cms_lead_id'], 'exist', 'targetClass' => CmsLead::class, 'targetAttribute' => 'id'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_lead_id' => 'Лид',
            'value' => 'Телефон',
            'name' => 'Описание',
            'sort' => 'Сортировка',
        ]);
    }

    public function validateUniquePhone(string $attribute): void
    {
        $condition = PhoneHelper::equalCondition('value', $this->{$attribute});
        if (!$condition || !$this->cms_lead_id) {
            return;
        }
        $query = static::find()->andWhere(['cms_lead_id' => (int)$this->cms_lead_id])->andWhere($condition);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', (int)$this->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'Такой телефон уже добавлен к лиду.');
        }
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

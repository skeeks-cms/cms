<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use skeeks\cms\validators\PhoneValidator;
use yii\helpers\ArrayHelper;
/**
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_company_id
 * @property string      $value Телефон
 * @property string|null $name Примечание к телефону
 * @property int         $sort
 *
 * @property CmsCompany  $cmsCompany
 */
class CmsCompanyPhone extends ActiveRecord
{
    use HasLogTrait;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_company_phone}}';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            CmsLogBehavior::class => [
                'class'           => CmsLogBehavior::class,
                'parent_relation' => 'cmsCompany',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_company_id', 'sort'], 'integer'],
            [['cms_company_id', 'value'], 'required'],
            [['value', 'name'], 'string', 'max' => 255],

            [
                ['cms_company_id', 'value'],
                'unique',
                'targetAttribute' => ['cms_company_id', 'value'],
                //'message' => 'Этот email уже занят'
            ],

            [['name'], 'default', 'value' => null],

            [['value'], "filter", 'filter' => 'trim'],
            [
                ['value'],
                "filter",
                'filter' => function ($value) {
                    return StringHelper::strtolower($value);
                },
            ],
            [['value'], PhoneValidator::class],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_company_id' => "Компания",
            'name'           => "Описание",
            'value'          => "Телефон",
            'sort'           => "Сортировка",
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    /**
     * @return string
     */
    public function asText()
    {
        return $this->value;
    }
}
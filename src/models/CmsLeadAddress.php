<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use yii\helpers\ArrayHelper;

/**
 * Address of a lead. Same fields and rules as {@see CmsCompanyAddress}
 * (without the photo), so it moves to the company created from the lead.
 *
 * @property int         $id
 * @property int         $cms_lead_id
 * @property string|null $name
 * @property string      $value
 * @property float       $latitude
 * @property float       $longitude
 * @property string|null $entrance
 * @property string|null $floor
 * @property string|null $apartment_number
 * @property string|null $comment
 * @property string|null $postcode
 * @property int         $sort
 *
 * @property CmsLead     $cmsLead
 */
class CmsLeadAddress extends ActiveRecord
{
    use HasLogTrait;

    public static function tableName()
    {
        return '{{%cms_lead_address}}';
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
            [['value', 'name', 'comment'], 'filter', 'filter' => 'trim', 'skipOnEmpty' => true],
            [['latitude', 'longitude'], 'required', 'message' => 'Выберите адрес из подсказок, чтобы определить его на карте.'],
            [['latitude', 'longitude'], 'number'],
            [['floor', 'apartment_number', 'entrance', 'postcode'], 'string', 'max' => 255],
            [['comment', 'name', 'value'], 'string', 'max' => 255],
            [['name', 'comment', 'floor', 'apartment_number', 'entrance', 'postcode'], 'default', 'value' => null],
            [['value'], 'unique', 'targetAttribute' => ['cms_lead_id', 'value'], 'message' => 'Такой адрес уже добавлен к лиду.'],
            [['cms_lead_id'], 'exist', 'targetClass' => CmsLead::class, 'targetAttribute' => 'id'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_lead_id'      => 'Лид',
            'name'             => 'Название',
            'value'            => 'Адрес',
            'latitude'         => 'Широта',
            'longitude'        => 'Долгота',
            'entrance'         => 'Подъезд',
            'floor'            => 'Этаж',
            'apartment_number' => 'Номер квартиры',
            'postcode'         => 'Почтовый индекс',
            'comment'          => 'Комментарий',
            'sort'             => 'Сортировка',
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

<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\behaviors\CmsLogBehavior;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\traits\HasLogTrait;
use yii\helpers\ArrayHelper;
/**
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_company_id
 * @property string|null $name Название адреса (необязательное)
 * @property string      $value Полный адрес
 * @property float       $latitude
 * @property float       $longitude
 * @property string      $entrance    Подъезд
 * @property string      $floor Этаж
 * @property string      $apartment_number Номер квартиры
 * @property string      $comment
 * @property string      $postcode
 * @property int|null    $cms_image_id
 * @property int         $sort
 *
 * @property CmsCompany  $cmsCompany
 */
class CmsCompanyAddress extends ActiveRecord
{
    use HasLogTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_company_address}}';
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            CmsLogBehavior::class => [
                'class'           => CmsLogBehavior::class,
                'parent_relation' => 'cmsCompany',
            ],
            HasStorageFile::class => [
                'class'  => HasStorageFile::class,
                'fields' => ['cms_image_id'],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            [
                [
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at',
                    'cms_company_id',

                    'sort',
                ],
                'integer',
            ],

            [['cms_company_id', 'value'], 'required'],

            //[['name'], 'default', 'value' => null],

            [['latitude', 'longitude'], 'number'],

            [
                [
                    'floor',
                    'apartment_number',
                    'entrance',
                    'postcode',
                    'name',
                ],
                'string',
            ],

            [['comment', 'name', 'value'], 'string', 'max' => 255],



            /*[
                [
                    'value',
                    'floor',
                    'apartment_number',
                    'entrance',
                    'postcode',
                    'name',
                ],
                "filter",
                'filter' => 'trim',
            ],*/

            [['cms_image_id'], 'safe'],

            [
                ['cms_image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions'  => ['jpg', 'jpeg', 'gif', 'png', 'webp'],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 10,
                'minSize'     => 256,
            ],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_company_id' => \Yii::t('skeeks/cms', 'User'),
            'name'             => "Название",
            'value'            => "Адрес",
            'latitude'         => "Широта",
            'longitude'        => "Долгота",
            'entrance'         => "Подъезд",
            'floor'            => "Этаж",
            'apartment_number' => "Номер квартиры",
            'comment'          => "Комментарий",
            'cms_image_id'     => "Фото",
            'priority'         => "Сортировка",
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
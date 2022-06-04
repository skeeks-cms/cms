<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.02.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\HasStorageFile;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_user_phone".
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property int         $cms_site_id
 * @property int         $cms_user_id
 * @property string|null $name Название адреса (необязательное)
 * @property string      $value Полный адрес
 * @property float       $latitude
 * @property float       $longitude
 * @property string         $entrance    Подъезд
 * @property string         $floor Этаж
 * @property string         $apartment_number Номер квартиры
 * @property string      $comment
 * @property int|null    $cms_image_id
 * @property int         $priority
 *
 * @property CmsSite     $cmsSite
 * @property CmsUser     $cmsUser
 */
class CmsUserAddress extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_address}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            HasStorageFile::class              => [
                'class'  => HasStorageFile::class,
                'fields' => ['cms_image_id'],
            ],
        ]);
    }

    /**
     * @return \skeeks\cms\query\CmsActiveQuery
     */
    /*public static function find()
    {
        return parent::find()->cmsSite();
    }*/

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],

            [['name'], 'default', 'value' => null],

            [['latitude', 'longitude'], 'number'],

            [
                [
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at',
                    'cms_site_id',
                    'cms_user_id',
                    
                    'priority',
                ],
                'integer',
            ],

            [
                [
                    'floor',
                    'apartment_number',
                    'entrance',
                ],
                'string',
            ],

            [['cms_user_id', 'value'], 'required'],

            [['comment', 'name', 'value'], 'string', 'max' => 255],

            [['cms_image_id'], 'safe'],

            [['cms_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['cms_user_id' => 'id']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],

            [['cms_user_id', 'name'], 'unique', 'targetAttribute' => ['cms_user_id', 'value'], 'message' => 'Уже есть адрес с таким названием'],
            //[['cms_user_id', 'value'], 'unique', 'targetAttribute' => ['cms_user_id', 'value'], 'message' => 'Этот телефон уже занят'],



            [
                [
                    'value',
                    'floor',
                    'apartment_number',
                    'entrance',
                ],
                "filter",
                'filter' => 'trim',
            ],



        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_user_id'      => Yii::t('skeeks/cms', 'User'),
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
     * @return string
     */
    public function getCoordinates()
    {
        if (!$this->latitude || !$this->longitude) {
            return '';
        }

        return $this->latitude.",".$this->longitude;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        $userClass = isset(\Yii::$app->user) ? \Yii::$app->user->identityClass : CmsUser::class;
        return $this->hasOne($userClass, ['id' => 'cms_user_id']);
    }

    /**
     * Gets query for [[CmsSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        return $this->hasOne(CmsSite::className(), ['id' => 'cms_site_id']);
    }
}

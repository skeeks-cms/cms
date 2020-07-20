<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\modules\cms\user\models\User;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_site_address".
 *
 * @property int                      $cms_site_id
 * @property string|null              $name Название адреса (необязательное)
 * @property string                   $value Полный адрес
 * @property float                    $latitude Широта
 * @property float                    $longitude Долгота
 * @property string|null              $work_time Рабочее время
 * @property int|null                 $cms_image_id Фото адреса
 * @property int                      $priority
 *
 * @property string|null              $email
 * @property string|null              $phone
 * @property array|null               $workTime
 *
 * @property CmsStorageFile           $cmsImage
 * @property CmsSiteAddressEmail|null $cmsSiteAddressPhone
 * @property CmsSiteAddressEmail|null $cmsSiteAddressEmail
 * @property CmsSiteAddressEmail[]    $cmsSiteAddressEmails
 * @property CmsSiteAddressPhone[]    $cmsSiteAddressPhones
 * @property CmsSite                  $cmsSite
 */
class CmsSiteAddress extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_site_address}}';
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
            HasJsonFieldsBehavior::className() => [
                'class'  => HasJsonFieldsBehavior::className(),
                'fields' => ['work_time'],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'value'        => 'Полный адрес',
            'name'         => 'Название',
            'cms_image_id' => 'Фото',
            'work_time'    => 'Время работы',
            'latitude'     => 'Широта',
            'longitude'    => 'Долгота',
            'coordinates'  => '',
            'priority'     => 'Сортировка',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'name'      => 'Необязтельное поле, можно дать название этому адресу',
            'work_time' => 'Если время работы указано не будет, то время работы адреса будет использоваться из настроек сайта.',
            'priority'  => 'Чем ниже цифра тем выше адрес',
        ]);
    }


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

            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'cms_image_id', 'priority'], 'integer'],
            [['cms_site_id', 'value', 'latitude', 'longitude'], 'required'],
            [['latitude', 'longitude'], 'number'],
            [['work_time'], 'string'],
            [['name', 'value'], 'string', 'max' => 255],
            [['cms_site_id', 'value'], 'unique', 'targetAttribute' => ['cms_site_id', 'value']],
            [['cms_image_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsStorageFile::className(), 'targetAttribute' => ['cms_image_id' => 'id']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => \Yii::$app->skeeks->siteClass, 'targetAttribute' => ['cms_site_id' => 'id']],

            [
                [
                    'latitude',
                    'longitude',
                ],
                function ($attribute) {
                    if ($this->{$attribute} <= 0) {
                        $this->addError($attribute, 'Адрес указан некорректно');
                        return false;
                    }
                    return true;
                },
            ],


        ]);
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

    /**
     * Gets query for [[CmsImage]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsImage()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'cms_image_id']);
    }


    /**
     * Gets query for [[CmsSiteAddressEmails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteAddressEmail()
    {
        $q = $this->getCmsSiteAddressEmails()->limit(1);
        $q->multiple = false;
        return $q;
    }
    /**
     * Gets query for [[CmsSiteAddressEmails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteAddressPhone()
    {
        $q = $this->getCmsSiteAddressPhones()->limit(1);
        $q->multiple = false;
        return $q;
    }

    /**
     * Gets query for [[CmsSiteAddressEmails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteAddressEmails()
    {
        return $this->hasMany(CmsSiteAddressEmail::className(), ['cms_site_address_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * Gets query for [[CmsSiteAddressPhones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSiteAddressPhones()
    {
        return $this->hasMany(CmsSiteAddressPhone::className(), ['cms_site_address_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
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
     * Основной телефона для адреса
     *
     * @return string
     */
    public function getPhone()
    {
        if ($this->cmsSiteAddressPhone) {
            return $this->cmsSiteAddressPhone->value;
        }

        if ($phone = $this->cmsSite->cmsSitePhone) {
            return $phone->value;
        }

        return '';
    }

    /**
     * Основной email для адреса
     *
     * @return string
     */
    public function getEmail()
    {
        if ($this->cmsSiteAddressEmail) {
            return $this->cmsSiteAddressEmail->value;
        }

        if ($phone = $this->cmsSite->cmsSiteEmail) {
            return $phone->value;
        }

        return '';
    }

    /**
     * Режим работы
     *
     * @return array|string|null
     */
    public function getWorkTime()
    {
        if ($this->work_time) {
            return $this->work_time;
        }

        return $this->cmsSite->work_time;
    }
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\queries\CmsCountryQuery;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * @property integer        $id
 * @property string         $name
 * @property string         $alpha2
 * @property string         $alpha3
 * @property string         $iso
 * @property string|null    $phone_code
 * @property string|null    $domain
 * @property int|null       $flag_image_id
 *
 * @property CmsStorageFile $flag
 */
class CmsCountry extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_country}}';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            HasStorageFile::class => [
                'class'  => HasStorageFile::class,
                'fields' => ['flag_image_id'],
            ],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name'          => Yii::t('skeeks/cms', 'Name'),
            'alpha2'        => Yii::t('skeeks/cms', 'Код страны в двухбуквенном формате'),
            'alpha3'        => Yii::t('skeeks/cms', 'Код страны в трехбуквенном формате'),
            'iso'           => Yii::t('skeeks/cms', 'Цифровой код страны'),
            'phone_code'    => Yii::t('skeeks/cms', 'Телефонный код'),
            'domain'        => Yii::t('skeeks/cms', 'Главное доменное имя'),
            'flag_image_id' => Yii::t('skeeks/cms', 'Флаг'),
        ]);
    }

    public function attributeHints()
    {
        return array_merge(parent::attributeLabels(), [
            'alpha2'     => "Код страны в формате ISO 3166-1 alpha-2. Пример RU",
            'alpha3'     => "Код страны в формате ISO 3166-1 alpha-3. Пример RUS",
            'iso'        => "Цифровой код. Пример 643",
            'domain'     => "Корневое доменное имя страны. Пример .ru",
            'phone_code' => "Код телефона. Пример +7",
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['flag_image_id'], 'safe'],
            [
                [
                    'name',
                    'alpha2',
                    'alpha3',
                    'iso',
                ],
                'required',
            ],

            [['name'], 'string', 'max' => 255],
            [['alpha2'], 'string', 'max' => 2],
            [['alpha3'], 'string', 'max' => 3],
            [['iso'], 'string', 'max' => 3],
            [['domain'], 'string', 'max' => 16],
            [['phone_code'], 'string', 'max' => 16],

            [
                ['flag_image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions'  => ['jpg', 'jpeg', 'gif', 'png', 'webp'],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 2,
                'minSize'     => 100,
            ],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFlag()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'flag_image_id']);
    }

    /**
     * @return CmsCountryQuery
     */
    public static function find()
    {
        return (new CmsCountryQuery(get_called_class()));
    }
}
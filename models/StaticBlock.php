<?php
/**
 * StaticBlock
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasRef;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * @property $config
 *
 * Class Publication
 * @package skeeks\cms\models
 */
class StaticBlock extends Core
{
    const DEFAULT_VALUE_SECTION = '_';

    use behaviors\traits\HasFiles;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_static_block}}';
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                "class"  => behaviors\Serialize::className(),
                'fields' => ['value']
            ],

            behaviors\HasFiles::className() =>
            [
                "class"  => behaviors\HasFiles::className(),
                "fields" =>
                [
                    "image" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 1*1024, //1Mb
                        behaviors\HasFiles::MAX_SIZE            => 1*1024, //1Mb
                        behaviors\HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                        behaviors\HasFiles::MAX_COUNT_FILES     => 1,
                        behaviors\HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                    ],

                    "image_cover" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 1*1024, //1Mb
                        behaviors\HasFiles::MAX_SIZE            => 1*1024, //1Mb
                        behaviors\HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                        behaviors\HasFiles::MAX_COUNT_FILES     => 1,
                        behaviors\HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                    ],

                    "images" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 15*1024, //1Mb
                        behaviors\HasFiles::MAX_SIZE            => 1*1024, //1Mb
                        behaviors\HasFiles::ALLOWED_EXTENSIONS  => ['jpg', 'jpeg', 'png', 'gif'],
                        behaviors\HasFiles::MAX_COUNT_FILES     => 10,
                        behaviors\HasFiles::ACCEPT_MIME_TYPE    => "image/*",
                    ],

                    "files" =>
                    [
                        behaviors\HasFiles::MAX_SIZE_TOTAL      => 15*1024, //1Mb
                        behaviors\HasFiles::MAX_SIZE            => 1*1024, //1Mb
                        behaviors\HasFiles::MAX_COUNT_FILES     => 10,
                    ],
                ]
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['code'], 'required'],
            [['description'], 'string'],
            [['code'], 'unique'],
            [["images", "files", "image_cover", "image", 'value'], 'safe'],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return  array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'code' => Yii::t('app', 'Code'),
            'description' => Yii::t('app', 'Description'),
            'value' => Yii::t('app', 'Value'),
            'image' => Yii::t('app', 'Image'),
            'image_cover' => Yii::t('app', 'Image Cover'),
            'images' => Yii::t('app', 'Images'),
            'files' => Yii::t('app', 'Files'),
        ]);
    }


    /**
     * @param $id
     * @return static
     */
    static public function findById($id)
    {
        return static::find()->where(['id' => (int) $id])->one();
    }

    /**
     * @param $code
     * @return static
     */
    static public function findByCode($code)
    {
        return static::find()->where(['code' => (string) $code])->one();
    }







    /**
     * @param $value
     * @return $this
     */
    public function setDefaultValue($value)
    {
        $values = $this->getValues();
        $values[self::DEFAULT_VALUE_SECTION] = $value;
        $this->setAttribute('value', $values);
        return $this;
    }

    /**
     * @param $value
     * @param null $sections
     * @return $this
     */
    public function setValue($value, $sections = null)
    {
        $values = $this->getValues();
        if (!$sections)
        {
            return $this->setDefaultValue($value);
        }

        if (is_string($sections))
        {
            $values = $this->getValues();
            $values[$sections] = $value;
            $this->setAttribute('value', $values);
            return $this;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return (array) $this->value;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return (string) ArrayHelper::getValue($this->getValues(), self::DEFAULT_VALUE_SECTION);
    }

    /**
     * @param null $sections
     * @return string
     */
    public function getValue($sections = null)
    {
        if ($sections)
        {
            if (is_string($sections) || is_int($sections))
            {
                if (isset($this->getValues()[$sections]))
                {
                    return (string) ArrayHelper::getValue($this->getValues(), $sections);
                }
            }
        }

        return $this->getDefaultValue();
    }
}

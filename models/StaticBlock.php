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
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use skeeks\cms\models\behaviors\traits\HasMultiLangAndSiteFieldsTrait;
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
    use HasMultiLangAndSiteFieldsTrait;
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

            HasMultiLangAndSiteFields::className() =>
            [
                'class' => HasMultiLangAndSiteFields::className(),
                'fields' => ['value']
            ],

            behaviors\HasFiles::className() =>
            [
                "class"  => behaviors\HasFiles::className(),
            ],
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios['create'] = ['code', 'name', 'value', 'multiValue', 'description'];
        $scenarios['update'] = ['code', 'name', 'value', 'multiValue', 'description'];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['code'], 'required'],
            [['code'], 'validateCode'],
            [['description'], 'string'],
            [['code'], 'unique'],
            [["images", "files", "image_cover", "image", 'value', 'multiValue'], 'safe'],
        ]);
    }

    public function validateCode($attribute)
    {
        if(!preg_match('/^[a-z]{1}[a-z0-1-]{2,20}$/', $this->$attribute))
        {
            $this->addError($attribute, 'Используйте только буквы латинского алфавита и цифры. Начинаться должен с буквы. Пример block1.');
        }
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
     * @return mixed
     */
    public function getMultiValue()
    {
        return $this->getMultiFieldValue('value');
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMultiValue($value)
    {
        return $this->setMultiFieldValue('value', $value);
    }


}

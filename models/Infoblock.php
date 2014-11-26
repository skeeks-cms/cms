<?php
/**
 * Infoblock
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use Yii;

/**
 * @property $config
 *
 * Class Publication
 * @package skeeks\cms\models
 */
class Infoblock extends Core
{
    use behaviors\traits\HasFiles;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_infoblock}}';
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
                'fields' => ['config']
            ],

            [
                "class"  => behaviors\Serialize::className(),
                'fields' => ['rules']
            ],

            behaviors\HasFiles::className() =>
            [
                "class"  => behaviors\HasFiles::className(),
                "groups" =>
                [
                    "image" =>
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
            [['name'], 'required'],
            [['description', 'widget', 'rules', 'template'], 'string'],
            [['code'], 'unique'],
            [["images", "files", "image_cover", "image", 'config', 'multiConfig'], 'safe'],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return  array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'code' => Yii::t('app', 'Code'),
            'description' => Yii::t('app', 'Description'),
            'widget' => Yii::t('app', 'Widget'),
            'config' => Yii::t('app', 'Config'),
            'rules' => Yii::t('app', 'Rules'),
            'template' => Yii::t('app', 'Template'),
            'priority' => Yii::t('app', 'Priority'),
            'status' => Yii::t('app', 'Status'),
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
     * @return bool
     */
    public function isAllow()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getWidgetClassName()
    {
        return (string) $this->widget;
    }

    /**
     * @return array
     */
    public function getWidgetConfig()
    {
        return (array) $this->multiConfig;
    }


    /**
     * @return array
     */
    public function getMultiConfig()
    {
        return (array) $this->getMultiFieldValue('config');
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMultiConfig($value)
    {
        return $this->setMultiFieldValue('config', $value);
    }


    /**
     * @return array
     */
    public function getWidgetRules()
    {
        return (array) $this->rules;
    }

    /**
     * @return string
     */
    public function getWidgetTemplate()
    {
        return (string) $this->template;
    }


    /**
     * @param array $config
     * @return string
     */
    public function run($config = [])
    {
        $result = "";
        if (!$this->isAllow() || !$model = $this->getRegisterdWidgetModel())
        {
            return $result;
        }

        $config = array_merge($this->multiConfig, $config);
        $widget = $model->createWidget($config);

        return $widget->run();
    }

    /**
     * @return null|WidgetDescriptor
     */
    public function getRegisterdWidgetModel()
    {
        return \Yii::$app->registeredWidgets->getDescriptor($this->getWidgetClassName());
    }
}

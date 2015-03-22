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

use skeeks\cms\base\Widget;
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
                "class"     => behaviors\HasFiles::className(),
                "groups"    => [],
            ],
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios['create'] = ['code', 'name', 'description', 'widget'];
        $scenarios['update'] = ['code', 'name', 'description', 'widget'];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name', 'widget'], 'required'],
            [['description', 'widget', 'rules', 'template'], 'string'],
            [['code'], 'unique'],
            [['code'], 'validateCode'],
            [["images", "files", "image_cover", "image", 'config', 'multiConfig'], 'safe'],
        ]);
    }


    public function validateCode($attribute)
    {
        if(!preg_match('/^[a-z]{1}[a-z0-9-]{2,20}$/', $this->$attribute))
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
            'name'              => 'Название инфоблока',
            'widget'            => 'Виджет',
            'description'       => 'Описание инфоблока',
            'code'              => 'Уникальный код блока',
        ]);
    }


    /**
     * @param $id
     * @return static
     */
    static public function fetchById($id)
    {
        return static::find()->where(['id' => (int) $id])->one();
    }

    /**
     * @param $code
     * @return static
     */
    static public function fetchByCode($code)
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
    public function getDisplayRules()
    {
        return (array) $this->rules;
    }



    /**
     * @param array $config
     * @return string
     */
    public function run($config = [])
    {
        $result = "";
        if (!$this->isAllow())
        {
            return $result;
        }

        if (!$widget = $this->widget())
        {
            return $result;
        }

        return $widget->run();
    }



    /**
     * @var Widget
     */
    protected $_loadedWidget = null;

    /**
     *
     *
     * @return Widget
     */
    public function widget()
    {
        if ($this->_loadedWidget === null)
        {
            $this->_loadedWidget = $this->loadWidget();
        }

        return $this->_loadedWidget;
    }

    /**
     * Создание объкта виджета (пустого)
     *
     * @return Widget
     */
    public function createWidget()
    {
        $widgetClass    = $this->getWidgetClassName();
        $widget         = new $widgetClass();

        return $widget;
    }

    /**
     * Загрузить виджет с текущими данными
     *
     * @return Widget
     */
    public function loadWidget($safeOnly = true)
    {
        $widget = $this->createWidget();
        $widget->setAttributes($this->getMultiConfig(), $safeOnly);

        return $widget;
    }
}

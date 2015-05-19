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
use skeeks\cms\models\behaviors\HasStatus;
use Yii;

/**
 * This is the model class for table "{{%cms_infoblock}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $name
 * @property string $code
 * @property string $description
 * @property string $widget
 * @property string $config
 * @property string $rules
 * @property string $template
 * @property integer $priority
 * @property string $files
 * @property integer $protected_widget
 * @property integer $auto_created
 * @property string $protected_widget_params
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
                'fields' => ['rules', 'protected_widget_params']
            ],

            behaviors\HasFiles::className() =>
            [
                "class"     => behaviors\HasFiles::className(),
                "groups"    => [],
            ],
        ]);
    }

    /**
     * Установка атрибутов по объекту виджета инфоблока
     *
     * @param \skeeks\cms\widgets\Infoblock $widgetInfoblock
     * @return $this
     */
    public function setAttributesByWidgetInfoblock(\skeeks\cms\widgets\Infoblock $widgetInfoblock)
    {
        if ($widgetInfoblock->name)
        {
            $this->name = $widgetInfoblock->name;
        }

        if ($widgetInfoblock->description)
        {
            $this->description = $widgetInfoblock->description;
        }

        if ($widgetInfoblock->id)
        {
            $this->code = $widgetInfoblock->id;
        }

        if ($widgetInfoblock->widget)
        {
            $this->protected_widget = (int) $widgetInfoblock->widget;
        }

        $this->protected_widget_params = (array) array_keys($widgetInfoblock->protectedWidgetParams());

        if ($widgetInfoblock->getWidgetClassName())
        {
            $this->widget = $widgetInfoblock->getWidgetClassName();
        }

        return $this;
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_DEFAULT];

        $scenarios['create'] = $scenarios[self::SCENARIO_DEFAULT];
        $scenarios['update'] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['widget'], 'required'],
            ['name', 'default', 'value' => function(Infoblock $model, $attribute)
            {
                $name = '';

                if ($model->widget)
                {
                    $name = (string) $this->widget;
                }

                if ($model->code)
                {
                    $name = (string) $this->code;
                }

                if ($model->id)
                {
                    $name = $name . ' #' . (string) $this->id;
                }

                return (string) $name;
            }],
            [['description', 'widget', 'rules', 'template'], 'string'],
            [['code'], 'unique'],
            [['code'], 'validateCode'],
            [['protected_widget', 'auto_created'], 'integer'],
            [['config', 'multiConfig', 'protected_widget_params'], 'safe'],
        ]);
    }


    public function validateCode($attribute)
    {
        if(!preg_match('/^[a-z]{1}[a-z0-9-]{2,64}$/', $this->$attribute))
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
            'name'                      => 'Название инфоблока',
            'widget'                    => 'Виджет',
            'description'               => 'Описание инфоблока',
            'code'                      => 'Уникальный код блока',
            'protected_widget_params'   => 'Параметры виджета которые нельзя менять',
            'auto_created'              => 'Автоматически созданный инфоблок',
            'protected_widget'          => 'Разрешено ли менять виджет инфоблока',
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


    static public function getByCode($code)
    {
        return static::find()->where(['code' => (string) $code])->one();

        /*$dependency = new \yii\caching\DbDependency(['sql' => 'SELECT MAX(updated_at) FROM ' . static::tableName()]);

        $cache = Infoblock::getDb()->cache(function ($db) {
            return Infoblock::find()->where(['code' => (string) $code])->one();
        }, 100);*/



    }

    static public function getById($id)
    {

        return static::find()->where(['id' => (int) $id])->one();
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

<?php
/**
 * Модель связанного свойства.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */

namespace skeeks\cms\relatedProperties\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\measure\models\CmsMeasure;
use skeeks\cms\models\behaviors\Serialize;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\cms\relatedProperties\propertyTypes\PropertyTypeText;
use Yii;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * @property integer                       $id
 * @property integer                       $created_by
 * @property integer                       $updated_by
 * @property integer                       $created_at
 * @property integer                       $updated_at
 * @property string                        $name
 * @property string                        $code
 * @property integer                       $content_id
 * @property integer                       $priority
 * @property string                        $property_type
 * @property string                        $component
 * @property string                        $component_settings
 * @property string                        $hint
 * @property bool                          $is_required
 * @property bool                          $is_active
 * @property bool                          $is_multiple
 * @property null|string                   $cms_measure_code
 *
 * @property RelatedElementPropertyModel[] $elementProperties
 * @property RelatedPropertyEnumModel[]    $enums
 *
 * @property PropertyType                  $handler
 * @property mixed                         $defaultValue
 * @property bool                          $isRequired
 * @property CmsMeasure                    $cmsMeasure
 */
abstract class RelatedPropertyModel extends ActiveRecord
{
    const VALUE_TYPE_BOOL = 'bool';
    const VALUE_TYPE_INT = 'int';
    const VALUE_TYPE_NUM = 'num';
    const VALUE_TYPE_INT_RANGE = 'int_range';
    const VALUE_TYPE_NUM_RANGE = 'num_range';
    //const VALUE_TYPE_JSON     = 'json';
    const VALUE_TYPE_STRING = 'string';
    const VALUE_TYPE_TEXT = 'text';

    const VALUE_TYPE_SOPTION = 'soption';
    const VALUE_TYPE_ELEMENT = 'element';
    const VALUE_TYPE_SECTION = 'section';


    /**
     * @var RelatedPropertiesModel
     */
    //public $relatedPropertiesModel = null;
    
    /**
     * @var null 
     */
    public $relatedPropertyEnumClassName = null;

    protected $_handler = null;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            Serialize::className() =>
                [
                    'class'  => Serialize::className(),
                    'fields' => ['component_settings'],
                ],
        ]);
    }

    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_INSERT, [$this, "_processBeforeSave"]);
        $this->on(self::EVENT_BEFORE_UPDATE, [$this, "_processBeforeSave"]);
        $this->on(self::EVENT_BEFORE_DELETE, [$this, "_processBeforeDelete"]);
    }

    public function _processBeforeSave($e)
    {
        if ($handler = $this->handler) {
            $this->property_type = $handler->code;
            $this->is_multiple = $handler->isMultiple ? 1 : 0;
        }
    }

    public function _processBeforeDelete($e)
    {
        //TODO:: find all the elements associated with this feature and to remove them
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'component'          => Yii::t('skeeks/cms', 'От этого зависит как будет показываться свойство в форме редактирования.'),
        ]);
    }


    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name'               => Yii::t('skeeks/cms', 'Name'),
            'code'               => Yii::t('skeeks/cms', 'Code'),
            'is_active'          => Yii::t('skeeks/cms', 'Active'),
            'priority'           => Yii::t('skeeks/cms', 'Priority'),
            'property_type'      => Yii::t('skeeks/cms', 'Property Type'),
            'is_multiple'        => Yii::t('skeeks/cms', 'Multiple'),
            'is_required'        => Yii::t('skeeks/cms', 'Is Required'),
            'component'          => Yii::t('skeeks/cms', 'Элемент формы'),
            'component_settings' => Yii::t('skeeks/cms', 'Component Settings'),
            'hint'               => Yii::t('skeeks/cms', 'Hint'),
            'cms_measure_code'   => Yii::t('skeeks/cms', 'Единица измерения'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority'], 'integer'],
            [['name', 'component'], 'required'],
            [['component_settings'], 'safe'],
            [['name', 'component', 'hint'], 'string', 'max' => 255],
            //[['code'], 'string', 'max' => 64],
            [
                ['code'],
                function ($attribute) {
                    if (!preg_match('/^[a-zA-Z]{1}[_a-zA-Z0-9]{1,255}$/',
                        $this->$attribute)) {
                        //if(!preg_match('/(^|.*\])([\w\.]+)(\[.*|$)/', $this->$attribute))
                        {
                            $this->addError($attribute, \Yii::t('skeeks/cms',
                                'Use only letters of the alphabet in lower or upper case and numbers, the first character of the letter (Example {code})',
                                ['code' => 'code1']));
                        }
                    }
                },
            ],

            [['property_type'], 'string', 'max' => 1],
            [
                'code',
                'default',
                'value' => function ($model, $attribute) {
                    return "property".StringHelper::ucfirst(md5(\Yii::$app->security->generateRandomString(). time()));
                },
            ],
            ['priority', 'default', 'value' => 500],

            [['is_required'], 'integer'],
            [['is_required'], 'default', 'value' => 0],

            [['is_multiple'], 'integer'],
            [['is_multiple'], 'default', 'value' => 0],

            [['is_active'], 'integer'],
            [['is_active'], 'default', 'value' => 1],

            [['cms_measure_code'], 'string'],
            [
                ['cms_measure_code'],
                'default',
                'value' => null,
            ],
            [
                ['cms_measure_code'],
                function ($model) {
                    if (!$this->cmsMeasure) {
                        $this->addError("cms_measure_code", "Указан код валюты которой нет в базе.");
                    }
                },
            ],
        ]);
    }
    /*{
        return $this->hasMany(CmsContentElementProperty::className(), ['property_id' => 'id']);
    }*/

    /**
     * @return \yii\db\ActiveQuery
     */
    abstract public function getElementProperties();
    /*{
        return $this->hasMany(CmsContentPropertyEnum::className(), ['property_id' => 'id']);
    }*/

    /**
     * @return \yii\db\ActiveQuery
     */
    abstract public function getEnums();

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsMeasure()
    {
        return $this->hasOne(CmsMeasure::class, ['code' => 'cms_measure_code']);

    }

    /**
     * @param ActiveForm $activeForm
     * @param null       $model
     * @return bool|\yii\widgets\ActiveField
     * @deprecated
     *
     */
    public function renderActiveForm(ActiveForm $activeForm, $model = null)
    {
        $handler = $this->handler;

        if ($handler) {
            $handler->activeForm = $activeForm;
        } else {
            return false;
        }

        /*if ($model && !$this->relatedPropertiesModel) {
            $this->relatedPropertiesModel = $model->relatedPropertiesModel;
        }*/

        return $handler->renderForActiveForm($model->relatedPropertiesModel);
    }

    /**
     * @return PropertyType
     * @throws \skeeks\cms\import\InvalidParamException
     */
    public function getHandler()
    {
        if ($this->_handler !== null) {
            return $this->_handler;
        }

        if ($this->component) {
            try {
                /**
                 * @var $component PropertyType
                 */
                $foundComponent = \Yii::$app->cms->getRelatedHandler($this->component);
                //TODO:: Подумать! Нужно чтобы создавался новый экземляр класса потому что в него передается property объект. В то же время хотелось бы чтобы объект handler собирался согласно настройкам конфига.
                $component = clone $foundComponent;
                //$component = \Yii::$app->cms->createRelatedHandler($this->component);
                //print_r($this->component_settings);die;
                $component->property = $this;
                $component->load($this->component_settings, "");

                $this->_handler = $component;
                return $this->_handler;
            } catch (\Exception $e) {
                //\Yii::warning("Related property handler not found '{$this->component}' or load with errors: " . $e->getMessage(), self::className());
                $component = new PropertyTypeText();
                $component->property = $this;

                $this->_handler = $component;
                return $this->_handler;
            }

        }

        return null;
    }

    /**
     * @return bool
     */
    public function getIsRequired()
    {
        return (bool)($this->is_required == 1);
    }


    /**
     * @varsion > 3.0.2
     *
     * @return $this
     */
    public function addRules(RelatedPropertiesModel $elatedPropertiesModel)
    {
        $this->handler->addRules($elatedPropertiesModel);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue(RelatedPropertiesModel $relatedPropertiesModel)
    {
        return $this->handler->getDefaultValue($relatedPropertiesModel);
    }

    /**
     * @return string
     */
    public function asText()
    {
        $result = parent::asText();

        if ($this->cms_measure_code) {
            $result = $result." ({$this->cmsMeasure->asShortText})";
        }

        return $result;
    }
}
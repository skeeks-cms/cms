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

use skeeks\cms\components\Cms;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\Serialize;
use skeeks\cms\models\Core;
use skeeks\cms\relatedProperties\PropertyType;
use Yii;
use yii\base\DynamicModel;
use yii\base\Model;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $name
 * @property string $code
 * @property integer $content_id
 * @property string $active
 * @property integer $priority
 * @property string $property_type
 * @property string $list_type
 * @property string $multiple
 * @property integer $multiple_cnt
 * @property string $with_description
 * @property string $searchable
 * @property string $filtrable
 * @property string $is_required
 * @property integer $version
 * @property string $component
 * @property string $component_settings
 * @property string $hint
 * @property string $smart_filtrable
 *
 * @property RelatedElementPropertyModel[]      $elementProperties
 * @property RelatedPropertyEnumModel[]         $enums
 *
 * @property PropertyType         $handler
 */
abstract class RelatedPropertyModel extends Core
{
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            Serialize::className() =>
            [
                'class' => Serialize::className(),
                'fields' => ['component_settings']
            ]
        ]);
    }

    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_INSERT,    [$this, "processBeforeSave"]);
        $this->on(self::EVENT_BEFORE_UPDATE,    [$this, "processBeforeSave"]);
    }

    public function processBeforeSave()
    {
        if ($handler = $this->handler)
        {
            $this->property_type    = $handler->code;
            $this->multiple         = $handler->multiple;
        }
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('skeeks/cms', 'ID'),
            'created_by' => Yii::t('skeeks/cms', 'Created By'),
            'updated_by' => Yii::t('skeeks/cms', 'Updated By'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'name' => Yii::t('skeeks/cms', 'Name'),
            'code' => Yii::t('skeeks/cms', 'Code'),
            'active' => Yii::t('skeeks/cms', 'Active'),
            'priority' => Yii::t('skeeks/cms', 'Priority'),
            'property_type' => Yii::t('skeeks/cms', 'Property Type'),
            'list_type' => Yii::t('skeeks/cms', 'List Type'),
            'multiple' => Yii::t('skeeks/cms', 'Multiple'),
            'multiple_cnt' => Yii::t('skeeks/cms', 'Multiple Cnt'),
            'with_description' => Yii::t('skeeks/cms', 'With Description'),
            'searchable' => Yii::t('skeeks/cms', 'Searchable'),
            'filtrable' => Yii::t('skeeks/cms', 'Filtrable'),
            'is_required' => Yii::t('skeeks/cms', 'Is Required'),
            'version' => Yii::t('skeeks/cms', 'Version'),
            'component' => Yii::t('skeeks/cms', 'Component'),
            'component_settings' => Yii::t('skeeks/cms', 'Component Settings'),
            'hint' => Yii::t('skeeks/cms', 'Hint'),
            'smart_filtrable' => Yii::t('skeeks/cms', 'Smart Filtrable'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority', 'multiple_cnt', 'version'], 'integer'],
            [['name', 'component'], 'required'],
            [['component_settings'], 'safe'],
            [['name', 'component', 'hint'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 64],

            [['active', 'property_type', 'list_type', 'multiple', 'with_description', 'searchable', 'filtrable', 'is_required', 'smart_filtrable'], 'string', 'max' => 1],
            ['code', 'default', 'value' => function($model, $attribute)
            {
                return "property" . StringHelper::ucfirst(md5(rand(1, 10) . time()));
            }],
            ['priority', 'default', 'value' => 500],
            [['active', 'searchable'], 'default', 'value' => Cms::BOOL_Y],
            [['is_required', 'smart_filtrable', 'filtrable', 'with_description'], 'default', 'value' => Cms::BOOL_N],
        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    abstract public function getElementProperties();
    /*{
        return $this->hasMany(CmsContentElementProperty::className(), ['property_id' => 'id']);
    }*/

    /**
     * @return \yii\db\ActiveQuery
     */
    abstract public function getEnums();
    /*{
        return $this->hasMany(CmsContentPropertyEnum::className(), ['property_id' => 'id']);
    }*/

    /**
     * @param ActiveForm $activeForm
     * @param \skeeks\cms\relatedProperties\models\RelatedElementModel $model
     * @return mixed
     */
    public function renderActiveForm(ActiveForm $activeForm, $model)
    {
        $handler = $this->handler;

        if ($handler)
        {
            $handler->model = $model;
            $handler->activeForm = $activeForm;
        } else
        {
            return false;
        }

        return $handler->renderForActiveForm();
    }

    /**
     * @return PropertyType
     * @throws \skeeks\cms\import\InvalidParamException
     */
    public function getHandler()
    {
        if ($this->component)
        {
            try
            {
                /**
                 * @var $component PropertyType
                 */
                $component = \Yii::$app->cms->getRelatedHandler($this->component);
                $component->property = $this;
                $component->load($this->component_settings, "");

                return $component;
            } catch (\Exception $e)
            {
                return false;
            }

        }

        return null;
    }



    /**
     * @varsion > 2.4.9.1
     * @param DynamicModel $dynamicModel
     * @return $this
     */
    public function addRulesToDynamicModel(DynamicModel $dynamicModel)
    {
        if ($this->is_required == Cms::BOOL_Y)
        {
            $dynamicModel->addRule($this->code, 'required');
        }

        if ($this->is_required == Cms::BOOL_Y)
        {
            $dynamicModel->addRule($this->code, 'required');
        } else
        {
            $dynamicModel->addRule($this->code, 'safe');
        }

        return $this;
    }
}
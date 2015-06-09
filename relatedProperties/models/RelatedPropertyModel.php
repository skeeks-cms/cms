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
use skeeks\cms\models\Core;
use skeeks\sx\String;
use Yii;
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
 */
abstract class RelatedPropertyModel extends Core
{
    public function init()
    {
        parent::init();

        $this->on(BaseActiveRecord::EVENT_BEFORE_INSERT,    [$this, "processBeforeSave"]);
        $this->on(BaseActiveRecord::EVENT_BEFORE_UPDATE,    [$this, "processBeforeSave"]);
    }

    public function processBeforeSave()
    {
        if ($this->component)
        {
            /**
             * @var $propertyType PropertyType
             */
            $propertyTypeClassName = $this->component;
            $propertyType = new $propertyTypeClassName();

            $this->property_type    = $propertyType->code;
            $this->multiple         = $propertyType->multiple ? Cms::BOOL_Y :  Cms::BOOL_N;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'name' => Yii::t('app', 'Name'),
            'code' => Yii::t('app', 'Code'),
            'active' => Yii::t('app', 'Active'),
            'priority' => Yii::t('app', 'Priority'),
            'property_type' => Yii::t('app', 'Property Type'),
            'list_type' => Yii::t('app', 'List Type'),
            'multiple' => Yii::t('app', 'Multiple'),
            'multiple_cnt' => Yii::t('app', 'Multiple Cnt'),
            'with_description' => Yii::t('app', 'With Description'),
            'searchable' => Yii::t('app', 'Searchable'),
            'filtrable' => Yii::t('app', 'Filtrable'),
            'is_required' => Yii::t('app', 'Is Required'),
            'version' => Yii::t('app', 'Version'),
            'component' => Yii::t('app', 'Component'),
            'component_settings' => Yii::t('app', 'Component Settings'),
            'hint' => Yii::t('app', 'Hint'),
            'smart_filtrable' => Yii::t('app', 'Smart Filtrable'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority', 'multiple_cnt', 'version'], 'integer'],
            [['name', 'component'], 'required'],
            [['component_settings'], 'string'],
            [['name', 'component', 'hint'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 64],

            [['active', 'property_type', 'list_type', 'multiple', 'with_description', 'searchable', 'filtrable', 'is_required', 'smart_filtrable'], 'string', 'max' => 1],
            ['code', 'default', 'value' => function($model, $attribute)
            {
                return "property" . String::ucfirst(md5(rand(1, 10) . time()));
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
     * @param $relatedElementModel $model
     * @return mixed
     */
    public function renderActiveForm(ActiveForm $activeForm, $model)
    {
        $elementClass   = $this->component;

        /**
         * @var $propertyType PropertyType
         */
        $propertyType = new $elementClass([
            'model'         => $model,
            'property'      => $this,
            'activeForm'    => $activeForm,
        ]);

        return $propertyType->renderForActiveForm();
    }


    /**
     * @return array
     */
    public function rulesForActiveForm()
    {
        $result = [];

        $rules = [];

        if ($this->is_required == Cms::BOOL_Y)
        {
            $rules = ['required'];
        }

        if ((array) $rules)
        {
            foreach ((array) $rules as $ruleCode)
            {
                $result[] = [[$this->code], $ruleCode];
            }
        } else
        {
            $result[] = [[$this->code], 'safe'];
        }

        return $result;
    }
}
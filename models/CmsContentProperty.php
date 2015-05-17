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
use skeeks\cms\components\Cms;
use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\modules\cms\user\models\User;
use Yii;
use yii\db\BaseActiveRecord;
use yii\widgets\ActiveForm;

/**
 * This is the model class for table "{{%cms_content_property}}".
 *
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
 * @property CmsContentElementProperty[] $cmsContentElementProperties
 * @property CmsContent $content
 * @property User $createdBy
 * @property User $updatedBy
 * @property CmsContentPropertyEnum[] $cmsContentPropertyEnums
 */
class CmsContentProperty extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_property}}';
    }

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
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), []);
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
            'content_id' => Yii::t('app', 'Content ID'),
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
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'content_id', 'priority', 'multiple_cnt', 'version'], 'integer'],
            [['name'], 'required'],
            [['component_settings'], 'string'],
            [['name', 'component', 'hint'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 32],
            [['active', 'property_type', 'list_type', 'multiple', 'with_description', 'searchable', 'filtrable', 'is_required', 'smart_filtrable'], 'string', 'max' => 1],
            [['code'], 'unique'],
            ['code', 'default', 'value' => function($model, $attribute)
            {
                return "sx_auto_" . md5(rand(1, 10) . time());
            }],
            ['priority', 'default', 'value' => function($model, $attribute)
            {
                return 500;
            }],
        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElementProperties()
    {
        return $this->hasMany(CmsContentElementProperty::className(), ['property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContent()
    {
        return $this->hasOne(CmsContent::className(), ['id' => 'content_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentPropertyEnums()
    {
        return $this->hasMany(CmsContentPropertyEnum::className(), ['property_id' => 'id']);
    }





    /**
     * @param ActiveForm $activeForm
     * @param Model $model
     * @return mixed
     */
    public function renderActiveForm(ActiveForm $activeForm, Model $model)
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
                $result[] = [[$this->getFormAttribute()], $ruleCode];
            }
        } else
        {
            $result[] = [[$this->getFormAttribute()], 'safe'];
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getFormAttribute()
    {
        return "property_" . $this->id;
    }

    /**
     * @param $modelWhithProperties
     * @return mixed
     */
    public function value($modelWhithProperties)
    {
        if ($this->multiple == "Y")
        {
            if ($values = $modelWhithProperties->findPropertyValue($this->id)->all())
            {
                return ArrayHelper::map($values, "id", "value");
            } else
            {
                return [];
            }
        } else
        {
            if ($value = $modelWhithProperties->findPropertyValue($this->id)->one())
            {
                return $value->value;
            } else
            {
                return null;
            }
        }
    }

    /**
     * @param $modelWhithProperties
     * @param $value
     * @return $this
     */
    public function saveValue($modelWhithProperties, $value)
    {
        if ($this->multiple == "Y")
        {
            $propertyValues = $modelWhithProperties->getPropertyValues()->where(['property_id' => $this->id])->all();
            if ($propertyValues)
            {
                foreach ($propertyValues as $pv)
                {
                    $pv->delete();
                }
            }

            $values = (array) $value;

            if ($values)
            {
                foreach ($values as $key => $value)
                {
                    $productPropertyValue = new ProductPropertyMap([
                        'product_id'    => $modelWhithProperties->id,
                        'property_id'   => $this->id,
                        'value'         => $value,
                    ]);

                    $productPropertyValue->save(false);
                }
            }

        } else
        {
            /**
             * @var $propertyValue ProductPropertyMap
             */
            if ($productPropertyValue = $modelWhithProperties->getPropertyValues()->where(['property_id' => $this->id])->one())
            {
                $productPropertyValue->value = $value;
            } else
            {
                $productPropertyValue = new ProductPropertyMap([
                    'product_id' => $modelWhithProperties->id,
                    'property_id' => $this->id,
                    'value' => $value,
                ]);
            }

            $productPropertyValue->save();
        }

        return $this;
    }


}
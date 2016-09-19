<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */
namespace skeeks\cms\relatedProperties\models;

use skeeks\cms\components\Cms;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\Implode;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\Core;
use skeeks\cms\relatedProperties\PropertyType;
use yii\base\DynamicModel;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseHtml;

/**
 * @property RelatedPropertyModel[] $properties
 *
 * Class RelatedPropertiesModel
 * @package skeeks\cms\relatedProperties\models
 */
class RelatedPropertiesModel extends DynamicModel
{
    /**
     * @var RelatedElementModel
     */
    public $relatedElementModel = null;

    /**
     * @var RelatedPropertyModel[]
     */
    private $_properties = [];

    /**
     * @var array
     */
    private $_propertyValues = [];

    public function init()
    {
        parent::init();

        if ($this->relatedElementModel->relatedProperties)
        {
            foreach ($this->relatedElementModel->relatedProperties as $property)
            {
                $this->defineAttribute($property->code, $property->handler->isMultiple ? [] : null );

                $property->relatedPropertiesModel = $this;
                $property->addRules();

                $this->_properties[$property->code] = $property;
            }
        }

        if ($relatedElementProperties = $this->relatedElementModel->relatedElementProperties)
        {
            foreach ($this->_properties as $code => $property)
            {
                if ($property->handler->isMultiple)
                {
                    $values = [];
                    $valuesModels = [];

                    foreach ($relatedElementProperties as $propertyElementVal)
                    {
                        if ($propertyElementVal->property_id == $property->id)
                        {
                            $values[$propertyElementVal->id] = $propertyElementVal->value;
                            $valuesModels[$propertyElementVal->id] = $propertyElementVal;
                        }
                    }

                    $values = $property->handler->initValue($values);

                    $this->setAttribute($code, $values);
                    $this->_propertyValues[$code] = $valuesModels;
                } else
                {
                    $value = null;
                    $valueModel = null;

                    foreach ($relatedElementProperties as $propertyElementVal)
                    {
                        if ($propertyElementVal->property_id == $property->id)
                        {
                            $value = $propertyElementVal->value;
                            $valueModel = $propertyElementVal;
                            break;
                        }
                    }

                    $value = $property->handler->initValue($value);

                    $this->setAttribute($code, $value);
                    $this->_propertyValues[$code] = $valueModel;
                }
            }
        }
    }


    /**
     * Saves the current record.
     *
     * This method will call [[insert()]] when [[isNewRecord]] is true, or [[update()]]
     * when [[isNewRecord]] is false.
     *
     * For example, to save a customer record:
     *
     * ```php
     * $customer = new Customer; // or $customer = Customer::findOne($id);
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->save();
     * ```
     *
     * @param boolean $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the database and this method will return `false`.
     * @param array $attributeNames list of attribute names that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the saving succeeded (i.e. no validation errors occurred).
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            \Yii::info('Model not updated due to validation error.', __METHOD__);
            return false;
        }

        $hasErrors = false;

        try
        {
            foreach ($this->_properties as $property)
            {
                $this->_saveRelatedPropertyValue($property);
            }

        } catch (\Exception $e)
        {
            $hasErrors = true;
            $this->addError($property->code, $e->getMessage());
        }

        if ($hasErrors)
        {
            return false;
        }

        return true;
    }

    /**
     * @return RelatedPropertyModel[]
     */
    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * @return bool
     */
    public function delete()
    {
        try
        {
            foreach ($this->_properties as $property)
            {
                $this->_deleteRelatedPropertyValue($property);
            }

        } catch (\Exception $e)
        {
            return false;
        }

        return true;
    }


    /**
     * @param RelatedPropertyModel $property
     * @param $value
     * @return $this
     * @throws \Exception
     */
    protected function _saveRelatedPropertyValue($property)
    {
        $value      = $this->getAttribute($property->code);
        $element    = $this->relatedElementModel;

        if ($element->isNewRecord)
        {
            throw new Exception("Additional property \"" . $property->code . "\" can not be saved until the stored parent model");
        }

        if ($property->handler->isMultiple)
        {
            $propertyValues = $element->getRelatedElementProperties()->where(['property_id' => $property->id])->all();
            if ($propertyValues)
            {
                foreach ($propertyValues as $pv)
                {
                    $pv->delete();
                }
            }

            $values  = (array) $this->getAttribute($property->code);
            $values = $property->handler->beforeSaveValue($values);

            if ($values)
            {
                foreach ($values as $key => $value)
                {
                    $className = $element->relatedElementPropertyClassName;
                    $productPropertyValue = new $className([
                        'element_id'    => $element->id,
                        'property_id'   => $property->id,
                        'value'         => $value,
                        'value_enum'    => $value,
                        'value_num'     => $value,
                    ]);

                    if (!$productPropertyValue->save())
                    {
                        throw new Exception("{$property->code} not save");
                    }
                }
            }

        } else
        {
            $value      = $this->getAttribute($property->code);
            $value      = $property->handler->beforeSaveValue($value);

            if ($productPropertyValue = $element->getRelatedElementProperties()->where(['property_id' => $property->id])->one())
            {
                $productPropertyValue->value        = $value;
                $productPropertyValue->value_enum   = $value;
                $productPropertyValue->value_num    = $value;
            } else
            {
                $className = $element->relatedElementPropertyClassName;

                $productPropertyValue = new $className([
                    'element_id'    => $element->id,
                    'property_id'   => $property->id,
                    'value'         => $value,
                    'value_enum'    => $value,
                    'value_num'     => $value,
                ]);
            }

            if (!$productPropertyValue->save())
            {
                throw new Exception("{$property->code} not save");
            }
        }

        return $this;
    }


    /**
     * @param RelatedPropertyModel $property
     * @param $value
     * @return $this
     * @throws \Exception
     */
    protected function _deleteRelatedPropertyValue($property)
    {
        $element    = $this->relatedElementModel;

        if ($element->isNewRecord)
        {
            throw new Exception("Additional property \"" . $property->code . "\" can not be saved until the stored parent model");
        }


        if ($property->handler->isMultiple)
        {
            $property->handler->beforeDeleteValue();

            $propertyValues = $element->getRelatedElementProperties()->where(['property_id' => $property->id])->all();
            if ($propertyValues)
            {
                foreach ($propertyValues as $pv)
                {
                    $pv->delete();
                }
            }

        } else
        {
            $property->handler->beforeDeleteValue();

            $propertyValues = $element->getRelatedElementProperties()->where(['property_id' => $property->id])->all();
            if ($propertyValues)
            {
                foreach ($propertyValues as $pv)
                {
                    $pv->delete();
                }
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $result = [];

        foreach ($this->relatedElementModel->relatedProperties as $property)
        {
            $result[$property->code] = $property->name;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        $result = [];

        foreach ($this->relatedElementModel->relatedProperties as $property)
        {
            $result[$property->code] = $property->hint;
        }

        return $result;
    }

    /**
     * Loads default values from database table schema
     *
     * You may call this method to load default values after creating a new instance:
     *
     * ```php
     * // class Customer extends \yii\db\ActiveRecord
     * $customer = new Customer();
     * $customer->loadDefaultValues();
     * ```
     *
     * @param boolean $skipIfSet whether existing value should be preserved.
     * This will only set defaults for attributes that are `null`.
     * @return $this the model instance itself.
     */
    public function loadDefaultValues($skipIfSet = true)
    {
        foreach ($this->_properties as $property)
        {
            if ((!$skipIfSet || $this->{$property->code} === null)) {
                $this->{$property->code} = $property->defaultValue;
            }
        }

        return $this;

        /*foreach (static::getTableSchema()->columns as $column) {
            if ($column->defaultValue !== null && (!$skipIfSet || $this->{$column->name} === null)) {
                $this->{$column->name} = $column->defaultValue;
            }
        }
        return $this;*/
    }

    /**
     * @param string $name
     * @return RelatedPropertyModel
     */
    public function getRelatedProperty($name)
    {
        return ArrayHelper::getValue($this->_properties, $name);
    }

    /**
     * @param string $name
     * @return RelatedElementPropertyModel|RelatedElementPropertyModel[]
     */
    public function getRelatedElementProperties($name)
    {
        return ArrayHelper::getValue($this->_propertyValues, $name);
    }


    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return boolean whether the model has an attribute with the specified name.
     */
    public function hasAttribute($name)
    {
        return in_array($name, $this->attributes());
    }

    /**
     * Returns the named attribute value.
     * If this record is the result of a query and the attribute is not loaded,
     * null will be returned.
     * @param string $name the attribute name
     * @return mixed the attribute value. Null if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute($name)
    {
        if ($this->hasAttribute($name))
        {
            return $this->$name;
        }

        return null;
    }

    /**
     * Sets the named attribute value.
     * @param string $name the attribute name
     * @param mixed $value the attribute value.
     * @throws InvalidParamException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name))
        {
            $this->$name = $value;
        } else
        {
            throw new InvalidParamException(get_class($this) . ' '.\Yii::t('skeeks/cms','has no attribute named "{name}".',['name' => $name]));
        }
    }

    /**
     * @param $name
     * @return string
     */
    public function getSmartAttribute($name)
    {
        $property       = $this->getRelatedProperty($name);

        if (!$property)
        {
            return '';
        }

        return $property->handler->stringValue;
    }

    public function getEnumByAttribute($name)
    {
        /**
         * @var $property RelatedPropertyModel
         */
        $value      = $this->getAttribute($name);
        $property   = $this->getRelatedProperty($name);

        if ($property->property_type == PropertyType::CODE_LIST)
        {
            if ($property->handler->isMultiple)
            {
                if ($property->enums)
                {
                    $result = [];

                    foreach ($property->enums as $enum)
                    {
                        if (in_array($enum->id, $value))
                        {
                            $result[$enum->code] = $enum;
                        }

                    }

                    return $result;
                }
            } else
            {
                if ($property->enums)
                {
                    $enum = array_shift($property->enums);

                    foreach ($property->enums as $enum)
                    {
                        if ($enum->id == $value)
                        {
                            return $enum;;
                        }
                    }
                }

                return "";
            }
        }

        return null;
    }
}

<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */
namespace skeeks\cms\relatedProperties\models;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\components\Cms;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\Implode;
use skeeks\cms\models\Core;
use skeeks\cms\relatedProperties\PropertyType;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseHtml;

/**
 * Class RelatedPropertiesModel
 * @package skeeks\cms\relatedProperties\models
 */
class RelatedPropertiesModel extends Model
{
    /**
     * @var RelatedElementModel
     */
    public $relatedElementModel = null;

    /**
     * @var RelatedPropertyModel[]
     */
    private $_properties = [];

    public function init()
    {
        parent::init();

        if ($this->relatedElementModel->relatedProperties)
        {
            foreach ($this->relatedElementModel->relatedProperties as $property)
            {
                $this->_attributes[$property->code] = $this->relatedElementModel->getRelatedPropertyValue($property);
                $this->_properties[$property->code] = $property;
            }
        }
    }

    /**
     * @return array
     */
    public function attributeValues()
    {
        $result = [];

        foreach ($this->relatedElementModel->relatedProperties as $property)
        {
            $result[$property->code] = $this->getAttribute($property->code);
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function save()
    {
        foreach ($this->relatedElementModel->relatedProperties as $property)
        {
            $this->relatedElementModel->saveRelatedPropertyValue($property, $this->getAttribute($property->code));
        }

        return $this;
    }


    /**
     * @var array attribute values indexed by attribute names
     */
    private $_attributes = [];

    public function rules()
    {
        $result = parent::rules();

        foreach ($this->relatedElementModel->relatedProperties as $proeperty)
        {
            $result = ArrayHelper::merge($result, $proeperty->rulesForActiveForm());
        }

        return $result;
    }


    /**
     * PHP getter magic method.
     * This method is overridden so that attributes and related objects can be accessed like properties.
     *
     * @param string $name property name
     * @throws \yii\base\InvalidParamException if relation name is wrong
     * @return mixed property value
     * @see getAttribute()
     */
    public function __get($name)
    {
        if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        } elseif ($this->hasAttribute($name)) {
            return null;
        } else {

            $value = parent::__get($name);
            return $value;
        }
    }

    /**
     * PHP setter magic method.
     * This method is overridden so that AR attributes can be accessed like properties.
     * @param string $name property name
     * @param mixed $value property value
     */
    public function __set($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named attribute is null or not.
     * @param string $name the property name or the event name
     * @return boolean whether the property value is null
     */
    public function __isset($name)
    {
        try {
            return $this->__get($name) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sets a component property to be null.
     * This method overrides the parent implementation by clearing
     * the specified attribute value.
     * @param string $name the property name or the event name
     */
    public function __unset($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->_attributes[$name]);
        } elseif ($this->getRelation($name, false) === null) {
            parent::__unset($name);
        }
    }







    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return boolean whether the model has an attribute with the specified name.
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->_attributes) || in_array($name, $this->attributes());
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
        return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
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
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        } else {
            throw new InvalidParamException(get_class($this) . ' has no attribute named "' . $name . '".');
        }
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
     * @param string $name
     * @return RelatedPropertyModel
     */
    public function getRelatedProperty($name)
    {
        return ArrayHelper::getValue($this->_properties, $name);
    }

    public function getSmartAttribute($name)
    {
        /**
         * @var $property RelatedPropertyModel
         */
        $value      = $this->getAttribute($name);
        $property   = $this->getRelatedProperty($name);

        if ($property->property_type == PropertyType::CODE_LIST)
        {
            if ($property->multiple == Cms::BOOL_Y)
            {
                if ($property->enums)
                {
                    $result = [];

                    foreach ($property->enums as $enum)
                    {
                        if (in_array($enum->id, $value))
                        {
                            $result[$enum->code] = $enum->value;
                        }

                    }

                    return $result;
                }
            } else
            {
                if ($property->enums)
                {
                    $enum = array_shift($property->enums);
                    return $enum->value;
                }

                return "";
            }
        } else
        {
            return $value;
        }
    }
}
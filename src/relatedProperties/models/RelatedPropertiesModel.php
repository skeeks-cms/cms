<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */

namespace skeeks\cms\relatedProperties\models;

use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\HasDescriptionsBehavior;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\relatedProperties\PropertyType;
use yii\base\DynamicModel;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\ModelEvent;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * @property RelatedPropertyModel[] $properties
 *
 * Class RelatedPropertiesModel
 * @package skeeks\cms\relatedProperties\models
 */
class RelatedPropertiesModel extends DynamicModel
{
    public $id = '';
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


    /**
     * @var array|null old attribute values indexed by attribute names.
     * This is `null` if the record [[isNewRecord|is new]].
     */
    private $_oldAttributes;


    protected $_attributeHints = [];

    protected $_attributeLabels = [];

    /**
     * Инициализация аттрибута
     * @param array $values
     * @param bool  $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        foreach ($values as $code => $value) {
            $this->hasAttribute($code);
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @param $property
     * @return $this
     */
    public function defineProperty($property) {
        $this->_defineByProperty($property);
        return $this;
    }

    protected function _defineByProperty($property)
    {
        \Yii::info("_defineByProperty: {$this->relatedElementModel->id} {$this->id} {$property->code}", "dev");
        /**
         * @var $property RelatedPropertyModel
         */
        $this->defineAttribute($property->code, $property->handler->isMultiple ? [] : null);

        $property->relatedPropertiesModel = $this;
        $property->addRules();

        $this->{$property->code} = $property->defaultValue;

        $this->_properties[$property->code] = $property;

        $this->_attributeLabels[$property->code] = $property->name;
        $this->_attributeHints[$property->code] = $property->hint;
    }

    /**
     * @param array $fields
     * @param array $expand
     * @param bool  $recursive
     * @return array
     */
    /*public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $this->initAllProperties();

        $result = parent::toArray($fields, $expand, $recursive);

        /*if (!$result) {
            $this->initAllProperties();
            return parent::toArray($fields, $expand, $recursive);
        }

        return $result;
    }*/

    /**
     * @param RelatedPropertyModel $property
     * @param array                $relatedElementProperties
     */
    protected function _initPropertyValue($property, $relatedElementProperties = [])
    {
        $code = $property->code;
        if ($property->handler->isMultiple) {
            $values = [];
            $valuesModels = [];

            foreach ($relatedElementProperties as $propertyElementVal) {
                if ($propertyElementVal->property_id == $property->id) {
                    $values[$propertyElementVal->id] = $propertyElementVal->value;
                    $valuesModels[$propertyElementVal->id] = $propertyElementVal;
                }
            }

            $values = $property->handler->initValue($values);

            $this->setAttribute($code, $values);
            $this->_oldAttributes[$code] = $values;
            $this->_propertyValues[$code] = $valuesModels;
        } else {
            $value = null;
            $valueModel = null;

            foreach ($relatedElementProperties as $propertyElementVal) {
                if ($propertyElementVal->property_id == $property->id) {
                    $value = $propertyElementVal->value;
                    $valueModel = $propertyElementVal;
                    break;
                }
            }

            $value = $property->handler->initValue($value);

            $this->setAttribute($code, $value);
            $this->_oldAttributes[$code] = $value;
            $this->_propertyValues[$code] = $valueModel;
        }
    }

    protected $_is_initAllProperties = false;
    /**
     *
     */
    public function initAllProperties()
    {
        if ($this->_is_initAllProperties === true) {
            return true;
        }

        \Yii::beginProfile('init initAllProperties ' . $this->relatedElementModel->id);

        if ($this->relatedElementModel->relatedProperties) {
            foreach ($this->relatedElementModel->relatedProperties as $property) {
                $this->_defineByProperty($property);
            }
        }

        if ($relatedElementProperties = $this->relatedElementModel->relatedElementProperties) {
            foreach ($this->_properties as $code => $property) {
                $this->_initPropertyValue($property, $relatedElementProperties);
            }
        }

        $this->_is_initAllProperties = true;

        \Yii::endProfile('init initAllProperties '  . $this->relatedElementModel->id);
    }

    public function init()
    {
       // \Yii::beginProfile('init RP' . $this->relatedElementModel->id);

        parent::init();
        $this->id = \Yii::$app->security->generateRandomString(5);
        $this->initAllProperties();

        //\Yii::endProfile('init RP' . $this->relatedElementModel->id);
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
     * @param array   $attributeNames list of attribute names that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the saving succeeded (i.e. no validation errors occurred).
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            \Yii::info('Model not updated due to validation error.', __METHOD__);
            return false;
        }

        if (!$this->beforeSave(false)) {
            return false;
        }

        $values = $this->getDirtyAttributes($attributeNames);
        if (empty($values)) {
            $this->afterSave(false, $values);
            return true;
        }

        $hasErrors = false;

        //TODO:Добавить транзакцию
        try {
            foreach ($this->_properties as $property) {
                $this->_saveRelatedPropertyValue($property);
            }

        } catch (\Exception $e) {
            $hasErrors = true;
            $this->addError($property->code, $e->getMessage());
        }

        if ($hasErrors) {
            return false;
        }

        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $changedAttributes[$name] = isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
            $this->_oldAttributes[$name] = $value;
        }

        $this->afterSave(false, $changedAttributes);

        return true;
    }


    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        $event = new ModelEvent();
        $this->trigger($insert ? ActiveRecord::EVENT_BEFORE_INSERT : ActiveRecord::EVENT_BEFORE_UPDATE, $event);

        return $event->isValid;
    }

    /**
     * @param $insert
     * @param $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->trigger($insert ? ActiveRecord::EVENT_AFTER_INSERT : ActiveRecord::EVENT_AFTER_UPDATE, new AfterSaveEvent([
            'changedAttributes' => $changedAttributes,
        ]));
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
        $this->initAllProperties();

        if (!$this->beforeDelete()) {
            return false;
        }

        try {
            foreach ($this->_properties as $property) {
                $this->_deleteRelatedPropertyValue($property);
            }

        } catch (\Exception $e) {
            return false;
        }

        $this->_oldAttributes = null;
        $this->afterDelete();

        return true;
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        $event = new ModelEvent();
        $this->trigger(ActiveRecord::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     *
     */
    public function afterDelete()
    {
        $this->trigger(ActiveRecord::EVENT_AFTER_DELETE);
    }


    /**
     * @param RelatedPropertyModel $property
     * @param                      $value
     * @return $this
     * @throws \Exception
     */
    protected function _saveRelatedPropertyValue($property)
    {
        $value = $this->getAttribute($property->code);
        $element = $this->relatedElementModel;

        if ($element->isNewRecord) {
            throw new Exception("Additional property \"".$property->code."\" can not be saved until the stored parent model");
        }

        if ($property->handler->isMultiple) {
            $propertyValues = $element->getRelatedElementProperties()->where(['property_id' => $property->id])->all();
            if ($propertyValues) {
                foreach ($propertyValues as $pv) {
                    $pv->delete();
                }
            }

            $values = (array)$this->getAttribute($property->code);
            $values = $property->handler->beforeSaveValue($values);

            if ($values) {
                foreach ($values as $key => $value) {
                    if (empty($value)) {
                        continue;
                    }
                    $className = $element->relatedElementPropertyClassName;
                    /**
                     * @var $productPropertyValue RelatedElementPropertyModel
                     */
                    $productPropertyValue = new $className([
                        'element_id'   => $element->id,
                        'property_id'  => $property->id,
                        'value'        => (string)$value,
                        'value_enum'   => $value,
                        'value_num'    => $value,
                        'value_bool'   => (bool)$value,
                        'value_num2'   => $value,
                        'value_int2'   => $value,
                        'value_string' => StringHelper::substr((string)$value, 0, 255)
                    ]);
                    if ($property->property_type == PropertyType::CODE_LIST) {
                        $productPropertyValue->value_enum_id = (int)$value;
                    } elseif ($property->property_type == PropertyType::CODE_ELEMENT) {
                        $productPropertyValue->value_element_id = (int)$value;
                    } else {
                        $productPropertyValue->value_element_id = null;
                    }

                    if (!$productPropertyValue->save()) {
                        throw new Exception("{$property->code} not save");
                    }
                }
            }

        } else {
            $value = $this->getAttribute($property->code);
            $value = $property->handler->beforeSaveValue($value);

            if ($productPropertyValue = $element->getRelatedElementProperties()->where(['property_id' => $property->id])->one()) {
                $productPropertyValue->value = (string)$value;
                $productPropertyValue->value_enum = $value;
                $productPropertyValue->value_num = $value;
                $productPropertyValue->value_bool = (bool)$value;
                $productPropertyValue->value_num2 = $value;
                $productPropertyValue->value_int2 = $value;
                $productPropertyValue->value_string = StringHelper::substr((string)$value, 0, 255);

                if ($property->property_type == PropertyType::CODE_LIST) {
                    $productPropertyValue->value_enum_id = (int)$value;
                } elseif ($property->property_type == PropertyType::CODE_ELEMENT) {
                    $productPropertyValue->value_element_id = (int)$value;
                } else {
                    $productPropertyValue->value_element_id = null;
                }

            } else {
                $className = $element->relatedElementPropertyClassName;

                $productPropertyValue = new $className([
                    'element_id'   => $element->id,
                    'property_id'  => $property->id,
                    'value'        => (string)$value,
                    'value_enum'   => $value,
                    'value_num'    => $value,
                    'value_bool'   => (bool)$value,
                    'value_num2'   => $value,
                    'value_int2'   => $value,
                    'value_string' => StringHelper::substr((string)$value, 0, 255),
                ]);
                
                if ($property->property_type == PropertyType::CODE_LIST) {
                    $productPropertyValue->value_enum_id = (int) $value;
                } elseif($property->property_type == PropertyType::CODE_ELEMENT) {
                    $productPropertyValue->value_element_id = (int) $value;
                } else {
                    $productPropertyValue->value_element_id = null;
                }
                
            }

            if (empty($value)) {
                $productPropertyValue->delete();
            } else {
                if (!$productPropertyValue->save()) {
                    throw new Exception("{$property->code} not save. ".Json::encode($productPropertyValue->errors));
                }
            }
        }

        return $this;
    }


    /**
     * @param RelatedPropertyModel $property
     * @param                      $value
     * @return $this
     * @throws \Exception
     */
    protected function _deleteRelatedPropertyValue($property)
    {
        $element = $this->relatedElementModel;

        if ($element->isNewRecord) {
            throw new Exception("Additional property \"".$property->code."\" can not be saved until the stored parent model");
        }


        if ($property->handler->isMultiple) {
            $property->handler->beforeDeleteValue();

            $propertyValues = $element->getRelatedElementProperties()->where(['property_id' => $property->id])->all();
            if ($propertyValues) {
                foreach ($propertyValues as $pv) {
                    $pv->delete();
                }
            }

        } else {
            $property->handler->beforeDeleteValue();

            $propertyValues = $element->getRelatedElementProperties()->where(['property_id' => $property->id])->all();
            if ($propertyValues) {
                foreach ($propertyValues as $pv) {
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
        return $this->_attributeLabels;
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return $this->_attributeHints;
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
        foreach ($this->_properties as $property) {
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
     * {@inheritdoc}
     */
    /*public function __get($name)
    {
        $this->hasAttribute($name);
        return parent::__get($name);
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function __set($name, $value)
    {
        $this->hasAttribute($name);
        return parent::__set($name, $value);
    }*/


    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return boolean whether the model has an attribute with the specified name.
     */
    public function hasAttribute($name)
    {
        //$this->initAllProperties();
        return parent::hasAttribute($name);

        //\Yii::beginProfile('RP hasAttribute ' . $name);
        if (in_array($name, $this->attributes())) {
            //\Yii::endProfile('RP hasAttribute ' . $name);
            return true;
        }

        $profileKey = 'RP hasAttribute ' . $this->relatedElementModel->id . "_" . $name;

        \Yii::beginProfile($profileKey);

        $repClass = $this->relatedElementModel->relatedElementPropertyClassName;
        $rpClass = $this->relatedElementModel->relatedPropertyClassName;
        //TODO: Подумать, возможно getTableCacheTag нужно делать у какого то другого метода. Например у элементов контент это cmsContent как только изменились данные в таблице cmsContent тогда обновляется кэш.
        $tags = [];
        
        if (isset($this->relatedElementModel->tableCacheTag)) {
            $tags[] = $this->relatedElementModel->tableCacheTag;
        }
        
        $rp = new $rpClass();
        $rep = new $repClass();
        if (isset($rp->tableCacheTag)) {
            $tags[] = $rp->tableCacheTag;
        }
        
        if (isset($rep->tableCacheTag)) {
            $tags[] = $rep->tableCacheTag;
        }
        
        $dependency = new TagDependency([
            'tags' => $tags,
        ]);
        \Yii::endProfile($profileKey);

        $property = $this->relatedElementModel::getDb()->cache(function ($db) use ($name) {
            return $this->relatedElementModel->getRelatedProperties()->andWhere(['code' => $name])->one();
        }, 3600 * 24, $dependency);


        if ($property) {

            /*$profileKey = 'RP _defineByProperty ' . $this->relatedElementModel->id . "_" . $name;
            \Yii::beginProfile($profileKey);*/
            $this->_defineByProperty($property);
            /*\Yii::endProfile($profileKey);*/


            $pv = $this->relatedElementModel::getDb()->cache(function ($db) use ($property) {
                return $this->relatedElementModel->getRelatedElementProperties()->where(['property_id' => $property->id])->all();
            }, 3600 * 24, $dependency);

            //$pv = $this->relatedElementModel->getRelatedElementProperties()->where(['property_id' => $property->id])->all();

            //$profileKey = 'RP _initPropertyValue ' . $this->relatedElementModel->id . "_" . $name;
            //\Yii::beginProfile($profileKey);
            $this->_initPropertyValue($property, (array)$pv);
            //\Yii::endProfile($profileKey);
        }


        if (in_array($name, $this->attributes())) {
            //\Yii::endProfile('RP hasAttribute ' . $name);
            return true;
        }

        //\Yii::endProfile('RP hasAttribute ' . $name);
        return false;
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
        //\Yii::beginProfile('RP getAttribute ' . $name);

        if ($this->hasAttribute($name)) {
            //\Yii::endProfile('RP getAttribute ' . $name);
            return $this->$name;
        } else {
            //\Yii::endProfile('RP getAttribute ' . $name);
        }

        return null;
    }

    /**
     * Sets the named attribute value.
     * @param string $name the attribute name
     * @param mixed  $value the attribute value.
     * @throws InvalidArgumentException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->$name = $value;
        } else {
            throw new InvalidArgumentException(get_class($this).' '.\Yii::t('skeeks/cms',
                    'has no attribute named "{name}".', ['name' => $name]));
        }
    }


    /**
     * Returns the old attribute values.
     * @return array the old attribute values (name-value pairs)
     */
    public function getOldAttributes()
    {
        return $this->_oldAttributes === null ? [] : $this->_oldAttributes;
    }

    /**
     * Sets the old attribute values.
     * All existing old attribute values will be discarded.
     * @param array|null $values old attribute values to be set.
     * If set to `null` this record is considered to be [[isNewRecord|new]].
     */
    public function setOldAttributes($values)
    {
        $this->_oldAttributes = $values;
    }

    /**
     * Returns the old value of the named attribute.
     * If this record is the result of a query and the attribute is not loaded,
     * `null` will be returned.
     * @param string $name the attribute name
     * @return mixed the old attribute value. `null` if the attribute is not loaded before
     * or does not exist.
     * @see hasAttribute()
     */
    public function getOldAttribute($name)
    {
        return isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
    }

    /**
     * Sets the old value of the named attribute.
     * @param string $name the attribute name
     * @param mixed  $value the old attribute value.
     * @throws InvalidArgumentException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setOldAttribute($name, $value)
    {
        if (isset($this->_oldAttributes[$name]) || $this->hasAttribute($name)) {
            $this->_oldAttributes[$name] = $value;
        } else {
            throw new InvalidArgumentException(get_class($this).' has no attribute named "'.$name.'".');
        }
    }

    /**
     * Marks an attribute dirty.
     * This method may be called to force updating a record when calling [[update()]],
     * even if there is no change being made to the record.
     * @param string $name the attribute name
     */
    public function markAttributeDirty($name)
    {
        unset($this->_oldAttributes[$name]);
    }

    /**
     * Returns a value indicating whether the named attribute has been changed.
     * @param string $name the name of the attribute.
     * @param bool   $identical whether the comparison of new and old value is made for
     * identical values using `===`, defaults to `true`. Otherwise `==` is used for comparison.
     * This parameter is available since version 2.0.4.
     * @return bool whether the attribute has been changed
     */
    public function isAttributeChanged($name, $identical = true)
    {
        if ($this->hasAttribute($name) && isset($this->_oldAttributes[$name])) {
            if ($identical) {
                return $this->getAttribute($name) !== $this->_oldAttributes[$name];
            }

            return $this->getAttribute($name) != $this->_oldAttributes[$name];
        }

        return $this->hasAttribute($name) || isset($this->_oldAttributes[$name]);
    }

    /**
     * Returns the attribute values that have been modified since they are loaded or saved most recently.
     *
     * The comparison of new and old values is made for identical values using `===`.
     *
     * @param string[]|null $names the names of the attributes whose values may be returned if they are
     * changed recently. If null, [[attributes()]] will be used.
     * @return array the changed attribute values (name-value pairs)
     */
    public function getDirtyAttributes($names = null)
    {
        if ($names === null) {
            $names = $this->attributes();
        }
        $names = array_flip($names);
        $attributes = [];
        if ($this->_oldAttributes === null) {
            foreach ($this->attributes as $name => $value) {
                if (isset($names[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {
            foreach ($this->attributes as $name => $value) {
                if (isset($names[$name]) && (!array_key_exists($name, $this->_oldAttributes) || $value !== $this->_oldAttributes[$name])) {
                    $attributes[$name] = $value;
                }
            }
        }

        return $attributes;
    }


    /**
     * @param $name
     * @return string
     */
    public function getAttributeAsText($name)
    {
        $property = $this->getRelatedProperty($name);

        /*print_r($property->toArray());*/

        if (!$property) {
            return '';
        }
        return $property->handler->asText;
    }


    /**
     * @param $name
     * @return string
     */
    public function getAttributeAsHtml($name)
    {
        $property = $this->getRelatedProperty($name);

        if (!$property) {
            return '';
        }

        return $property->handler->asHtml;
    }

    /**
     * @param $name
     * @return string
     * @deprecated
     */
    public function getSmartAttribute($name)
    {
        $property = $this->getRelatedProperty($name);

        if (!$property) {
            return '';
        }

        return $property->handler->asHtml;
    }

    /**
     * @param $name
     *
     * @return RelatedPropertyEnumModel|RelatedPropertyEnumModel[]|null
     */
    public function getEnumByAttribute($name)
    {
        /**
         * @var $property RelatedPropertyModel
         */
        $value = $this->getAttribute($name);
        $property = $this->getRelatedProperty($name);

        if ($property && $property->property_type == PropertyType::CODE_LIST) {
            if ($property->handler->isMultiple) {
                if ($property->enums) {
                    $result = [];

                    foreach ($property->enums as $enum) {
                        if (in_array($enum->id, $value)) {
                            $result[$enum->code] = $enum;
                        }

                    }

                    return $result;
                }
            } else {
                if ($property->enums) {
                    $enums = (array)$property->enums;

                    foreach ($enums as $enum) {
                        if ($enum->id == $value) {
                            return $enum;
                        }
                    }
                }

                return "";
            }
        }

        return null;
    }
}

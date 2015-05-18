<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\propertyTypes;
use skeeks\cms\relatedProperties\PropertyType;

/**
 * Class PropertyTypeTextarea
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeTextarea extends PropertyType
{
    public $code             = self::CODE_STRING;
    public $name             = "Текстовое поле";

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();
        $field->textarea($this->getActiveFormConfig());

        return $field;
    }
}
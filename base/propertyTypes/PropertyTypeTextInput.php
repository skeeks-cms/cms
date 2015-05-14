<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\base\propertyTypes;
use skeeks\cms\base\PropertyType;

/**
 * Class PropertyTypeTextInput
 * @package skeeks\cms\base\propertyTypes
 */
class PropertyTypeTextInput extends PropertyType
{
    public $code             = self::CODE_STRING;
    public $name             = "Текстовая строка";

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();
        $field->textInput($this->getActiveFormConfig());

        return $field;
    }
}
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
class PropertyTypeTextInput extends PropertyTypeStandartElement
{
    static public $code             = self::CODE_STRING;
    static public $elementCode      = "textInput";
    static public $name             = "Текстовая строка";
}
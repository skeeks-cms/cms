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
 * Class PropertyTypeElement
 * @package skeeks\cms\base\propertyTypes
 */
class PropertyTypeElement extends PropertyType
{
    static public $code = self::CODE_ELEMENT;
    static public $name = "Привязка к элементу";
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\propertyTypes;
/**
 * Class PropertyTypeNumber
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeNumber extends PropertyTypeTextInput
{
    public $code                 = self::CODE_NUMBER;
    public $name                 = "Число";
}
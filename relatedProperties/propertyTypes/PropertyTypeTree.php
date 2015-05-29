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
 * Class PropertyTypeTree
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeTree extends PropertyType
{
    public $code = self::CODE_TREE;
    public $name = "Привязка к разделу";
}
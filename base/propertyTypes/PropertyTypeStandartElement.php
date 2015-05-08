<?php
/**
 *
 * Стандартный Yii элемент формы
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 08.05.2015
 */
namespace skeeks\cms\base\propertyTypes;
use skeeks\cms\base\PropertyType;

/**
 * Class PropertyTypeStandartElement
 * @package skeeks\cms\base\propertyTypes
 */
abstract class PropertyTypeStandartElement extends PropertyType
{
    /**
     * @var называние элемента формы, например textarea
     */
    static public $elementCode;
}
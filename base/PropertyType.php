<?php
/**
 *
 * Базовый тип свойства.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\base;
/**
 * Class PropertyType
 * @package skeeks\cms\base
 */
abstract class PropertyType extends Component
{
    const CODE_STRING   = 'S';
    const CODE_NUMBER   = 'N';
    const CODE_LIST     = 'L';
    const CODE_FILE     = 'F';
    const CODE_TREE     = 'T';
    const CODE_ELEMENT  = 'E';

    public static $code;
    public static $name;

    public function init()
    {
        //Не загружаем настройки по умолчанию
    }
}
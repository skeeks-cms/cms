<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */

namespace skeeks\cms\traits;

use skeeks\cms\base\ComponentDescriptor;

/**
 *
 * @property ComponentDescriptor descriptor
 *
 * Class HasComponentDescriptorTrait
 * @package skeeks\cms\traits
 */
trait HasComponentDescriptorTrait
{

    /**
     * @var ComponentDescriptor
     */
    protected $_descriptor = null;
    /**
     * @var string
     */
    static public $descriptorClassName = 'skeeks\cms\base\ComponentDescriptor';

    /**
     * @return array
     */
    public static function descriptorConfig()
    {
        return [
            "name"        => "Skeeks CMS",
            "description" => "",
            "keywords"    => "skeeks, cms",
        ];
    }

    /**
     * @return ComponentDescriptor
     */
    public function getDescriptor()
    {
        if ($this->_descriptor === null) {
            $classDescriptor = static::$descriptorClassName;
            if (class_exists($classDescriptor)) {
                $this->_descriptor = new $classDescriptor(static::descriptorConfig());
            } else {
                $this->_descriptor = new ComponentDescriptor(static::descriptorConfig());
            }
        }

        return $this->_descriptor;
    }
}
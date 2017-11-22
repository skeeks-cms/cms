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
        return
            [
                "version" => "1.0.0",

                "name" => "Skeeks CMS",
                "description" => "",
                "keywords" => "skeeks, cms",

                "homepage" => "https://cms.skeeks.com/",
                "license" => "BSD-3-Clause",

                "support" =>
                    [
                        "issues" => "https://www.skeeks.com/",
                        "wiki" => "https://cms.skeeks.com/docs/",
                        "source" => "https://github.com/skeeks-cms/cms"
                    ],

                "companies" =>
                    [
                        [
                            "name" => "SkeekS",
                            "emails" => ["info@skeeks.com", "support@skeeks.com"],
                            "phones" => ["+7 (495) 722-28-73"],
                            "sites" => ["skeeks.com"]
                        ]
                    ],

                "authors" =>
                    [
                        [
                            "name" => "Semenov Alexander",
                            "emails" => ["semenov@skeeks.com"],
                            "phones" => ["+7 (495) 722-28-73"]
                        ],

                        [
                            "name" => "Semenov Alexander",
                            "emails" => ["semenov@skeeks.com"],
                            "phones" => ["+7 (495) 722-28-73"]
                        ],
                    ],
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
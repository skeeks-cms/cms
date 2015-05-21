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
     * @var string
     */
    static public $descriptorClassName = 'skeeks\cms\base\ComponentDescriptor';

    /**
     * @return array
     */
    static public function descriptorConfig()
    {
        return
        [
            "version"               => "1.0.0",

            "name"                  => "Skeeks CMS",
            "description"           => "",
            "keywords"              => "skeeks, cms",

            "homepage"              => "http://www.skeeks.com/",
            "license"               => "BSD-3-Clause",

            "support"               =>
            [
                "issues"    =>  "http://www.skeeks.com/",
                "wiki"      =>  "http://cms.skeeks.com/wiki/",
                "source"    =>  "http://git.skeeks.com/skeeks/yii2-app"
            ],

            "companies"   =>
            [
                [
                    "name"      =>  "SkeekS",
                    "emails"    => ["info@skeeks.com", "support@skeeks.com"],
                    "phones"    => ["+7 (495) 722-28-73"],
                    "sites"     => ["skeeks.com"]
                ]
            ],

            "authors"    =>
            [
                [
                    "name"      => "Semenov Alexander",
                    "emails"    => ["semenov@skeeks.com"],
                    "phones"    => ["+7 (495) 722-28-73"]
                ],

                [
                    "name"      => "Semenov Alexander",
                    "emails"    => ["semenov@skeeks.com"],
                    "phones"    => ["+7 (495) 722-28-73"]
                ],
            ],
        ];
    }

    /**
     * @return ComponentDescriptor
     */
    public function getDescriptor()
    {
        if ($this->_descriptor === null)
        {
            $classDescriptor = static::$descriptorClassName;
            if (class_exists($classDescriptor))
            {
                $this->_descriptor = new $classDescriptor(static::descriptorConfig());
            } else
            {
                $this->_descriptor = new ComponentDescriptor(static::descriptorConfig());
            }
        }

        return $this->_descriptor;
    }
}
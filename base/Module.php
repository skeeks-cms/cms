<?php
/**
 * Module
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\base;
use skeeks\cms\base\components\Descriptor;

/**
 * Class Module
 * @package skeeks\cms
 */
abstract class Module extends \yii\base\Module
{
    /**
     * @var string название модуля
     */
    public $name = "";

    /**
     *  [
            "label"     => "Управление пользователями",
            "url"       => "cms/test-admin",
            "priority"  => 10,
        ],

        [
            "label"     => "Управление группами",
            "url"       => "cms/user-group-admin",
            "priority"  => 5,
        ]
     *
     * @var array пункуты меню админки
     */
    public $adminMenuItems      = [];
    /**
     * @var string название модуля в меню админки, опционально
     */
    public $adminMenuName       = "";
    /**
     * @var bool включить/отключить отображение блока меню в админке
     */
    public $adminMenuEnabled    = true;


    /**
     * @return array
     */
    protected function _descriptor()
    {
        return
        [
            "version"               => "1.0.0",

            "name"                  => "Module Skeeks Cms",
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
     * @var Descriptor
     */
    protected $_descriptor = null;

    /**
     * @return Descriptor
     */
    public function getDescriptor()
    {
        if ($this->_descriptor === null)
        {
            $this->_descriptor = new Descriptor($this->_descriptor());
        }

        return $this->_descriptor;
    }


    /**
     * Название модуля
     *
     * @return string
     */
    public function getName()
    {
        return $this->name ? $this->name : $this->getDescriptor()->name;
    }


    /**
     *
     * Берет файл относительно views модуля и рендерит его
     *
     * @param string $filePath helpers/test.php
     * @param array $data
     * @param null $context
     * @return string
     */
    public function renderFile($filePath, $data = [], $context = null)
    {
        return \Yii::$app->view->renderFile($this->getViewPath() . "/" . $filePath, $data, $context);
    }
}
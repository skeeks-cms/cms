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
use skeeks\sx\Dir;

/**
 * Class Module
 * @package skeeks\cms
 */
abstract class Module extends \yii\base\Module
{
    const CHECKS_DIR_NAME = "checks";
    /**
     * namespace проверок
     * skeeks\cms\checks - например, если не будет задан, то будет сформирован опираясь на значение controllerNamespace
     * @var null
     */
    public $checkNamespace = null;

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


    public function init()
    {
        parent::init();

        if ($this->controllerNamespace && !$this->checkNamespace)
        {
            $data = explode('\\', $this->controllerNamespace);
            if (count($data) > 1)
            {
                unset($data[count($data)-1]);
                $data[] = static::CHECKS_DIR_NAME;
                $this->checkNamespace = implode("\\", $data);
            }
        }
    }

    /**
     * @return CheckComponent[]
     */
    public function loadChecksComponents()
    {
        $result = [];

        $dir = new Dir($this->basePath . "/" . static::CHECKS_DIR_NAME);
        if ($dir->isExist())
        {
            if ($files = $dir->findFiles())
            {
                foreach ($files as $file)
                {
                    $className = $this->checkNamespace . "\\" . $file->getFileName();

                    if (class_exists($className))
                    {
                        $component = new $className();
                        if (is_subclass_of($component, CheckComponent::className()))
                        {
                            $result[] = $component;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Название модуля
     *
     * @return string
     */
    public function getName()
    {
        return $this->getDescriptor()->name;
    }

    /**
     * Версия
     *
     * @return string
     */
    public function getVersion()
    {
        return (string) $this->getDescriptor()->getVersion();
    }


    /**
     *
     * TODO: is depricated (начиная с версии 1.1.5)
     * Использовать: \Yii::$app->view->render("@skeeks/cms/views/test")
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
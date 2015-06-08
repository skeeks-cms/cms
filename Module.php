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
namespace skeeks\cms;
use skeeks\cms\modules\admin\components\UrlRule;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\web\Application;
use yii\web\View;

/**
 * Class Module
 * @package skeeks\cms
 */
class Module extends base\Module implements BootstrapInterface
{
    public $controllerNamespace = 'skeeks\cms\controllers';

    public function bootstrap($app)
    {}

    /**
     * Используем свой layout
     * @var string
     */
    //public $layout ='@skeeks/cms/modules/admin/views/layouts/main.php';
    /**
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            "version"               => file_get_contents(__DIR__ . "/VERSION"),

            "name"          => "SkeekS CMS",
            "description"   => "Базовый модуль cms, без него не будет работать ничего и весь мир рухнет.",
        ]);
    }



}
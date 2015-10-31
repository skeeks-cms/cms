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
            //"version"               => file_get_contents(__DIR__ . "/VERSION"),
            "version"               => \Yii::$app->cms->extension->version,

            "name"                  => "SkeekS CMS",
            "description"           => "Базовый модуль cms, без него не будет работать ничего и весь мир рухнет.",
        ]);
    }

    static public $isRegisteredTranslations = false;

    public function init()
    {
        parent::init();
        self::registerTranslations();
    }

    static public function registerTranslations()
    {
        if (self::$isRegisteredTranslations === false)
        {
            \Yii::$app->i18n->translations['skeeks/cms/user'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@skeeks/cms/messages',
                'fileMap' => [
                    'skeeks/cms/user' => 'user.php',
                ],
                'on missingTranslation' => ['skeeks\cms\components\TranslationEventHandler', 'handleMissingTranslation']
            ];

            \Yii::$app->i18n->translations['skeeks/cms/v2'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@skeeks/cms/messages',
                'fileMap' => [
                    'skeeks/cms/v2' => 'v2.php',
                ],
                'on missingTranslation' => ['skeeks\cms\components\TranslationEventHandler', 'handleMissingTranslation']
            ];

            self::$isRegisteredTranslations = true;
        }
    }

    public static function t($category, $message, $params = [], $language = null)
    {
        self::registerTranslations();
        return \Yii::t('skeeks/cms/' . $category, $message, $params, $language);
    }

}
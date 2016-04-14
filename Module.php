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
class Module extends base\Module
{
    public $controllerNamespace = 'skeeks\cms\controllers';

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
            if (!isset(\Yii::$app->i18n->translations['skeeks/cms/user']))
            {
                \Yii::$app->i18n->translations['skeeks/cms/user'] = [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@skeeks/cms/messages',
                    'fileMap' => [
                        'skeeks/cms/user' => 'user.php',
                    ],
                ];
            }


            if (!isset(\Yii::$app->i18n->translations['skeeks/cms/v2']))
            {
                \Yii::$app->i18n->translations['skeeks/cms/v2'] = [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@skeeks/cms/messages',
                    'fileMap' => [
                        'skeeks/cms/v2' => 'v2.php',
                    ],
                ];
            }


            self::$isRegisteredTranslations = true;
        }
    }

    public static function t($category, $message, $params = [], $language = null)
    {
        self::registerTranslations();
        return \Yii::t('skeeks/cms/' . $category, $message, $params, $language);
    }

}
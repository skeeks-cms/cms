<?php

namespace skeeks\cms\console;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\FileHelper;

class Composer
{
    public static function postInstall(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        echo "\tpostInstall\n";
        self::generateTmpConfigs($vendorDir);
    }

    public static function postUpdate(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        echo "\tpostUpdate\n";
        self::generateTmpConfigs($vendorDir);
    }

    static public function generateTmpConfigs($vendorDir)
    {
        require $vendorDir . '/autoload.php';
        require $vendorDir . '/yiisoft/yii2/Yii.php';
        define('ROOT_DIR', __DIR__);
        define('APP_CONFIG_DIR', __DIR__);
        define('VENDOR_DIR', $vendorDir);
        require $vendorDir . '/skeeks/cms/global.php';

        $application = new \yii\console\Application([
            'id' => 'skeeks',
            'basePath' => __DIR__,

            'id'            => 'skeeks-cms-app',
            "name"          => "SkeekS CMS",
            'language'      => 'ru',
            'vendorPath'    => $vendorDir,

            'components' => [

                'cms' =>
                [
                    'class'                         => 'skeeks\cms\components\Cms',
                ],

                'i18n' => [
                    'class' => 'skeeks\cms\i18n\I18N',
                    'translations' =>
                    [
                        'skeeks/cms' => [
                            'class'             => 'yii\i18n\PhpMessageSource',
                            'basePath'          => '@skeeks/cms/messages',
                            'fileMap' => [
                                'skeeks/cms' => 'main.php',
                            ],
                        ],

                        'skeeks/cms/user' => [
                            'class'             => 'yii\i18n\PhpMessageSource',
                            'basePath'          => '@skeeks/cms/messages',
                            'fileMap' => [
                                'skeeks/cms/user' => 'user.php',
                            ],
                        ]
                    ]
                ],
            ],
        ]);

        if (\Yii::$app->cms->generateTmpConfig())
        {
            echo "\t\ttmp web config is generated\n";
        } else
        {
            echo "\t\tError tmp web config is generated\n";
        }

        if (\Yii::$app->cms->generateTmpConsoleConfig())
        {
            echo "\t\ttmp console config is generated\n";
        } else
        {
            echo "\t\tError tmp console config is generated\n";
        }
    }
}
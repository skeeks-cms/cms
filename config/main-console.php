<?php
/**
 * Самый базовый конфиг приложения на базе skeeks cms
 * По умолчанию конфигурирование всех базовых используемых компонентов и админки
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
$config =
[
    'id'            => 'skeeks-cms-app',
    "name"          => "SkeekS CMS",
    'language'      => 'ru',
    'vendorPath'    => VENDOR_DIR,

    'components' => [

        'db' => [
            'class' => 'yii\db\Connection',
            //'dsn' => 'mysql:host=mysql.skeeks.com;dbname=s2_vz1005_demo-cms',
            //'username' => 's2_vz1016',
            //'password' => 'dryagtepEjsiocakVenAvyeyb',
            'charset' => 'utf8',
            'enableSchemaCache' => false,
        ],

        'storage' => [
            'class' => 'skeeks\cms\components\Storage',
            'components' =>
            [
                'local' =>
                [
                    'class'                 => 'skeeks\cms\components\storage\ClusterLocal',
                ]
            ],
        ],

        'currentSite' =>
        [
            'class' => 'skeeks\cms\components\CurrentSite'
        ],

        'cms' =>
        [
            'class'                         => '\skeeks\cms\components\Cms',
        ],

        'imaging' =>
        [
            'class' => '\skeeks\cms\components\Imaging',
        ],

        'console' =>
        [
            'class' => 'skeeks\cms\components\ConsoleComponent',
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

        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'suffix' => '',
            'rules' => [
                'u'                           => 'cms/user/index',
                'u/<username>'                => 'cms/user/view',
                'u/<username>/<action>'       => 'cms/user/<action>',

                '~<_a:(login|logout|register|forget|reset-password)>'                   => 'cms/auth/<_a>',

                'skeeks-cms'                            => 'cms/cms/index',
                'skeeks-cms/<action>'                   => 'cms/cms/<action>',

                "cms-imaging" => ["class" => 'skeeks\cms\components\ImagingUrlRule'], //Resize image on request
            ]
        ],
    ],


    'modules' => [

        'cms' =>
        [
            'class' => 'skeeks\cms\Module',
            'controllerNamespace' => 'skeeks\cms\console\controllers'
        ],
    ],
];

return $config;
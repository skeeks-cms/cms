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
    'vendorPath'    => VENDOR_DIR,
    'language'      => 'ru',
    'bootstrap'     => ['cms', 'cmsToolbar'],

    'components' => [

        'db' => [
            'class' => 'yii\db\Connection',
            //'dsn' => 'mysql:host=mysql.skeeks.com;dbname=s2_vz1005_demo-cms',
            //'username' => 's2_vz1016',
            //'password' => 'dryagtepEjsiocakVenAvyeyb',
            'charset' => 'utf8',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
        ],

        'cms' =>
        [
            'class'                         => '\skeeks\cms\components\Cms',
        ],

        'user' => [
            'class'             => '\yii\web\User',
            'identityClass'     => 'skeeks\cms\models\CmsUser',
            'enableAutoLogin'   => true,
            'loginUrl'          => ['cms/auth/login'],
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

        'cmsToolbar' =>
        [
            'class' => 'skeeks\cms\components\CmsToolbar',
        ],

        'storage' => [
            'class' => 'skeeks\cms\components\Storage',
            'components' =>
            [
                'local' =>
                [
                    'class'                 => 'skeeks\cms\components\storage\ClusterLocal',
                    'priority'              => 100,
                ]
            ],
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

        'assetManager' =>
        [
            'appendTimestamp'   => true,

            'bundles' =>
            [
                'yii\web\JqueryAsset' =>
                [
                    'js' => [
                        'jquery.min.js',
                    ]
                ],

                'yii\bootstrap\BootstrapPluginAsset' =>
                [
                    'js' => [
                        'js/bootstrap.min.js',
                    ]
                ],

                'yii\bootstrap\BootstrapAsset' =>
                [
                    'css' => [
                        'css/bootstrap.min.css',
                    ]
                ]
            ],
        ],

        'imaging' =>
        [
            'class' => '\skeeks\cms\components\Imaging',
        ],

        'breadcrumbs' =>
        [
            'class' => '\skeeks\cms\components\Breadcrumbs',
        ],

        'currentSite' =>
        [
            'class' => '\skeeks\cms\components\CurrentSite',
        ],

        'console' =>
        [
            'class' => 'skeeks\cms\components\ConsoleComponent',
        ],

    ],


    'modules' => [

        'cms' =>
        [
            'class' => '\skeeks\cms\Module',
        ],

        'datecontrol' =>  [
            'class' => 'skeeks\cms\modules\datecontrol\Module',
        ]
    ],
];

return $config;
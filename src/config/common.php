<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 06.11.2017
 */
return [
    "name" => "SkeekS CMS",
    'id' => 'skeeks-cms-app',

    'vendorPath' => '@vendor',

    'language' => 'ru',

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],

    'timeZone' => 'UTC',

    'components' => [
        'formatter' => [
            'defaultTimeZone' => 'UTC',
            'timeZone'        => 'Europe/Moscow',
        ],

        'db' => [
            'class' => 'yii\db\Connection',
            //'dsn' => 'mysql:host=mysql.skeeks.com;dbname=s2_vz1005_demo-cms',
            //'username' => 's2_vz1016',
            //'password' => 'dryagtepEjsiocakVenAvyeyb',
            'charset' => 'utf8',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
        ],

        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],

        'cms' => [
            'class' => '\skeeks\cms\components\Cms',
        ],

        'storage' => [
            'class' => 'skeeks\cms\components\Storage',
            'components' => [
                'local' => [
                    'class' => 'skeeks\cms\components\storage\ClusterLocal',
                    'priority' => 100,
                ],
            ],
        ],

        'currentSite' => [
            'class' => '\skeeks\cms\components\CurrentSite',
        ],

        'imaging' => [
            'class' => '\skeeks\cms\components\Imaging',
        ],

        'console' => [
            'class' => 'skeeks\cms\components\ConsoleComponent',
        ],

        'i18n' => [
            'translations' => [
                'skeeks/cms' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@skeeks/cms/messages',
                    'fileMap' => [
                        'skeeks/cms' => 'main.php',
                    ],
                ],

                'skeeks/cms/user' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@skeeks/cms/messages',
                    'fileMap' => [
                        'skeeks/cms/user' => 'user.php',
                    ],
                ],
            ],
        ],

        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'suffix' => '',
            'normalizer' => [
                'class' => 'yii\web\UrlNormalizer',
                'collapseSlashes' => true,
                'normalizeTrailingSlash' => true,
                'action' => \yii\web\UrlNormalizer::ACTION_REDIRECT_PERMANENT,
            ],
            'rules' => [
                'u' => 'cms/user/index',
                'u/<username>' => 'cms/user/view',
                'u/<username>/<action>' => 'cms/user/<action>',

                '~<_a:(login|logout|register|forget|reset-password)>' => 'cms/auth/<_a>',

                'skeeks-cms' => 'cms/cms/index',
                'skeeks-cms/<action>' => 'cms/cms/<action>',

                "cms-imaging" => ["class" => 'skeeks\cms\components\ImagingUrlRule'],
                //Resize image on request
            ],
        ],

        'cmsAgent' => [
            'commands' => [

                'cms/cache/flush-all' => [
                    'class' => \skeeks\cms\agent\CmsAgent::class,
                    'name' => ['skeeks/cms', 'Clearing the cache'],
                    'interval' => 3600 * 24,
                ],

                'ajaxfileupload/cleanup' => [
                    'class' => \skeeks\cms\agent\CmsAgent::class,
                    'name' => ['skeeks/cms', 'Cleaning temporarily downloaded files'],
                    'interval' => 3600 * 24,
                ],

            ],
        ],

        'authManager' => [
            'config' => require __DIR__ . '/_permissions.php'
        ],
    ],

    'modules' => [

        'cms' => [
            'class' => '\skeeks\cms\Module',
        ],

        'ajaxfileupload' => [
            'class' => '\skeeks\yii2\ajaxfileupload\AjaxFileUploadModule',
        ],
    ],
];
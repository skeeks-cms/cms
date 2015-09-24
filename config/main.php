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
    'bootstrap'     => ['cms', 'log', 'cmsToolbar', 'seo'],

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

        'user' => [
            'class'             => '\yii\web\User',
            'identityClass'     => 'skeeks\cms\models\CmsUser',
            'enableAutoLogin'   => true,
            'loginUrl'          => ['cms/auth/login'],
        ],


        'mailer' => [
            'class'         => 'skeeks\cms\mail\Mailer',
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

                    "name"                  => "Локальное хранилище",
                    "publicBaseUrl"         => "/uploads/all",
                    "rootBasePath"          =>  Yii::getAlias("@frontend/web/uploads/all"),
                ]
            ],
        ],

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class'     => 'yii\log\FileTarget',
                    'logVars'   => [],
                    'levels' => [
                        'error',
                        'warning'
                    ],
                ],
            ],
        ],

        'authManager' => [
            'class' => '\skeeks\cms\rbac\DbManager',
            //'defaultRoles' => ['user'],
        ],

        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'suffix' => '',
            'rules' => [
                ["class" => 'skeeks\cms\modules\admin\components\UrlRule', 'adminPrefix' => '~sx'], //админка

                'robots.txt'                            => 'cms/seo/robots',
                'sitemap.xml'                           => 'cms/seo/sitemap',

                '~<_c:(profile)>'                        => 'cms/user/profile',
                'u'                           => 'cms/user/index',
                'u/<username>'                => 'cms/user/view',
                'u/<username>/<action>'       => 'cms/user/<action>',

                '~<_a:(login|logout|register|forget|reset-password)>'                   => 'cms/auth/<_a>',

                'skeeks-cms'                            => 'cms/cms/index',
                'skeeks-cms/<action>'                   => 'cms/cms/<action>',
                'search'                                => 'cms/search/result',

                ["class" => 'skeeks\cms\components\ImagingUrlRule'], //админка
            ]
        ],

        'assetManager' =>
        [
            'appendTimestamp'   => true,
            'linkAssets'        => true,

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

        //Админское меню
        'adminMenu' =>
        [
            'class' => '\skeeks\cms\modules\admin\components\Menu',
        ],

        'seo' =>
        [
            'class' => '\skeeks\cms\components\Seo'
        ],

        'admin' =>
        [
            'class' => '\skeeks\cms\modules\admin\components\settings\AdminSettings'
        ],

        'cmsSettings' =>
        [
            'class'                         => '\skeeks\cms\components\CmsSettings',

        ],

        'cms' =>
        [
            'class'                         => '\skeeks\cms\components\Cms',

            'template'                      => "default",
            'templates'                     =>
            [
                'default' =>
                [
                    'name'          => 'Базовый шаблон (по умолчанию)',
                    'pathMap'       =>
                    [
                        '@app/views' =>
                        [
                            '@app/templates/default',
                        ],
                    ]
                ]
            ],
        ],

        'dbSession' =>
        [
            'class'         => '\skeeks\cms\base\DbSession',
        ],

        'session' =>
        [
            'class' => '\skeeks\cms\base\Session',
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

        'dbDump' =>
        [
            'class' => '\skeeks\cms\components\db\DbDumpComponent',
        ],

        'cmsSearch' =>
        [
            'class' => '\skeeks\cms\components\CmsSearchComponent',
        ],

        'cmsMarkeplace' =>
        [
            'class' => '\skeeks\cms\components\marketplace\MarketplaceApi',
        ],


        'authClientCollection' =>
        [
            'class' => 'skeeks\cms\authclient\Collection',

            'clients' =>
            []
        ],

        'authClientSettings' =>
        [
            'class' => 'skeeks\cms\authclient\AuthClientSettings',
        ],

    ],


    'modules' => [

        'admin' =>
        [
            'class' => '\skeeks\cms\modules\admin\Module'
        ],

        'gii' =>
        [
            'class' => 'skeeks\cms\components\GiiModule',
        ],

        'debug' =>
        [
            'class' => 'yii\debug\Module',
        ],

        'cms' =>
        [
            'class'                         => '\skeeks\cms\Module',
        ],

        'markdown' => [
            'class' => 'kartik\markdown\Module',
        ],

        'datecontrol' =>  [
            'class' => '\kartik\datecontrol\Module',

            // format settings for displaying each date attribute (ICU format example)
            'displaySettings' => [
                \kartik\datecontrol\Module::FORMAT_DATE => 'dd-MM-yyyy',
                \kartik\datecontrol\Module::FORMAT_TIME => 'HH:mm:ss',
                \kartik\datecontrol\Module::FORMAT_DATETIME => 'dd-MM-yyyy HH:mm:ss',
            ],

            // format settings for saving each date attribute (PHP format example)
            'saveSettings' => [
                \kartik\datecontrol\Module::FORMAT_DATE => 'php:U', // saves as unix timestamp
                \kartik\datecontrol\Module::FORMAT_TIME => 'php:U', //'php:H:i:s',
                \kartik\datecontrol\Module::FORMAT_DATETIME => 'php:U', //'php:Y-m-d H:i:s',
            ],

            // set your display timezone
            'displayTimezone' => 'Europe/Moscow',

            // set your timezone for date saved to db
            'saveTimezone' => 'UTC',

            // automatically use kartik\widgets for each of the above formats
            'autoWidget' => true,

            // use ajax conversion for processing dates from display format to save format.
            'ajaxConversion' => true,

            // default settings for each widget from kartik\widgets used when autoWidget is true
            'autoWidgetSettings' => [
                \kartik\datecontrol\Module::FORMAT_DATE => ['type'=>2, 'pluginOptions'=>['autoclose'=>true]], // example
                \kartik\datecontrol\Module::FORMAT_DATETIME => [], // setup if needed
                \kartik\datecontrol\Module::FORMAT_TIME => [], // setup if needed
            ],

            // custom widget settings that will be used to render the date input instead of kartik\widgets,
            // this will be used when autoWidget is set to false at module or widget level.
            'widgetSettings' => [
                \kartik\datecontrol\Module::FORMAT_DATE => [
                    //'class' => '\yii\jui\DatePicker', // example
                    'class' => '\kartik\datetime\DatePicker',
                    'options' => [
                        'dateFormat' => 'php:d-M-Y',
                        'options' => ['class'=>'form-control'],
                    ]
                ],

                \kartik\datecontrol\Module::FORMAT_DATETIME => [
                    //'class' => '\yii\jui\DatePicker', // example
                    'class' => '\kartik\datetime\DateTimePicker',
                    'options' => [
                        'dateFormat' => 'php:d-F-Y H:i:s',
                        'options' => ['class'=>'form-control'],
                    ]
                ]
            ]
            // other settings
        ]
    ],
];

return $config;
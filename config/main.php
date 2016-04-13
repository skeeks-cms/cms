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

        'i18n' => [
            //'class' => 'skeeks\cms\i18n\components\I18NDb',
            'class' => 'skeeks\cms\i18n\components\I18N',
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
                    'priority'              => 100,

                    "name"                  => \Yii::t('app',"Local storage"),
                    "publicBaseUrl"         => "/uploads/all",
                    "rootBasePath"          =>  \Yii::getAlias("@frontend/web/uploads/all"),
                ]
            ],
        ],

        'authManager' => [
            'class' => '\skeeks\cms\rbac\DbManager',
        ],

        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'suffix' => '',
            'rules' => [
                'cms-admin' => ["class" => 'skeeks\cms\modules\admin\components\UrlRule', 'adminPrefix' => '~sx'], //admin panel

                'robots.txt'                            => 'cms/seo/robots',
                'sitemap.xml'                           => 'cms/seo/sitemap',

                '~<_c:(profile)>'             => 'cms/profile/index',
                'u'                           => 'cms/user/index',
                'u/<username>'                => 'cms/user/view',
                'u/<username>/<action>'       => 'cms/user/<action>',

                '~<_a:(login|logout|register|forget|reset-password)>'                   => 'cms/auth/<_a>',

                'skeeks-cms'                            => 'cms/cms/index',
                'skeeks-cms/<action>'                   => 'cms/cms/<action>',

                'search'                                => 'cms/search/result',

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

        'cms' =>
        [
            'class'                         => '\skeeks\cms\components\Cms',

            'template'                      => "default",
            'templates'                     =>
            [
                'default' =>
                [
                    'name'          => 'Базовый шаблон (по умолчанию)',
                    /*'pathMap'       =>
                    [
                        '@app/views' =>
                        [
                            '@app/templates/default',
                        ],
                    ]*/
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

        'console' =>
        [
            'class' => 'skeeks\cms\components\ConsoleComponent',
        ],

    ],


    'modules' => [

        'admin' =>
        [
            'class' => '\skeeks\cms\modules\admin\Module'
        ],

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
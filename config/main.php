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
    'id' => 'skeeks-cms-app',
    'language' => 'ru',
    'bootstrap' => ['log'],

    'components' => [

        'user' => [
            'class'         => \yii\web\User::className(),
            'identityClass' => 'skeeks\cms\models\User',
            'enableAutoLogin' => true,
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
                    'class' => 'yii\log\FileTarget',
                    'levels' => [
                        'error',
                        'warning'
                    ],
                ],
            ],
        ],

        'authManager' => [
            'class' => skeeks\cms\rbac\DbManager::className(),
        ],

        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'suffix' => '',
            'rules' => [
                ["class" => 'skeeks\cms\modules\admin\components\UrlRule', 'adminPrefix' => '~sx'], //админка

                '<_c:(publication|user)>'               => 'cms/<_c>/index',
                '<_c:(publication)>/<seo_page_name>'    => 'cms/<_c>/view',
                '<_c:(user)>/<username>'                => 'cms/<_c>/view',

                '<_a:(login|logout)>'                   => 'cms/auth/<_a>',

                'skeeks-cms'                            => 'cms/cms/index',
                'skeeks-cms/<action>'                   => 'cms/cms/<action>',
            ]
        ],

        'currentSite' => ['class' => 'skeeks\cms\components\CurrentSite'],

        'registeredModels' =>
        [
            'class' => 'skeeks\cms\components\RegisteredModels',
            //Модели к которым можно крепить другие, то есть эти модели имеют ссылку на себя объект Ref
            'components' => include_once 'models.php'
        ],

        //Зарегистрированные виджеты
        'registeredWidgets' =>
        [
            'class' => 'skeeks\cms\components\RegisteredWidgets',
            'components' => include_once 'widgets.php'
        ],

        //Админское меню
        'adminMenu' =>
        [
            'class' => \skeeks\cms\modules\admin\components\Menu::className(),
            'groups' => include_once 'admin-menu.php'
        ],

        'registeredLayouts' =>
        [
            'class'         => '\skeeks\cms\components\RegisteredLayouts',
            /*'components'    =>
            [
                'default' =>
                [
                    'name' => 'По умолчанию',
                    'path' => '@app/views/layouts/main.php'
                ]
            ]*/
        ],


        //Глобальные опции страниц
        'pageOptions' =>
        [
            'class' => '\skeeks\cms\components\PageOptions',
            'components' =>  include_once 'page-options.php'
        ],


        //Языки проекта
        'langs' =>
        [
            'class' => '\skeeks\cms\components\Langs',
            /*'components' =>
            [
                'ru' =>
                [
                    'name' => 'Русский'
                ],

                'en' =>
                [
                    'name' => 'Английский'
                ],
            ]*/
        ],

        'cms' =>
        [
            'class' => '\skeeks\cms\components\Cms',
        ]
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
            'displayTimezone' => 'Asia/Kolkata',

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
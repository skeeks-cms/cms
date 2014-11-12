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
            'clusters' =>
            [
                [
                    'class'                 => 'skeeks\cms\components\storage\ClusterLocal',
                    "id"                    => "local",

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

        'registeredModels' =>
        [
            'class' => 'skeeks\cms\components\RegisteredModels',
            //Модели к которым можно крепить другие, то есть эти модели имеют ссылку на себя объект Ref
            'models' =>
            [
                'publication' =>
                [
                    'class'                 => 'skeeks\cms\models\Publication',
                    'name'                 => 'Публикация',
                ],

                'tree' =>
                [
                    'class'             => 'skeeks\cms\models\Tree',
                    'name'             => 'Страница',
                ],

                'comment' =>
                [
                    'class'             => 'skeeks\cms\models\Comment',
                    'name'             => 'Комментарий',
                ],

                'user' =>
                [
                    'class'             => 'skeeks\cms\models\User',
                    'name'             => 'Пользователь',
                ],

                'userGroup' =>
                [
                    'class'             => 'skeeks\cms\models\UserGroup',
                    'name'             => 'Группа пользователя',
                ],

                'vote'      =>
                [
                    'class' => 'skeeks\cms\models\Vote',
                    'name' => 'Голос'
                ],

                'subscribe'   =>
                [
                    'class' => 'skeeks\cms\models\Subscribe',
                    'name' => 'Подписка'
                ],
            ],
        ],

        'registeredWidgets' =>
        [
            'class' => 'skeeks\cms\components\RegisteredWidgets',
            'widgets' => include_once 'widgets.php'

        ],

        'adminMenu' =>
        [
            'class' => \skeeks\cms\modules\admin\components\Menu::className(),
            'groups' => include_once 'admin-menu.php'
        ],

        'treeTypes' =>
        [
            'class' => \skeeks\cms\components\TreeTypes::className(),

            'components' =>
            [
                'news' =>
                [
                    'name' => 'Новостной раздел'
                ],

                'article' =>
                [
                    'name'      => 'Раздел статей',
                ]
            ]
        ],

        'publicationTypes' =>
        [
            'class' => \skeeks\cms\components\PublicationTypes::className(),

            'components' =>
            [
                'news' =>
                [
                    'name' => 'Новость'
                ],

                'article' =>
                [
                    'name'      => 'Статья',
                ],

                'demo' =>
                [
                    'name'      => 'Демо нода',
                    'enabled'   => false
                ]
            ]
        ]
    ],


    'modules' => [

        'admin' =>
        [
            'class' => \skeeks\cms\modules\admin\Module::className()
        ],

        'cms' =>
        [
            'class'             => \skeeks\cms\Module::className(),
        ],
    ],
];

return $config;
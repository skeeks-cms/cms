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
                    'label'                 => 'Публикация',
                ],

                'tree' =>
                [
                    'class'             => 'skeeks\cms\models\Tree',
                    'label'             => 'Страница',
                ],

                'comment' =>
                [
                    'class'             => 'skeeks\cms\models\Comment',
                    'label'             => 'Комментарий',
                ],

                'user' =>
                [
                    'class'             => 'skeeks\cms\models\User',
                    'label'             => 'Пользователь',
                ],

                'userGroup' =>
                [
                    'class'             => 'skeeks\cms\models\UserGroup',
                    'label'             => 'Группа пользователя',
                ],

                'vote'      =>
                [
                    'class' => 'skeeks\cms\models\Vote',
                    'label' => 'Голос'
                ],

                'subscribe'   =>
                [
                    'class' => 'skeeks\cms\models\Subscribe',
                    'label' => 'Подписка'
                ],
            ],
        ],

        'registeredWidgets' =>
        [
            'class' => 'skeeks\cms\components\RegisteredWidgets',

            'widgets' =>
            [
                'skeeks\cms\widgets\text\Text' =>
                [
                    'label'         => 'Текст',
                    'description'   => 'Виджет просто выводит текст',
                    'templates'     =>
                    [
                        'default' =>
                        [
                            'label' => 'Шаблон по умолчанию'
                        ]
                    ],
                    'enabled'       => true
                ],

                'skeeks\cms\widgets\infoblocks\Infoblocks' =>
                [
                    'label'         => 'Список инфоблоков',
                    'description'   => 'Виджет который содержит в себе другие инфоблоки',
                    'templates'     =>
                    [
                        'default' =>
                        [
                            'label' => 'Шаблон по умолчанию'
                        ]
                    ],
                    'enabled'       => true
                ]
            ]
        ],

        'adminMenu' =>
        [
            'class' => \skeeks\cms\modules\admin\components\Menu::className(),

            'groups' =>
            [
                'cms' =>
                [
                    'label'     => 'Основное',

                    'items' =>
                    [

                        [
                            "label"     => "Сайты",
                            "url"       => ["cms/admin-user-group"],
                        ],

                        [
                            "label"     => "Дерево страниц",
                            "url"       => ["cms/admin-tree"],
                        ],

                        [
                            "label"     => "Инфоблоки",
                            "url"       => ["cms/admin-infoblock"],
                        ],

                        [
                            "label"     => "Управление пользователями",
                            "url"       => ["cms/admin-user"],
                        ],

                        [
                            "label"     => "Управление группами пользователей",
                            "url"       => ["cms/admin-user-group"],
                        ],

                        [
                            "label"     => "Публикации",
                            "url"       => ["cms/admin-publication/index"],
                        ],

                        [
                            "label"     => "Комментарии",
                            "url"       => ["cms/admin-comment/index"],
                        ],

                        [
                            "label"     => "Голоса",
                            "url"       => ["cms/admin-vote/index"],
                        ],

                        [
                            "label"     => "Подписки",
                            "url"       => ["cms/admin-subscribe/index"],
                        ],
                    ]
                ],

                'dev' =>
                [
                    'label'     => 'Для разработчика',
                    'priority'  => 0,
                    'enabled'   => true,

                    'items' =>
                    [
                        [
                            "label"     => "Генератор кода",
                            "url"       => ["admin/gii"],
                            'enabled'   => true,
                            'priority'  => 0,
                        ],

                        [
                            "label"     => "Удаление и чистка",
                            "url"       => ["admin/clear"],
                        ],
                        [
                            "label"     => "Работа с базой данных",
                            "url"       => ["admin/db"],
                        ],
                    ]
                ],
            ]
        ],

        'treeTypes' =>
        [
            'class' => \skeeks\cms\components\TreeTypes::className(),

            'components' =>
            [
                'news' =>
                [
                    'label' => 'Новостной раздел'
                ],

                'article' =>
                [
                    'label' => 'Раздел статей'
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
                    'label' => 'Новость'
                ],

                'article' =>
                [
                    'label' => 'Статья'
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
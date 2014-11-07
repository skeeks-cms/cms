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
                ["class" => 'skeeks\cms\modules\admin\components\UrlRule', 'adminPrefix' => '~sx'],
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

                [
                    'class' => 'skeeks\cms\models\Vote',
                    'label' => 'Голос'
                ],

                [
                    'class' => 'skeeks\cms\models\Subscribe',
                    'label' => 'Подписка'
                ],
            ],
        ]
    ],


    'modules' => [

        'admin' => [
            'class' => \skeeks\cms\modules\admin\Module::className()
        ],

        'cms' => [
            'class'     => \skeeks\cms\Module::className(),
        ],
    ],
];


return $config;
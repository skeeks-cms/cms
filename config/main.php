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
            'identityClass' => 'skeeks\cms\models\User',
            'enableAutoLogin' => true,
        ],

        'cache' => [
            'class' => 'yii\caching\FileCache',
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
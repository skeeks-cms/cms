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
$config = [
    'bootstrap' => ['cms'],

    'components' => [

        'errorHandler' => [
            'errorAction' => 'cms/error/error',
        ],

        'user' => [
            'class' => '\yii\web\User',
            'identityClass' => 'skeeks\cms\models\CmsUser',
            'enableAutoLogin' => true,
            'loginUrl' => ['cms/auth/login'],
        ],

        'assetManager' => [
            'appendTimestamp' => true,

            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'js' => [
                        'jquery.min.js',
                    ]
                ],

                'yii\bootstrap\BootstrapPluginAsset' => [
                    'js' => [
                        'js/bootstrap.min.js',
                    ]
                ],

                'yii\bootstrap\BootstrapAsset' => [
                    'css' => [
                        'css/bootstrap.min.css',
                    ]
                ]
            ],
        ],

        'breadcrumbs' => [
            'class' => '\skeeks\cms\components\Breadcrumbs',
        ],

        'upaBackend' => [
            'menu' => [
                'data' => [
                    'personal' => [
                        'name' => ['skeeks/cms', 'Personal data'],
                        'icon' => 'fa fa-user',
                        'items' => [
                            [
                                'name' => ['skeeks/cms', 'Personal data'],
                                'url' => ['/cms/upa-personal/update'],
                                'icon' => 'fa fa-user',
                            ],
                            [
                                'name' => ['skeeks/cms', 'Change password'],
                                'url' => ['/cms/upa-personal/change-password'],
                                'icon' => 'fa fa-key',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],


    'modules' => [
        'datecontrol' => [
            'class' => 'skeeks\cms\modules\datecontrol\Module',
        ],
    ],
];

return $config;
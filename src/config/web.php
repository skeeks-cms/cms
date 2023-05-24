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
    'bootstrap' => ['cms', 'upaBackend'],

    'components' => [

        'adult' => [
            'class' => \skeeks\cms\components\Adult::class,
        ],

        'errorHandler' => [
            'errorAction' => 'cms/error/error',
        ],

        'user' => [
            'class'           => '\yii\web\User',
            'identityClass'   => 'skeeks\cms\models\CmsUser',
            'enableAutoLogin' => true,
            'loginUrl'        => ['cms/auth/login'],
        ],

        'session' => [

            //Чтобы не разрушался сеанс сессии, после закрытия браузера нужно уставить cookie
            'timeout'      => 3600 * 24 * 365,
            'useCookies'   => true,
            'cookieParams' => [
                'httponly' => true,
                'lifetime' => 3600 * 24 * 365,
            ],
        ],

        'assetManager' => [
            'appendTimestamp' => true,

            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'js' => [
                        'jquery.min.js',
                    ],
                ],

                'yii\bootstrap\BootstrapPluginAsset' => [
                    'js' => [
                        'js/bootstrap.min.js',
                    ],
                ],

                'yii\bootstrap\BootstrapAsset' => [
                    'css' => [
                        'css/bootstrap.min.css',
                    ],
                ],
            ],
        ],

        'view' => [
            'class' => '\skeeks\cms\web\View',
            /*'themes' => [
                "id" => [
                    'class' => \skeeks\cms\base\Theme::class
                ],
            ]*/
        ],

        'breadcrumbs' => [
            'class' => '\skeeks\cms\components\Breadcrumbs',
        ],

        'upaBackend' => [
            'id'    => 'upaBackend',
            'class' => \skeeks\cms\components\UpaBackendComponent::class,
            'menu' => [
                'data' => [
                    'personal' => [
                        'name' => ['skeeks/cms', 'Мой профиль'],
                        'url'  => ['/cms/upa-personal/view'],
                        'icon' => 'icon-user',
                    ],
                    'profile' =>
                    [
                        'priority' => 9999,
                        'name' => 'Личные данные',
                        'url'  => ['/cms/upa-personal/update'],

                        'items' => [
                            [
                                'name' => 'Личные данные',
                                'url'  => ['/cms/upa-personal/update'],
                            ],
                            [
                                'name' => 'Смена пароля',
                                'url'  => ['/cms/upa-personal/password'],
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
<?php
/**
 * базовые глобальные опции
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */
return
[
    'publication' =>
    [
        'modelClass'                 => 'skeeks\cms\models\Publication',
        'name'                      => 'Публикация',

        'types'                 =>
        [
            /*'news' =>
            [
                'name'      => 'Новость'
            ],

            'article' =>
            [
                'name'      => 'Статья',
            ],*/
        ],
    ],

    'tree' =>
    [
        'modelClass'                    => 'skeeks\cms\models\Tree',
        'name'                          => 'Страница',

        /*'types'                 =>
        [
            'homePage' =>
            [
                'name'          => 'Главная страница',
                'actionView'    => 'home',
                'layout'        => 'site2'
            ],

            'secondPage' =>
            [
                'name'          => 'Вторая страница',
                'actionView'    => 'second'
            ],

            'demo' =>
            [
                'name'      => 'Демо нода',
                'enabled'   => false
            ]
        ],

        'actionViews'                 =>
        [
            'home' =>
            [
                'name' => 'Главная страница'
            ],

            'second' =>
            [
                'name'      => 'Вторая страница',
            ],
        ]*/

    ],

    'comment' =>
    [
        'modelClass'             => 'skeeks\cms\models\Comment',
        'name'             => 'Комментарий',
    ],

    'user' =>
    [
        'modelClass'             => 'skeeks\cms\models\User',
        'name'                  => 'Пользователь',
    ],

    'userGroup' =>
    [
        'modelClass'             => 'skeeks\cms\models\UserGroup',
        'name'             => 'Группа пользователя',
    ],

    'vote'      =>
    [
        'modelClass' => 'skeeks\cms\models\Vote',
        'name' => 'Голос'
    ],

    'subscribe'   =>
    [
        'modelClass' => 'skeeks\cms\models\Subscribe',
        'name' => 'Подписка'
    ],

    'storageFile'   =>
    [
        'modelClass' => 'skeeks\cms\models\StorageFile',
        'name' => 'Файл хранилища'
    ],

    'infoblock'   =>
    [
        'modelClass'    => 'skeeks\cms\models\Infoblock',
        'name'          => 'Инфоблок'
    ],

    'staticBlock'   =>
    [
        'modelClass'    => 'skeeks\cms\models\StaticBlock',
        'name'          => 'Инфоблок'
    ],


    'f-crop' =>
    [
        'modelClass'    => 'skeeks\cms\components\imaging\filters\Crop',
        'name'          => 'Фильтро обрезать'
    ],
];
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
        'modelClass'                    => 'skeeks\cms\models\Publication',
        'name'                          => 'Публикация',
        'adminControllerRoute'          => 'cms/admin-publication',

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
        'adminControllerRoute'          => 'cms/admin-tree',

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

    'cmsContentElement'   =>
    [
        'modelClass'    => 'skeeks\cms\models\CmsContentElement',
        'name'          => 'Элемент контента'
    ],


    'f-crop' =>
    [
        'modelClass'    => 'skeeks\cms\components\imaging\filters\Crop',
        'name'          => 'Фильтр обрезать'
    ],

    'f-thumbnail' =>
    [
        'modelClass'    => 'skeeks\cms\components\imaging\filters\Thumbnail',
        'name'          => 'Фильтр Thumbnail'
    ],
];
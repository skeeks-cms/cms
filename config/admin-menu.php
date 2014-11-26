<?php
/**
 * admin-menu
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 12.11.2014
 * @since 1.0.0
 */

return
[
    'cms' =>
    [
        'label'     => 'Основное',

        'items' =>
        [

            [
                "label"     => "Сайты",
                "url"       => ["cms/admin-site"],
            ],

            [
                "label"     => "Дерево разделов",
                "url"       => ["cms/admin-tree"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.gif']
            ],

            [
                "label"     => 'Статические блоки',
                "url"       => ["cms/admin-static-block"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.conct.png']
            ],

            [
                "label"     => "Инфоблоки",
                "url"       => ["cms/admin-infoblock"],

            ],

            [
                "label"     => "Управление пользователями",
                "url"       => ["cms/admin-user"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.users.png']
            ],

            [
                "label"     => "Управление группами пользователей",
                "url"       => ["cms/admin-user-group"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.users_role.png'],
                'enabled'   => false
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

    'storage' =>
    [
        'label' => 'Файловое хранилище',
        'enabled' => true,

        'items' =>
        [
            [
                "label"     => "Сервера",
                "url"       => ["cms/admin-storage/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/storage_file.png'],
            ],

            [
                "label"     => "Файлы",
                "url"       => ["cms/admin-storage-files/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/storage_file.png'],
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
                "label"     => "Удаление и чистка",
                "url"       => ["admin/clear"],
            ],

            [
                "label"     => "Работа с базой данных",
                "url"       => ["admin/db"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.bd_arch.png'],
            ],
        ]
    ],


    'access' =>
    [
        'label'     => 'Права доступа',
        'priority'  => 0,
        'enabled'   => true,

        'items' =>
        [
            [
                "label"     => "Роли",
                "url"       => ["admin/role"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.users_role.png'],
                'enabled'   => true,
                'priority'  => 0,
            ],

            [
                "label"     => "Привилегии",
                "url"       => ["admin/permission"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/access.png'],
                'enabled'   => true,
                'priority'  => 0,
            ],

            /*[
                "label"     => "Правила",
                "url"       => ["admin/rule"],
                'enabled'   => true,
                'priority'  => 0,
            ],*/
        ]
    ],
];
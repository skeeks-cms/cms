<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 12.03.2015
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
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/www.png']
            ],

            [
                "label"     => "Дерево разделов",
                "url"       => ["cms/admin-tree"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.gif']
            ],

            [
                "label"     => "Метки разделов",
                "url"       => ["cms/admin-tree-menu"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.menu.png']
            ],

            [
                "label"     => 'Статические блоки',
                "url"       => ["cms/admin-static-block"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.conct.png']
            ],

            [
                "label"     => "Инфоблоки",
                "url"       => ["cms/admin-infoblock"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.infoblock.png']
            ],

            [
                "label"     => "Публикации",
                "url"       => ["cms/admin-publication"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.article.png']
            ],

            [
                "label"     => "Файловый менеджер",
                "url"       => ["cms/admin-file-manager"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/storage_file.png'],
            ],



        ]
    ],




    'access' =>
    [
        'label'     => 'Пользователи, права доступа',
        'priority'  => 0,
        'enabled'   => true,

        'items' =>
        [
            [
                "label"     => "Управление пользователями",
                "url"       => ["cms/admin-user"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png']
            ],

            [
                "label"     => "Управление группами пользователей",
                "url"       => ["cms/admin-user-group"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.users_role.png'],
                'enabled'   => false
            ],

            [
                "label"     => "Роли",
                "url"       => ["admin/admin-role"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.users_role.png'],
                'enabled'   => true,
                'priority'  => 0,
            ],

            [
                "label"     => "Привилегии",
                "url"       => ["admin/admin-permission"],
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

    'storage' =>
    [
        'label' => 'Файловое хранилище',
        'enabled' => true,

        'items' =>
        [
            [
                "label"     => "Сервера",
                "url"       => ["cms/admin-storage/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/servers.png'],
            ],

            [
                "label"     => "Файлы в хранилище",
                "url"       => ["cms/admin-storage-files/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/storage_file.png'],
            ],
        ]
    ],

    'social' =>
    [
        'label'     => 'Социальные элементы',
        'priority'  => 0,
        'enabled'   => true,

        'items' =>
        [
            [
                "label"     => "Комментарии",
                "url"       => ["cms/admin-comment/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/comments.png']
            ],

            [
                "label"     => "Голоса",
                "url"       => ["cms/admin-vote/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/votes.png']
            ],

            [
                "label"     => "Подписки",
                "url"       => ["cms/admin-subscribe/index"],
            ],
        ]
    ],


    'dev' =>
    [
        'label'     => 'Система',
        'priority'  => 0,
        'enabled'   => true,

        'items' =>
        [
            [
                "label"     => "Информация",
                "url"       => ["admin/info"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.infoblock.png'],
            ],

            [
                "label"     => "Читска временных данных",
                "url"       => ["admin/clear"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/clear.png'],
            ],

            [
                "label"     => "Работа с базой данных",
                "url"       => ["admin/db"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.bd_arch.png'],
            ],

            [
                "label"     => "Обновления",
                "url"       => ["admin/update"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/update.png'],
            ],

            [
                "label"     => "Ssh console",
                "url"       => ["admin/ssh"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/ssh.png'],
            ],
        ]
    ],



];
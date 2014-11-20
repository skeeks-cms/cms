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
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.users_role.png']
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


    'access' =>
    [
        'label'     => 'Права доступа',
        'priority'  => 0,
        'enabled'   => true,

        'items' =>
        [
            [
                "label"     => "Роли",
                "url"       => ["admin/roles"],
                'enabled'   => true,
                'priority'  => 0,
            ],


        ]
    ],
];
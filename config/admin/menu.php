<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 12.03.2015
 */

return
[

    function()
    {
        $result = [];

        if ($contentTypes = \skeeks\cms\models\CmsContentType::find()->orderBy("priority DESC")->all())
        {
            /**
             * @var $contentType \skeeks\cms\models\CmsContentType
             */
            foreach ($contentTypes as $contentType)
            {
                $itemData = [
                    'code'      => "content-block-" . $contentType->id,
                    'label'     => $contentType->name,
                    "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.article.png'],
                ];

                if ($contents = $contentType->cmsContents)
                {
                    foreach ($contents as $content)
                    {
                        $itemData['items'][] =
                        [
                            'label' => $content->name,
                            'url'   => ["cms/admin-cms-content-element", "content_id" => $content->id, "content_type" => $contentType->code],
                        ];
                    }
                }

                $result[] = new \skeeks\cms\modules\admin\helpers\AdminMenuItemCmsConent($itemData);
            }
        }

        return $result;
    },

    'cms' =>
    [
        'label'     => 'Основное',
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.gif'],

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

            /*[
                "label"     => 'Статические блоки',
                "url"       => ["cms/admin-static-block"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.conct.png']
            ],*/

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

            [
                "label"     => "Настройки контента",
                "url"       => ["cms/admin-cms-content-type"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/content.png'],
            ],

            [
                "label"     => "Настройки модулей",
                "url"       => ["cms/admin-settings"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings.png'],
            ],
        ]
    ],




    'access' =>
    [
        'label'     => 'Пользователи, права доступа',
        'priority'  => 0,
        'enabled'   => true,

        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png'],

        'items' =>
        [
            [
                "label"     => "Управление пользователями",
                "url"       => ["cms/admin-user"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png']
            ],

            /*[
                "label"     => "Управление группами пользователей",
                "url"       => ["cms/admin-user-group"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.users_role.png'],
                'enabled'   => true
            ],*/

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

        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/storage_file.png'],

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

    /*'social' =>
    [
        'label'     => 'Социальные элементы',
        'priority'  => 0,
        'enabled'   => true,

        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/votes.png'],

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
    ],*/


    'dev' =>
    [
        'label'     => 'Инструменты',
        'priority'  => 0,
        'enabled'   => true,

        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/tools.png'],

        'items' =>
        [
            [
                "label"     => "Проверка системы",
                "url"       => ["admin/checker"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/tools.png'],
            ],

            [
                "label"     => "Информация",
                "url"       => ["admin/info"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.infoblock.png'],
            ],

            [
                "label"     => "Отправка email",
                "url"       => ["admin/email"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/email.png'],
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
            [
                "label"     => "Генератор кода gii",
                "url"       => ["admin/gii"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/ssh.png'],
            ],
        ]
    ],



];
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.03.2015
 */
return [

    'rules' => [
        [
            'class' => \skeeks\cms\rbac\AuthorRule::class,
        ],
    ],

    'roles' => [

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_ROOT,
            'description' => ['skeeks/cms', 'Superuser'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_GUEST,
            'description' => ['skeeks/cms', 'Unauthorized user'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_ADMIN,
            'description' => ['skeeks/cms', 'Admin'],

            'child' => [
                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_CONTROLL_PANEL,

                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_ADDITIONAL_FILES,

                    "cms/admin-settings",
                    "cms/admin-info",

                    "cms/admin-cms-site",
                    "cms/admin-cms-lang",

                    "cms/admin-tree",
                    "cms/admin-tree/new-children",
                    "cms/admin-tree/update",
                    "cms/admin-tree/delete",
                    "cms/admin-tree/delete-multi",
                    "cms/admin-tree/list",
                    "cms/admin-tree/move",
                    "cms/admin-tree/resort",

                    "cms/admin-storage-files",
                    "cms/admin-storage-files/upload",
                    "cms/admin-storage-files/index",
                    "cms/admin-storage-files/update",
                    "cms/admin-storage-files/delete",
                    "cms/admin-storage-files/delete-mult",
                    "cms/admin-storage-files/download",
                    "cms/admin-storage-files/delete-tmp-dir",


                    "cms/admin-user",
                    "cms/admin-user/create",
                    "cms/admin-user/update",
                    "cms/admin-user/update-advanced",
                    "cms/admin-user/delete",
                    "cms/admin-user/delete-multi",
                    "cms/admin-user/activate-multi",
                    "cms/admin-user/deactivate-multi",

                    "cms/admin-storage",
                    "cms/admin-cms-tree-type",
                    "cms/admin-cms-tree-type-property",
                    "cms/admin-cms-tree-type-property-enum",

                    "cms/admin-cms-content-property",
                    "cms/admin-cms-content-property-enum",

                    "cms/admin-cms-user-universal-property",
                    "cms/admin-cms-user-universal-property-enum",
                ],
            ],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_MANGER,
            'description' => ['skeeks/cms', 'Manager (access to the administration)'],

            'child' => [


                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_CONTROLL_PANEL,

                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,

                    "cms/admin-tree",
                    "cms/admin-tree/new-children",
                    "cms/admin-tree/update",
                    "cms/admin-tree/move",
                    "cms/admin-tree/resort",
                    "cms/admin-tree/delete/own",

                    "cms/admin-storage-files",
                    "cms/admin-storage-files/upload",
                    "cms/admin-storage-files/index",
                    "cms/admin-storage-files/update",
                    "cms/admin-storage-files/download",
                    "cms/admin-storage-files/delete/own",
                    "cms/admin-storage-files/delete-tmp-dir",
                ],
            ],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_EDITOR,
            'description' => ['skeeks/cms', 'Editor (access to the administration)'],

            'child' => [

                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_CONTROLL_PANEL,

                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,

                    "cms/admin-tree",
                    "cms/admin-tree/new-children",
                    "cms/admin-tree/update/own",
                    "cms/admin-tree/delete/own",
                    "cms/admin-tree/move/own",


                    "cms/admin-storage-files",
                    "cms/admin-storage-files/upload",
                    "cms/admin-storage-files/index/own",
                    "cms/admin-storage-files/delete-tmp-dir/own",
                    "cms/admin-storage-files/download/own",
                    "cms/admin-storage-files/delete/own",
                    "cms/admin-storage-files/update/own",
                ],
            ],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_USER,
            'description' => ['skeeks/cms', 'Registered user'],

            //Есть доступ к системе администрирования
            'child'       => [
                'permissions' => [
                    \skeeks\cms\components\Cms::UPA_PERMISSION,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
                ],
            ],

        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_APPROVED,
            'description' => ['skeeks/cms', 'Confirmed user'],

            //Есть доступ к системе администрирования
            'permissions' => [
                \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
            ],
        ],
    ],

    'permissions' => [
        [
            'name'        =>\skeeks\cms\rbac\CmsManager::PERMISSION_ROOT_ACCESS,
            'description' => ['skeeks/cms', 'Возможности суперадминистратора'],
        ],

        [
            'name'        => \skeeks\cms\components\Cms::UPA_PERMISSION,
            'description' => ['skeeks/cms', 'Доступ к персональной части'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
            'description' => ['skeeks/cms', 'Access to system administration'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_CONTROLL_PANEL,
            'description' => ['skeeks/cms', 'Access to the site control panel'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_EDIT_VIEW_FILES,
            'description' => ['skeeks/cms', 'The ability to edit view files'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
            'description' => ['skeeks/cms', 'Access to personal files'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,
            'description' => ['skeeks/cms', 'Access to the public files'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_ADDITIONAL_FILES,
            'description' => ['skeeks/cms', 'Access to all files'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_DASHBOARDS_EDIT,
            'description' => ['skeeks/cms', 'Access to edit dashboards'],
        ],


        [
            'name'        => 'cms/admin-cms-site',
            'description' => ['skeeks/cms', 'Управление сайтами'],
        ],
        [
            'name'        => 'cms/admin-cms-lang',
            'description' => ['skeeks/cms', 'Управление языками'],
        ],
        [
            'name'        => 'cms/admin-storage-files',
            'description' => ['skeeks/cms', 'Управление языками'],
        ],
        [
            'name'        => 'cms/admin-storage-files/index',
            'description' => ['skeeks/cms', 'Просмотр списка своих файлов'],
        ],
        [
            'name'        => 'cms/admin-storage-files/index/own',
            'description' => ['skeeks/cms', 'Просмотр списка своих файлов'],
        ],
        [
            'name'        => 'cms/admin-tree/resort',
            'description' => ['skeeks/cms', 'Сортировать подразделы'],
        ],
        [
            'name'        => 'cms/admin-tree/new-children',
            'description' => ['skeeks/cms', 'Создать подраздел'],
        ],



        //Управление пользователями
        [
            'name'        => 'cms/admin-user',
            'description' => ['skeeks/cms', 'Управление пользователями'],
        ],

        [
            'name'        => 'cms/admin-user/update',
            'description' => ['skeeks/cms', 'Редактирование данных пользователя'],
        ],

        [
            'name'        => 'cms/admin-user/create',
            'description' => ['skeeks/cms', 'Создать пользователя'],
        ],

        [
            'name'        => 'cms/admin-user/update-advanced',
            'description' => ['skeeks/cms', 'Расширенное редактирование данных пользователя'],
        ],

        [
            'name'        => 'cms/admin-user/delete',
            'description' => ['skeeks/cms', 'Удаление пользователя'],
        ],

        /*[
            'name'        => 'cms/admin-user/update/not-root',
            'description' => ['skeeks/crm', 'Редактирование данных доступного пользователя'],
            'child' => [
                'permissions' => [
                    'cms/admin-user/update',
                ],
            ],
            'ruleName' => \skeeks\cms\rbac\rules\CmsUserNotRootRule::class
        ],*/


    ],


];
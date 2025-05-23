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
            'class' => \skeeks\cms\rbac\CmsLogRule::class,
            /*'class' => \skeeks\cms\rbac\CmsWorkerRule::class,*/
            'class' => \skeeks\cms\rbac\CmsUserRule::class,
            'class' => \skeeks\cms\rbac\CmsTaskRule::class,
            'class' => \skeeks\cms\rbac\CmsCompanyRule::class,
        ],
    ],

    'roles' => [

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_GUEST,
            'description' => ['skeeks/cms', 'Unauthorized user'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_USER,
            'description' => ['skeeks/cms', 'Пользователь'],

            //Есть доступ к системе администрирования
            'child'       => [
                'permissions' => [
                    \skeeks\cms\components\Cms::UPA_PERMISSION,
                ],
            ],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_WORKER,
            'description' => ['skeeks/cms', 'Сотрудник'],

            'child' => [
                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,

                    'cms/admin-storage-files',
                    'cms/admin-storage-files/index/own',
                    "cms/admin-storage-files/delete-tmp-dir/own",
                    "cms/admin-storage-files/download/own",
                    "cms/admin-storage-files/delete/own",
                    "cms/admin-storage-files/update/own",

                    "cms/admin-cms-log/update-delete/own",


                    "cms/admin-worker",

                    "cms/admin-user",
                    "cms/admin-user/manage/own",

                    "cms/admin-task",
                    "cms/admin-task/manage/own",
                ],
            ],
        ],


        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_EDITOR,
            'description' => ['skeeks/cms', 'Редактор контента'],

            'child' => [

                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::ROLE_WORKER,

                    "cms/admin-element",
                    "cms/admin-element/index",
                    "cms/admin-element/create",
                    "cms/admin-element/update/own",
                    "cms/admin-element/delete/own",



                    "cms/admin-tree",
                    "cms/admin-tree/index",
                    "cms/admin-tree/new-children",
                    "cms/admin-tree/update/own",
                    "cms/admin-tree/delete/own",
                    "cms/admin-tree/move/own",
                    "cms/admin-tree/resort/own",


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
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_MAIN_EDITOR,
            'description' => ['skeeks/cms', 'Главный редактор контента'],

            'child' => [


                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::ROLE_WORKER,

                    "cms/admin-element",
                    "cms/admin-element/index",
                    "cms/admin-element/create",
                    "cms/admin-element/update",
                    "cms/admin-element/delete/own",

                    "cms/admin-tree",
                    "cms/admin-tree/index",
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

                    "cms/admin-cms-saved-filter",

                ],
            ],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_MANGER,
            'description' => ['skeeks/cms', 'Менеджер'],

            'child' => [


                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::ROLE_WORKER,

                    "cms/admin-company",
                    "cms/admin-company/manage/own",
                ],
            ],
        ],
        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_MARKETER,
            'description' => ['skeeks/cms', 'Маркетолог'],

            'child' => [


                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::ROLE_WORKER,

                    "shop/admin-bonus-transaction",
                    "shop/admin-discount",
                    "shop/admin-discount-coupon",
                    "shop/admin-shop-feedback",
                ],
            ],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_ADMIN,
            'description' => ['skeeks/cms', 'Admin'],

            'child' => [
                //Есть доступ к системе администрирования
                'permissions' => [
                    \skeeks\cms\rbac\CmsManager::ROLE_WORKER,

                    \skeeks\cms\rbac\CmsManager::PERMISSION_ROLE_ADMIN_ACCESS,


                    "cms/admin-tree",
                    "cms/admin-tree/index",
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


                    "cms/admin-task",

                    "cms/admin-user",
                    "cms/admin-user/manage",

                    "cms/admin-company",
                    "cms/admin-company/manage",

                    "cms/admin-cms-log/update-delete",

                ],
            ],
        ],


        [
            'name'        => \skeeks\cms\rbac\CmsManager::ROLE_ROOT,
            'description' => ['skeeks/cms', 'Superuser'],
        ],


    ],

    'permissions' => [
        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ROOT_ACCESS,
            'description' => ['skeeks/cms', 'Возможности суперадминистратора'],
        ],
        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ROLE_ADMIN_ACCESS,
            'description' => ['skeeks/cms', 'Возможности администратора'],
        ],

        [
            'name'        => \skeeks\cms\components\Cms::UPA_PERMISSION,
            'description' => ['skeeks/cms', 'Доступ к личному кабинету клиента'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
            'description' => ['skeeks/cms', 'Доступ к кабинету сотрудника'],
        ],

        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_EDIT_VIEW_FILES,
            'description' => ['skeeks/cms', 'Редактирование шаблонов'],
        ],


        [
            'name'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_DASHBOARDS_EDIT,
            'description' => ['skeeks/cms', 'Access to edit dashboards'],
        ],


        [
            'name'        => 'cms/admin-storage-files/index',
            'description' => ['skeeks/cms', 'Файловое хранилище | Прсмотр всех файлов'],
        ],

        [
            'name'        => 'cms/admin-storage-files/index/own',
            'description' => ['skeeks/cms', 'Файловое хранилище | Прсмотр только своих файлов'],
        ],


        [
            'name'        => 'cms/admin-cms-saved-filter',
            'description' => ['skeeks/cms', 'Сохраненные фильтры'],
        ],



        [
            'name'        => 'cms/admin-company',
            'description' => ['skeeks/cms', 'Компании'],
        ],

        [
            'name'        => 'cms/admin-company/manage',
            'description' => ['skeeks/cms', 'Управление компаниями'],
        ],

        [
            'name'        => 'cms/admin-company/manage/own',
            'description' => ['skeeks/cms', 'Управление компаниями (только доступные)'],
            'child' => [
                'permissions' => [
                    'cms/admin-company/manage',
                ],
            ],
            'ruleName' => \skeeks\cms\rbac\CmsCompanyRule::NAME
        ],
        
        
        
        [
            'name'        => 'cms/admin-tree',
            'description' => ['skeeks/cms', 'Разделы сайта'],
        ],


        [
            'name'        => 'cms/admin-task',
            'description' => ['skeeks/cms', 'Задачи'],
        ],

        [
            'name'        => 'cms/admin-task/manage',
            'description' => ['skeeks/cms', 'Управление задачами'],
        ],

        [
            'name'        => 'cms/admin-task/manage/own',
            'description' => ['skeeks/cms', 'Управление задачами (только доступные)'],
            'child' => [
                'permissions' => [
                    'cms/admin-task/manage',
                ],
            ],
            'ruleName' => \skeeks\cms\rbac\CmsTaskRule::NAME
        ],



        [
            'name'        => 'cms/admin-user',
            'description' => ['skeeks/cms', 'Клиенты'],
        ],

        [
            'name'        => 'cms/admin-user/manage',
            'description' => ['skeeks/cms', 'Управление клиентами'],
        ],

        [
            'name'        => 'cms/admin-user/manage/own',
            'description' => ['skeeks/cms', 'Управление клиентами (только доступные)'],
            'child' => [
                'permissions' => [
                    'cms/admin-user/manage',
                ],
            ],
            'ruleName' => \skeeks\cms\rbac\CmsUserRule::NAME
        ],


        /**
         * Доступ к элементам
         */
        [
            'name'        => 'cms/admin-element',
            'description' => ['skeeks/cms', 'Элементы'],
        ],

        [
            'name'        => 'cms/admin-element/index',
            'description' => ['skeeks/cms', 'Элементы | Список'],
        ],

        [
            'name'        => 'cms/admin-element/create',
            'description' => ['skeeks/cms', 'Элементы | Добавить'],
        ],

        [
            'name'        => 'cms/admin-element/update',
            'description' => ['skeeks/cms', 'Элементы | Редактировать'],
        ],

        [
            'name'        => 'cms/admin-element/update/own',
            'description' => ['skeeks/cms', 'Элементы | Редактировать (только свои)'],
            'child' => [
                'permissions' => [
                    'cms/admin-element/update',
                ],
            ],
            'ruleName' => \skeeks\cms\rbac\AuthorRule::NAME
        ],

        [
            'name'        => 'cms/admin-element/delete',
            'description' => ['skeeks/cms', 'Элементы | Удалить'],
        ],


        [
            'name'        => 'cms/admin-element/delete/own',
            'description' => ['skeeks/cms', 'Элементы | Удалить (только свои)'],
            'child' => [
                'permissions' => [
                    'cms/admin-element/delete',
                ],
            ],
            'ruleName' => \skeeks\cms\rbac\AuthorRule::NAME
        ],


        [
            'name'        => 'cms/admin-worker',
            'description' => ['skeeks/cms', 'Доступ к сотрудникам'],
        ],




        [
            'name'        => 'cms/admin-cms-log/update-delete',
            'description' => ['skeeks/cms', 'Обновление и удаление логов'],
        ],

        [
            'name'        => 'cms/admin-cms-log/update-delete/own',
            'description' => ['skeeks/cms', 'Обновление и удаление логов (только своих комментариев)'],
            'child' => [
                'permissions' => [
                    'cms/admin-cms-log/update-delete',
                ],
            ],
            'ruleName' => \skeeks\cms\rbac\CmsLogRule::NAME
        ],
    ],


];
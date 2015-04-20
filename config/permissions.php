<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.03.2015
 */
return [

    'rules'         => [
        [
            'class' => \skeeks\cms\rbac\AuthorRule::className(),
        ]
    ],

    'roles'         => [

        [
            'name'          => \skeeks\cms\rbac\CmsManager::ROLE_ROOT,
            'description'   => 'Суперпользователь',
        ],

        [
            'name'          => \skeeks\cms\rbac\CmsManager::ROLE_ADMIN,
            'description'   => 'Администратор',

            'child'         =>
            [
                //Обладает возможностями всех ролей
                'roles' =>
                [
                    \skeeks\cms\rbac\CmsManager::ROLE_MANGER,
                    \skeeks\cms\rbac\CmsManager::ROLE_EDITOR,
                    \skeeks\cms\rbac\CmsManager::ROLE_USER,
                ],

                //Есть доступ к системе администрирования
                'permissions' =>
                [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_CONTROLL_PANEL,

                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_CREATE,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_DELETE,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_ADVANCED,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_ADDITIONAL_FILES,
                ],
            ]
        ],

        [
            'name'          => \skeeks\cms\rbac\CmsManager::ROLE_MANGER,
            'description'   => 'Менеджер',

            'child'         =>
            [
                //Обладает возможностями всех ролей
                'roles' =>
                [
                    \skeeks\cms\rbac\CmsManager::ROLE_EDITOR,
                    \skeeks\cms\rbac\CmsManager::ROLE_USER,
                ],

                //Есть доступ к системе администрирования
                'permissions' =>
                [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_CONTROLL_PANEL,

                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_CREATE,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_DELETE,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,
                ],
            ]
        ],

        [
            'name'          => \skeeks\cms\rbac\CmsManager::ROLE_EDITOR,
            'description'   => 'Редактор',

            'child'         =>
            [
                //Обладает возможностями всех ролей
                'roles' =>
                [
                    \skeeks\cms\rbac\CmsManager::ROLE_USER,
                ],

                //Есть доступ к системе администрирования
                'permissions' =>
                [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_CONTROLL_PANEL,

                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_CREATE,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_OWN,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_DELETE_OWN,

                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,
                ],
            ]
        ],

        [
            'name'          => \skeeks\cms\rbac\CmsManager::ROLE_USER,
            'description'   => 'Пользователь',

            //Есть доступ к системе администрирования
            'permissions' =>
            [
                \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
            ],
        ]
    ],

    'permissions'   => [
        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
            'description'   => 'Доступ к системе администрирования'
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_CONTROLL_PANEL,
            'description'   => 'Доступ к панеле управления сайтом'
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_CREATE,
            'description'   => 'Возможность создания записей'
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE,
            'description'   => 'Обновление данных записей',
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_OWN,
            'description'   => 'Обновление данных своих записей',
            'ruleName'      => (new \skeeks\cms\rbac\AuthorRule())->name,
            'child'         =>
            [
                'permissions' =>
                [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE,
                ],
            ]
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_ADVANCED,
            'description'   => 'Обновление дополнительных данных записей',
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_ADVANCED_OWN,
            'description'   => 'Обновление дополнительных данных своих записей',
            'ruleName'      => (new \skeeks\cms\rbac\AuthorRule())->name,
            'child'         =>
            [
                'permissions' =>
                [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_ADVANCED,
                ],
            ]
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_DELETE,
            'description'   => 'Удаление записей',
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_DELETE_OWN,
            'description'   => 'Удаление своих записей',
            'ruleName'      => (new \skeeks\cms\rbac\AuthorRule())->name,
            'child'         =>
            [
                'permissions' =>
                [
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_DELETE,
                ],
            ]
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
            'description'   => 'Доступ к личным файлам'
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,
            'description'   => 'Доступ к общим публичным файлам'
        ],

        [
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_ADDITIONAL_FILES,
            'description'   => 'Доступ ко всем файлам'
        ],
    ],


];
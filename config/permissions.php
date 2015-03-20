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
                    \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_EDIT_SYSTEM,
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
                ],
            ]
        ],

        [
            'name'          => \skeeks\cms\rbac\CmsManager::ROLE_USER,
            'description'   => 'Пользователь'
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
            'name' => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_EDIT_SYSTEM,
            'description'   => 'Управление служебныеми данными',
        ]
    ],


];
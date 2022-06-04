<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 12.03.2015
 */

/**
 * Меню контента
 * @return array
 */
function contentMenu()
{
    $result = [];

    if ($contentTypes = \skeeks\cms\models\CmsContentType::find()->orderBy("priority ASC")->all()) {
        /**
         * @var $contentType \skeeks\cms\models\CmsContentType
         */
        foreach ($contentTypes as $contentType) {
            $itemData = [
                'code'  => "content-block-".$contentType->id,
                'label' => $contentType->name,
                "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.article.png'],
            ];

            $contents = $contentType->getCmsContents()->andWhere(['is_visible' => 1])->all();

            if ($contents) {
                foreach ($contents as $content) {
                    $itemData['items'][] = [
                        'label'          => $content->name,
                        'url'            => ["cms/admin-cms-content-element", "content_id" => $content->id],
                        "activeCallback" => function ($adminMenuItem) use ($content) {
                            return (bool)($content->id == \Yii::$app->request->get("content_id") && \Yii::$app->controller->uniqueId == 'cms/admin-cms-content-element');
                        },

                        "accessCallback" => function ($adminMenuItem) use ($content) {

                            $permissionNames = "cms/admin-cms-content-element__".$content->id;
                            foreach ([$permissionNames] as $permissionName) {
                                if ($permission = \Yii::$app->authManager->getPermission($permissionName)) {
                                    if (!\Yii::$app->user->can($permission->name)) {
                                        return false;
                                    }
                                }
                            }

                            return true;
                        },

                    ];
                }
            }

            if (isset($itemData['items'])) {
                $result[] = $itemData;
            }
        }
    }

    return $result;
}

;


/**
 * Меню контента
 * @return array
 */
function dashboardsMenu()
{
    $result = [];
    $dashboards = \skeeks\cms\models\CmsDashboard::find()->orderBy("priority ASC")->all();
    if (count($dashboards) > 1) {
        /**
         * @var $dashboard \skeeks\cms\models\CmsDashboard
         */
        foreach ($dashboards as $dashboard) {
            $itemData = [
                'label'          => $dashboard->name,
                "img"            => ['\skeeks\cms\assets\CmsAsset', 'images/icons/dashboard.png'],
                'url'            => ["admin/admin-index/dashboard", "pk" => $dashboard->id],
                "activeCallback" => function ($adminMenuItem) {
                    return (bool)(\Yii::$app->controller->action->uniqueId == 'admin/admin-index/dashboard' && \yii\helpers\ArrayHelper::getValue($adminMenuItem->urlData,
                            'pk') == \Yii::$app->request->get('pk'));
                },
            ];

            $result[] = $itemData;
        }

        return [
            'dashboard' =>
                [
                    'priority' => 90,
                    'label'    => \Yii::t('skeeks/cms', 'Dashboards'),
                    "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/dashboard.png'],

                    'items' => $result,
                ],
        ];

    } elseif (count($dashboards) == 1) {
        $dashboard = $dashboards[0];

        return [
            'dashboard' => [
                'priority'       => 90,
                'label'          => $dashboard->name,
                "img"            => ['\skeeks\cms\assets\CmsAsset', 'images/icons/dashboard.png'],
                'url'            => ["admin/admin-index/dashboard", "pk" => $dashboard->id],
                "activeCallback" => function ($adminMenuItem) {
                    return (bool)(\Yii::$app->controller->action->uniqueId == 'admin/admin-index/dashboard' && \yii\helpers\ArrayHelper::getValue($adminMenuItem->urlData,
                            'pk') == \Yii::$app->request->get('pk'));
                },
            ],
        ];

    } else {
        $result[] = [
            "label" => \Yii::t('skeeks/cms', "Рабочий стол 1"),
            "url"   => ["admin/admin-index"],
            "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/dashboard.png'],
        ];
    }

    return $result;
}

;

/**
 * Меню контента
 * @return array
 */
function contentEditMenu()
{
    $result = [];

    if ($contentTypes = \skeeks\cms\models\CmsContentType::find()->orderBy("priority ASC")->all()) {
        /**
         * @var $contentType \skeeks\cms\models\CmsContentType
         */
        foreach ($contentTypes as $contentType) {
            $itemData = [
                'code'           => "content-block-edit-".$contentType->id,
                'url'            => ["/cms/admin-cms-content-type/update", "pk" => $contentType->id],
                'label'          => $contentType->name,
                "img"            => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.article.png'],
                "activeCallback" => function ($adminMenuItem) {
                    return (bool)(\Yii::$app->controller->action->uniqueId == 'cms/admin-cms-content-type/update' && \yii\helpers\ArrayHelper::getValue($adminMenuItem->urlData,
                            'pk') == \Yii::$app->request->get('pk'));
                },
            ];

            if ($contents = $contentType->cmsContents) {
                foreach ($contents as $content) {
                    $itemData['items'][] =
                        [
                            'label'          => $content->name,
                            'url'            => ["cms/admin-cms-content/update", "pk" => $content->id],
                            "activeCallback" => function ($adminMenuItem) {
                                return (bool)(\Yii::$app->controller->action->uniqueId == 'cms/admin-cms-content/update' && \yii\helpers\ArrayHelper::getValue($adminMenuItem->urlData,
                                        'pk') == \Yii::$app->request->get('pk'));
                            },
                        ];
                }
            }


            $result[] = $itemData;
        }
    }

    return $result;
}

;

function componentsMenu()
{
    $result = [];

    if (\Yii::$app instanceof \yii\console\Application) {
        return $result;
    }

    foreach (\Yii::$app->getComponents(true) as $id => $data) {
        try {
            $loadedComponent = \Yii::$app->get($id);
            if ($loadedComponent instanceof \skeeks\cms\base\Component) {
                $result[] = [
                    'label'          => $loadedComponent->descriptor->name,
                    'url'            => ["cms/admin-settings", "component" => $loadedComponent->className()],
                    "activeCallback" => function ($adminMenuItem) {
                        return (bool)(\Yii::$app->request->getUrl() == $adminMenuItem->getUrl());
                    },
                ];
            }
        } catch (\Exception $e) {

        }

    }

    return $result;
}

return array_merge(dashboardsMenu(), [
    /*'dashboard' =>
        [
            'priority' => 90,
            'label'    => \Yii::t('skeeks/cms', 'Dashboards'),
            "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/dashboard.png'],

            'items' => dashboardsMenu(),
        ],*/

    'tree' => [
        'priority' => 100,
        "label"    => \Yii::t('skeeks/cms', "Sections"),
        "url"      => ["cms/admin-tree"],
        "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/sections.png'],
    ],

    'content' =>
        [
            'priority' => 180,
            'label'    => \Yii::t('skeeks/cms', 'Content'),
            "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.article.png'],

            'items' => array_merge([

                /*[
                    "label" => \Yii::t('skeeks/cms', "Sections"),
                    "url"   => ["cms/admin-tree"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/sections.png'],
                ],*/

                [
                    "label" => \Yii::t('skeeks/cms', "File manager"),
                    "url"   => ["cms/admin-file-manager"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/folder.png'],
                ],

                [
                    "label" => \Yii::t('skeeks/cms', "Files"),
                    "url"   => ["cms/admin-storage-files"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/storage_file.png'],
                ],

                [
                    "label" => \Yii::t('skeeks/cms', "Сохраненные фильтры"),
                    "url"   => ["cms/admin-cms-saved-filter"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/storage_file.png'],
                ],


            ], contentMenu()),
        ],

    'users' =>
        [
            'label'    => \Yii::t('skeeks/cms', 'Users'),
            'priority' => 200,
            "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/users_clients_group.png'],
            "url"      => ["cms/admin-user"],

            /*'items' => [
                [
                    "label"    => \Yii::t('skeeks/cms', "Users"),
                    "url"      => ["cms/admin-user"],
                    "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/user.png'],
                    'priority' => 0,
                ],
            ],*/
        ],



    'settings' => [
        'priority' => 300,
        'label'    => \Yii::t('skeeks/cms', 'Settings'),
        "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/settings-big.png'],

        'items' =>
            [
                'siteinfo' => [
                    //'priority' => 290,
                    'label'    => \Yii::t('skeeks/cms', 'Информация'),
                    "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/information.png'],

                    'items' => [
                        [
                            'label' => 'Общая',
                            'url'   => ['cms/admin-cms-site-info'],
                        ],
                        [
                            'label' => 'Телефоны',
                            'url'   => ['cms/admin-cms-site-phone'],
                        ],
                        [
                            'label' => 'Email-ы',
                            'url'   => ['cms/admin-cms-site-email'],
                        ],
                        [
                            'label' => 'Адреса',
                            'url'   => ['cms/admin-cms-site-address'],
                        ],
                        [
                            'label' => 'Социальные сети',
                            'url'   => ['cms/admin-cms-site-social'],
                        ],
                        [
                            'label' => 'Домены',
                            'url'   => ['cms/admin-cms-site-domain'],
                        ],
                    ],
                ],

                [
                    "label" => \Yii::t('skeeks/cms', 'Юр. Лица'),
                    "url"   => ["/cms/admin-cms-contractor"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/company.png'],

                ],
                [
                    "label" => \Yii::t('skeeks/cms', 'Дизайн'),
                    "url"   => ["/cms/admin-cms-theme"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/themes.png'],

                ],

                [
                    "label" => \Yii::t('skeeks/cms', "Languages"),
                    "url"   => ["cms/admin-cms-lang"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/ru.png'],
                ],


                [
                    "label" => \Yii::t('skeeks/cms', "Components"),
                    "url"   => ["cms/admin-settings"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/settings-big.png'],
                    /*'items' => componentsMenu(),*/
                ],


                [
                    "label" => \Yii::t('skeeks/cms', "Settings sections"),
                    //"url"       => ["cms/admin-cms-tree-type"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.tree.gif'],
                    "items" =>
                        [
                            [
                                "label" => \Yii::t('skeeks/cms', "Properties"),
                                "url"   => ["cms/admin-cms-tree-type-property"],
                                //"img"       => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.tree.gif'],
                            ],

                            [
                                "label" => \Yii::t('skeeks/cms', "Options"),
                                "url"   => ["cms/admin-cms-tree-type-property-enum"],
                                //"img"       => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.tree.gif'],
                            ],

                            [
                                "label" => \Yii::t('skeeks/cms', "Types"),
                                "url"   => ["cms/admin-cms-tree-type"],
                                "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.tree.gif'],
                            ],
                        ],
                ],

                [
                    "label" => \Yii::t('skeeks/cms', "Content settings"),
                    //"url"   => ["cms/admin-cms-content-type"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/content.png'],

                    'items' => [
                        [
                            'url'   => ["cms/admin-cms-content-property"],
                            'label' => \Yii::t('skeeks/cms', "Properties"),
                        ],
                        [
                            'url'   => ["cms/admin-cms-content-property-enum"],
                            'label' => \Yii::t('skeeks/cms', "Options"),
                        ],

                        [
                            'url'   => ["cms/admin-cms-content"],
                            'label' => \Yii::t('skeeks/cms', "Типы контента"),
                        ],

                        [
                            'url'   => ["cms/admin-cms-content-type"],
                            'label' => \Yii::t('skeeks/cms', "Группы контента"),
                            "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.tree.gif'],
                        ],
                    ],
                    //contentEditMenu()
                ],

                [
                    "label" => \Yii::t('skeeks/cms', "SMS"),
                    //"url"   => ["cms/admin-cms-content-type"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/sms_icon-icons.com_67293.svg'],

                    'items' => [
                        [
                            'url'   => ["/cms/admin-cms-sms-provider"],
                            'label' => \Yii::t('skeeks/cms', "SMS провайдеры"),
                        ],
                        [
                            'url'   => ["/cms/admin-cms-sms-message"],
                            'label' => \Yii::t('skeeks/cms', "SMS сообщения"),
                        ],
                    ],
                    //contentEditMenu()
                ],


                [
                    "label" => \Yii::t('skeeks/cms', "User settings"),
                    "url"   => ["cms/admin-cms-user-universal-property"],
                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/user.png'],
                    "items" =>
                        [
                            [
                                "label"    => \Yii::t('skeeks/rbac', 'Roles'),
                                "url"      => ["rbac/admin-role"],
                                "img"      => ['skeeks\cms\rbac\assets\RbacAsset', 'icons/users-role.png'],
                                'enabled'  => true,
                                'priority' => 500,
                            ],

                            [
                                "label"    => \Yii::t('skeeks/rbac', 'Privileges'),
                                "url"      => ["rbac/admin-permission"],
                                "img"      => ['skeeks\cms\rbac\assets\RbacAsset', 'icons/access.png'],
                                'enabled'  => true,
                                'priority' => 500,
                            ],

                            [
                                "label" => \Yii::t('skeeks/cms', "User properties"),
                                "url"   => ["cms/admin-cms-user-universal-property"],
                                "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/settings-big.png'],
                            ],

                            [
                                "label" => \Yii::t('skeeks/cms', "Options"),
                                "url"   => ["cms/admin-cms-user-universal-property-enum"],
                                "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/settings-big.png'],
                            ],

                        ],
                ],


            ],
    ],

    /*'sites' => [
        'priority' => 310,
        'label'    => \Yii::t('skeeks/cms', 'Sites'),
        "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/www.png'],
        "url"      => ["/cms/admin-cms-site"],
    ],*/


    'other' =>
        [
            'priority' => 500,
            'label'    => \Yii::t('skeeks/cms', 'Additionally'),
            "img"      => ['\skeeks\cms\assets\CmsAsset', 'images/icons/other.png'],

            'items' =>
                [

                    [
                        "label" => \Yii::t('skeeks/cms', "Для разработчика"),
                        "url"   => ["cms/admin-info"],
                        "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.infoblock.png'],
                    ],

                    /*[
                        'label'    => \Yii::t('skeeks/cms', 'Instruments'),
                        'priority' => 0,
                        'enabled'  => true,

                        "img" => ['\skeeks\cms\assets\CmsAsset', 'images/icons/tools.png'],

                        'items' =>
                            [
                                [
                                    "label" => \Yii::t('skeeks/cms', "Information"),
                                    "url"   => ["cms/admin-info"],
                                    "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/icon.infoblock.png'],
                                ],
                            ],
                    ],*/

                    [
                        "label" => \Yii::t('skeeks/cms', "Clearing temporary data"),
                        "url"   => ["cms/admin-clear"],
                        "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/clear.png'],
                    ],
                ],
        ],


    'sites' => [
        'priority' => 9000,
        "label" => \Yii::t('skeeks/cms', 'Sites'),
        "url"   => ["/cms/admin-cms-site"],
        "img"   => ['\skeeks\cms\assets\CmsAsset', 'images/icons/www.png'],

    ],
]);
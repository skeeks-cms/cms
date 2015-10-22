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
                        'url'   => ["cms/admin-cms-content-element/index", "content_id" => $content->id, "content_type" => $contentType->code],

                    ];
                }
            }

            $result[] = new \skeeks\cms\modules\admin\helpers\AdminMenuItemCmsConent($itemData);
        }
    }

    return $result;
};

function componentsMenu()
{
    $result = [];

    if (\Yii::$app instanceof \yii\console\Application)
    {
        return $result;
    }

    foreach (\Yii::$app->getComponents(true) as $id => $data)
    {
        $loadedComponent = \Yii::$app->get($id);
        if ($loadedComponent instanceof \skeeks\cms\base\Component)
        {
            $result[] = new \skeeks\cms\modules\admin\helpers\AdminMenuItemCmsConent([
                'label'     => $loadedComponent->descriptor->name,
                'url'   => ["cms/admin-settings", "component" => $loadedComponent->className()],
                /*"activeCallback"       => function(\skeeks\cms\modules\admin\helpers\AdminMenuItem $adminMenuItem)
                {
                    return (bool) (\Yii::$app->request->getUrl() == $adminMenuItem->getUrl());
                },*/
            ]);
        }
    }

    return $result;
}

return
[
    'content' =>
    [
        'priority'  => 0,
        'label'     => \Yii::t('app','Content'),
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.gif'],

        'items' => array_merge([

            [
                "label"     => \Yii::t('app',"Sections"),
                "url"       => ["cms/admin-tree"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.gif']
            ],

            [
                "label"     => \Yii::t('app',"File manager"),
                "url"       => ["cms/admin-file-manager"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/folder.png'],
            ],

            [
                "label"     => \Yii::t('app',"File storage"),
                "url"       => ["cms/admin-storage-files/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/storage_file.png'],
            ],
        ], contentMenu())
    ],

    'settings' =>
    [
        'priority'  => 10,
        'label'     => \Yii::t('app','Settings'),
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings.png'],

        'items' =>
        [

            [
                "label"     => \Yii::t('app',"Product settings"),
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings.png'],

                'items' =>
                [
                    [
                        "label"     => \Yii::t('app','Sites'),
                        "url"       => ["cms/admin-cms-site"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/www.png']
                    ],

                    [
                        "label"     => \Yii::t('app',"Languages"),
                        "url"       => ["cms/admin-cms-lang"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/lang.png']
                    ],

                    [
                        "label"     => \Yii::t('app',"Section markers"),
                        "url"       => ["cms/admin-tree-menu"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.menu.png']
                    ],

                    [
                        "label"     => \Yii::t('app',"Server file storage"),
                        "url"       => ["cms/admin-storage/index"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/servers.png'],
                    ],


                    [
                        "label"     => \Yii::t('app',"Settings sections"),
                        "url"       => ["cms/admin-cms-tree-type"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.gif'],
                    ],

                    [
                        "label"     => \Yii::t('app',"Content settings"),
                        "url"       => ["cms/admin-cms-content-type"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/content.png'],
                    ],

                    [
                        "label"     => \Yii::t('app',"Module settings"),
                        "url"       => ["cms/admin-settings"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings.png'],
                        'items'     => componentsMenu()
                    ],

                    [
                        "label"     => \Yii::t('app',"Agents"),
                        "url"       => ["cms/admin-cms-agent"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/clock.png'],
                    ],
                ],
            ],


            [
                'label'     => \Yii::t('app','Users and Access'),
                'priority'  => 0,
                'enabled'   => true,

                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png'],

                'items' =>
                [
                    [
                        "label"     => \Yii::t('app',"User management"),
                        "url"       => ["cms/admin-user"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png']
                    ],

                    [
                        "label"     => \Yii::t('app',"User properties"),
                        "url"       => ["cms/admin-cms-user-universal-property"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings.png']
                    ],

                    [
                        "label"     => \Yii::t('app','The base of {email} addresses',['email' => 'email']),
                        "url"       => ["cms/admin-user-email"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/email-2.png']
                    ],

                    [
                        "label"     => \Yii::t('app',"Base phones"),
                        "url"       => ["cms/admin-user-phone"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/phone.png']
                    ],

                    [
                        "label"     => \Yii::t('app',"Social profiles"),
                        "url"       => ["cms/admin-user-auth-client"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/facebook.png']
                    ],


                    [
                        "label"     => \Yii::t('app','Roles'),
                        "url"       => ["admin/admin-role"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.users_role.png'],
                        'enabled'   => true,
                        'priority'  => 0,
                    ],

                    [
                        "label"     => \Yii::t('app','Privileges'),
                        "url"       => ["admin/admin-permission"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/access.png'],
                        'enabled'   => true,
                        'priority'  => 0,
                    ],
                ],
            ],


            [

                "label"     => \Yii::t('app',"Searching"),
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/search.png'],

                'items' =>
                [
                    [
                        "label" => \Yii::t('app',"Settings"),
                        "url"   => ["cms/admin-settings", "component" => 'skeeks\cms\components\CmsSearchComponent'],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings.png'],
                        "activeCallback"       => function(\skeeks\cms\modules\admin\helpers\AdminMenuItem $adminMenuItem)
                        {
                            return (bool) (\Yii::$app->request->getUrl() == $adminMenuItem->getUrl());
                        },
                    ],

                    [
                        "label"     => \Yii::t('app',"Statistic"),
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/statistics.png'],

                        'items' =>
                        [
                            [
                                "label" => \Yii::t('app',"Jump list"),
                                "url"   => ["cms/admin-search-phrase"],
                            ],

                            [
                                "label" => \Yii::t('app',"Phrase list"),
                                "url"   => ["cms/admin-search-phrase-group"],
                            ],
                        ],
                    ],
                ],
            ],



            [
                'label'     => \Yii::t('app','Instruments'),
                'priority'  => 0,
                'enabled'   => true,

                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/tools.png'],

                'items' =>
                [
                    [
                        "label"     => \Yii::t('app',"Checking system"),
                        "url"       => ["admin/checker"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/tools.png'],
                    ],

                    [
                        "label"     => \Yii::t('app',"Information"),
                        "url"       => ["admin/info"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.infoblock.png'],
                    ],

                    [
                        "label"     => \Yii::t('app',"Sending {email}",['email' => 'email']),
                        "url"       => ["admin/email"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/email.png'],
                    ],

                    [
                        "label"     => \Yii::t('app',"Clearing temporary data"),
                        "url"       => ["admin/clear"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/clear.png'],
                    ],

                    [
                        "label"     => \Yii::t('app',"Job to database"),
                        "url"       => ["admin/db"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.bd_arch.png'],
                    ],

                    /*[
                        "label"     => "Обновления",
                        "url"       => ["admin/update"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/update.png'],
                    ],*/

                    [
                        "label"     => \Yii::t('app',"{ssh} console",['ssh' => 'Ssh']),
                        "url"       => ["admin/ssh"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/ssh.png'],
                    ],
                    [
                        "label"         => \Yii::t('app','Code generator'). " gii",
                        "url"           => ["admin/gii"],
                        "img"           => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/ssh.png'],
                        "accessCallback"=> function()
                        {
                            if ((bool) \Yii::$app->hasModule('gii'))
                            {
                                /**
                                 * @var $gii yii\gii\Module
                                 */
                                $gii = \Yii::$app->getModule('gii');

                                $ip = \Yii::$app->getRequest()->getUserIP();
                                foreach ($gii->allowedIPs as $filter) {
                                    if ($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos))) {
                                        return true;
                                    }
                                }
                            }

                            return false;
                        },
                    ],
                ]
            ],
        ]
    ],


    'marketplace' =>
    [
        'priority'  => 20,
        'label'     => \Yii::t('app','Marketplace'),
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/marketplace.png'],

        'items' =>
        [

            [
                "label"     => \Yii::t('app',"Catalog"),
                "url"       => ["cms/admin-marketplace/catalog"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/marketplace.png']
            ],

            [
                "label"     => \Yii::t('app',"Installed"),
                "url"       => ["cms/admin-marketplace/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/installed.png']
            ],

            [
                "label"     => \Yii::t('app',"Install{s}Remove",['s' => '/']),
                "url"       => ["cms/admin-marketplace/install"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/installer.png']
            ],

            [
                "label"     => \Yii::t('app',"Updated platforms"),
                "url"       => ["cms/admin-marketplace/update"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/update-2.png']
            ],
        ]
    ],


    'other' =>
    [
        'priority'  => 100,
        'label'     => \Yii::t('app','Additionally'),
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/other.png'],

        'items' =>
        [
            [
                "label"     => \Yii::t('app',"Clearing temporary data"),
                "url"       => ["admin/clear"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/clear.png'],
            ],
        ]
    ]
];
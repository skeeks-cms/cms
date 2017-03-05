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

    if ($contentTypes = \skeeks\cms\models\CmsContentType::find()->orderBy("priority ASC")->all())
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

            $contents = $contentType->getCmsContents()->andWhere(['visible' => 'Y'])->all();

            if ($contents)
            {
                foreach ($contents as $content)
                {
                    $itemData['items'][] =
                    [
                        'label' => $content->name,
                        'url'   => ["cms/admin-cms-content-element", "content_id" => $content->id],
                    ];
                }
            }

            if (isset($itemData['items']))
            {
                $result[] = new \skeeks\cms\modules\admin\helpers\AdminMenuItemCmsConent($itemData);
            }
        }
    }

    return $result;
};


/**
 * Меню контента
 * @return array
 */
function dashboardsMenu()
{
    $result = [];

    if ($dashboards = \skeeks\cms\models\CmsDashboard::find()->orderBy("priority ASC")->all())
    {
        /**
         * @var $dashboard \skeeks\cms\models\CmsDashboard
         */
        foreach ($dashboards as $dashboard)
        {
            $itemData = [
                'label'     => $dashboard->name,
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/dashboard.png'],
                'url'       => ["admin/index/dashboard", "pk" => $dashboard->id],
                "activeCallback"       => function(\skeeks\cms\modules\admin\helpers\AdminMenuItem $adminMenuItem)
                {
                    return (bool) (\Yii::$app->controller->action->uniqueId == 'admin/index/dashboard' && \yii\helpers\ArrayHelper::getValue($adminMenuItem->url, 'pk') == \Yii::$app->request->get('pk'));
                },
            ];

            $result[] = $itemData;
        }
    } else
    {
        $result[] = [
            "label"     => \Yii::t('skeeks/cms', "Рабочий стол 1"),
            "url"       => ["admin/index"],
            "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/dashboard.png']
        ];
    }

    return $result;
};

/**
 * Меню контента
 * @return array
 */
function contentEditMenu()
{
    $result = [];

    if ($contentTypes = \skeeks\cms\models\CmsContentType::find()->orderBy("priority ASC")->all())
    {
        /**
         * @var $contentType \skeeks\cms\models\CmsContentType
         */
        foreach ($contentTypes as $contentType)
        {
            $itemData = [
                'code'      => "content-block-edit-" . $contentType->id,
                'url'       => ["/cms/admin-cms-content-type/update", "pk" => $contentType->id],
                'label'     => $contentType->name,
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.article.png'],
                "activeCallback"       => function(\skeeks\cms\modules\admin\helpers\AdminMenuItem $adminMenuItem)
                {
                    return (bool) (\Yii::$app->controller->action->uniqueId == 'cms/admin-cms-content-type/update' && \yii\helpers\ArrayHelper::getValue($adminMenuItem->url, 'pk') == \Yii::$app->request->get('pk'));
                },
            ];

            if ($contents = $contentType->cmsContents)
            {
                foreach ($contents as $content)
                {
                    $itemData['items'][] =
                    [
                        'label' => $content->name,
                        'url'   => ["cms/admin-cms-content/update", "pk" => $content->id],
                        "activeCallback"       => function(\skeeks\cms\modules\admin\helpers\AdminMenuItem $adminMenuItem)
                        {
                            return (bool) (\Yii::$app->controller->action->uniqueId == 'cms/admin-cms-content/update' && \yii\helpers\ArrayHelper::getValue($adminMenuItem->url, 'pk') == \Yii::$app->request->get('pk'));
                        },
                    ];
                }
            }


            $result[] = $itemData;
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
        try
        {
            $loadedComponent = \Yii::$app->get($id);
            if ($loadedComponent instanceof \skeeks\cms\base\Component)
            {
                $result[] = new \skeeks\cms\modules\admin\helpers\AdminMenuItem([
                    'label'     => $loadedComponent->descriptor->name,
                    'url'   => ["cms/admin-settings", "component" => $loadedComponent->className()],
                    "activeCallback"       => function(\skeeks\cms\modules\admin\helpers\AdminMenuItem $adminMenuItem)
                    {
                        return (bool) (\Yii::$app->request->getUrl() == $adminMenuItem->getUrl());
                    },
                ]);
            }
        } catch (\Exception $e)
        {

        }

    }

    return $result;
}

return
[
    'dashboard' =>
    [
        'priority'  => 90,
        'label'     => \Yii::t('skeeks/cms','Dashboards'),
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/dashboard.png'],

        'items' => dashboardsMenu()
    ],

    'content' =>
    [
        'priority'  => 200,
        'label'     => \Yii::t('skeeks/cms','Content'),
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/sections.png'],

        'items' => array_merge([

            [
                "label"     => \Yii::t('skeeks/cms',"Sections"),
                "url"       => ["cms/admin-tree"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/sections.png']
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"File manager"),
                "url"       => ["cms/admin-file-manager"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/folder.png'],
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"File storage"),
                "url"       => ["cms/admin-storage-files"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/storage_file.png'],
            ],


        ], contentMenu())
    ],

    'users' =>
    [
        'label'     => \Yii::t('skeeks/cms', 'Users'),
        'priority'  => 200,
        'enabled'   => true,
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png'],

        'items' =>
        [
            [
                "label"     => \Yii::t('skeeks/cms',"User management"),
                "url"       => ["cms/admin-user"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png'],
                'priority'  => 0
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"User properties"),
                "url"       => ["cms/admin-cms-user-universal-property"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings-big.png']
            ],

            [
                "label"     => \Yii::t('skeeks/cms','The base of {email} addresses',['email' => 'email']),
                "url"       => ["cms/admin-user-email"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/email-2.png']
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"Base phones"),
                "url"       => ["cms/admin-user-phone"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/phone.png']
            ],
        ]
    ],


    'settings' =>
    [
        'priority'  => 300,
        'label'     => \Yii::t('skeeks/cms','Settings'),
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings-big.png'],

        'items' =>
        [
            [
                "label"     => \Yii::t('skeeks/cms','Sites'),
                "url"       => ["/cms/admin-cms-site"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/www.png']
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"Languages"),
                "url"       => ["cms/admin-cms-lang"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/ru.png']
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"Server file storage"),
                "url"       => ["cms/admin-storage/index"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/servers.png'],
            ],


            [
                "label"     => \Yii::t('skeeks/cms',"Settings sections"),
                "url"       => ["cms/admin-cms-tree-type"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.tree.gif'],
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"Content settings"),
                "url"       => ["cms/admin-cms-content-type"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/content.png'],

                'items'     => contentEditMenu()
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"Module settings"),
                //"url"       => ["cms/admin-settings"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/settings-big.png'],
                'items'     => componentsMenu()
            ],
        ]
    ],


    'other' =>
    [
        'priority'  => 500,
        'label'     => \Yii::t('skeeks/cms','Additionally'),
        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/other.png'],

        'items' =>
        [

            [
                'label'     => \Yii::t('skeeks/cms','Instruments'),
                'priority'  => 0,
                'enabled'   => true,

                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/tools.png'],

                'items' =>
                [
                    /*[
                        "label"     => \Yii::t('skeeks/cms',"Checking system"),
                        "url"       => ["admin/checker"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/tools.png'],
                    ],*/

                    [
                        "label"     => \Yii::t('skeeks/cms',"Information"),
                        "url"       => ["admin/info"],
                        "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/icon.infoblock.png'],
                    ],

/**
 * TODO:: make a separate module

                    [
                        "label"         => \Yii::t('skeeks/cms','Code generator'). " gii",
                        "url"           => ["admin/gii"],
                        "img"           => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/ssh.png'],
                        "accessCallback"=> function()
                        {
                            if ((bool) \Yii::$app->hasModule('gii'))
                            {
                                /**
                                 * @var $gii yii\gii\Module
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
                    ],*/
                ]
            ],

            [
                "label"     => \Yii::t('skeeks/cms',"Clearing temporary data"),
                "url"       => ["admin/clear"],
                "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/clear.png'],
            ],
        ]
    ]
];
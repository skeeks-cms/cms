<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 06.11.2017
 */
return [

    'bootstrap' => ['skeeks'],
    'vendorPath' => '@vendor',

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],

    'components' => [
        'userSessions' => ['class' => \skeeks\cms\components\UserSessions::class],
        'jobRegistry' => [
            'types' => [
                'cms.flush-cache' => [
                    'type' => 'cms.flush-cache',
                    'title' => 'Очистка кэша',
                    'handler' => \skeeks\cms\jobs\FlushCacheJobHandler::class,
                    'queue' => 'maintenance',
                    'timeout' => 7200,
                    'leaseSeconds' => 120,
                    'maxAttempts' => 1,
                    'idempotent' => false,
                    'overlapPolicy' => 'skip',
                    'permission' => \skeeks\cms\rbac\CmsManager::PERMISSION_ROLE_ADMIN_ACCESS,
                    'resourceKey' => static function () { return 'cms:cache'; },
                    'dedupKey' => static function () { return 'cms:flush-cache'; },
                ],
                'cms.cleanup-temporary-files' => [
                    'type' => 'cms.cleanup-temporary-files',
                    'title' => 'Чистка временных файлов',
                    'handler' => \skeeks\cms\jobs\CleanupTemporaryFilesJobHandler::class,
                    'queue' => 'maintenance',
                    'timeout' => 7200,
                    'leaseSeconds' => 120,
                    'maxAttempts' => 1,
                    'idempotent' => false,
                    'overlapPolicy' => 'skip',
                    'permission' => \skeeks\cms\rbac\CmsManager::PERMISSION_ROLE_ADMIN_ACCESS,
                    'resourceKey' => static function () { return 'cms:temporary-files'; },
                    'dedupKey' => static function () { return 'cms:cleanup-temporary-files'; },
                ],
                'cms.notify-cleanup' => [
                    'type' => 'cms.notify-cleanup',
                    'title' => 'Чистка прочитанных уведомлений',
                    'handler' => \skeeks\cms\jobs\NotifyCleanupJobHandler::class,
                    'queue' => 'maintenance',
                    'timeout' => 600,
                    'leaseSeconds' => 120,
                    'maxAttempts' => 1,
                    'idempotent' => true,
                    'overlapPolicy' => 'skip',
                    'permission' => \skeeks\cms\rbac\CmsManager::PERMISSION_ROLE_ADMIN_ACCESS,
                    'resourceKey' => static function () { return 'cms:notify-cleanup'; },
                    'dedupKey' => static function () { return 'cms:notify-cleanup'; },
                ],
            ],
        ],

        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],

        'cms' => [
            'class' => '\skeeks\cms\components\Cms',
        ],

        'storage' => [
            'class' => 'skeeks\cms\components\Storage',
            'components' => [
                'local' => [
                    'class' => 'skeeks\cms\components\storage\ClusterLocal',
                    'priority' => 100,
                ],
            ],
        ],

        'skeeks' => [
            'class' => '\skeeks\cms\Skeeks',
        ],

        //"Сделка как услуга": access of client people to deals, access areas and deal resources.
        'dealAccess' => [
            'class' => \skeeks\cms\deal\DealAccess::class,
        ],

        //Employee rights in the CRM (who sorts, works on, assigns a deal; what of a client is visible).
        'crmAccess' => [
            'class' => \skeeks\cms\crm\CrmAccess::class,
        ],

        //The one duplicate check of companies, people and deals (publicMailDomains, legalForms).
        'crmMatcher' => [
            'class' => \skeeks\cms\crm\CrmMatcher::class,
        ],

        //Settings of the site CRM: «Повторы клиентов» (strict / free).
        'crmSettings' => [
            'class' => \skeeks\cms\crm\CrmSettings::class,
        ],

        //Notifications of employees and clients: kinds of events, categories, channels, personal settings.
        'notifications' => [
            'class' => \skeeks\cms\notify\Notifications::class,
        ],

        'legal' => [
            'class' => \skeeks\cms\components\LegalComponent::class,
        ],

        'imaging' => [
            'class' => '\skeeks\cms\components\Imaging',
        ],

        'console' => [
            'class' => 'skeeks\cms\components\ConsoleComponent',
        ],

        'i18n' => [
            'translations' => [
                'skeeks/cms' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@skeeks/cms/messages',
                    'fileMap' => [
                        'skeeks/cms' => 'main.php',
                    ],
                ],

                'skeeks/cms/user' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@skeeks/cms/messages',
                    'fileMap' => [
                        'skeeks/cms/user' => 'user.php',
                    ],
                ],
            ],
        ],

        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'suffix' => '',
            'normalizer' => [
                'class' => 'yii\web\UrlNormalizer',
                'collapseSlashes' => true,
                'normalizeTrailingSlash' => true,
                'action' => \yii\web\UrlNormalizer::ACTION_REDIRECT_PERMANENT,
            ],
            /*'rules' => [
                'u' => 'cms/user/index',
                'u/<username>' => 'cms/user/view',
                'u/<username>/<action>' => 'cms/user/<action>',

                '~<_a:(login|logout|register|forget|reset-password|approve-email)>' => 'cms/auth/<_a>',

                'skeeks-cms' => 'cms/cms/index',
                'skeeks-cms/<action>' => 'cms/cms/<action>',

                'favicon.<extension:\w+>' => '/cms/favicon/on-request',

                "image-preview" => ["class" => 'skeeks\cms\components\urlRules\UrlRuleImagePreview'],
                "storage-file" => ["class" => 'skeeks\cms\components\urlRules\UrlRuleStorageFile'],

                [
                    'class' => \skeeks\cms\components\urlRules\UrlRuleSavedFilter::class
                ],
            ],*/
        ],

        'cmsAgent' => [
            'commands' => [

                'cms/cache/flush-all' => [
                    'jobType' => 'cms.flush-cache',
                    'class' => \skeeks\cms\agent\CmsAgent::class,
                    'name' => ['skeeks/cms', 'Clearing the cache'],
                    'interval' => 3600 * 24,
                ],

                'ajaxfileupload/cleanup' => [
                    'jobType' => 'cms.cleanup-temporary-files',
                    'class' => \skeeks\cms\agent\CmsAgent::class,
                    'name' => ['skeeks/cms', 'Cleaning temporarily downloaded files'],
                    'interval' => 3600 * 24,
                ],

                // П2д: the protection of clients whose payment or service expired.
                'cms/crm/protection' => [
                    'jobType' => 'cms.crm-protection',
                    'class' => \skeeks\cms\agent\CmsAgent::class,
                    'name' => 'Защита клиентов: снять истёкшую',
                    'interval' => 3600 * 24,
                ],

                'cms/notify/cleanup' => [
                    'jobType' => 'cms.notify-cleanup',
                    'class' => \skeeks\cms\agent\CmsAgent::class,
                    'name' => ['skeeks/cms', 'Чистка прочитанных уведомлений'],
                    'interval' => 3600 * 24,
                ],

                /*'cms/utils/update-content-element-codes' => [
                    'class' => \skeeks\cms\agent\CmsAgent::class,
                    'name' => ['skeeks/cms', 'Обновляет url для элементов'],
                    'interval' => 3600 * 24 * 7,
                ],*/

            ],
        ],

        'authManager' => [
            'config' => require __DIR__ . '/_permissions.php'
        ],
    ],

    'modules' => [

        'cms' => [
            'class' => '\skeeks\cms\Module',
        ],

        'ajaxfileupload' => [
            'class' => '\skeeks\yii2\ajaxfileupload\AjaxFileUploadModule',
        ],
    ],
];

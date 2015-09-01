
<?php
/**
 * Самый базовый конфиг приложения на базе skeeks cms
 * По умолчанию конфигурирование всех базовых используемых компонентов и админки
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

$config =
[
    'id'            => 'skeeks-cms-app',
    "name"          => "SkeekS CMS",
    'vendorPath'    => VENDOR_DIR,

    'components' => [

        'db' => [
            'class' => 'yii\db\Connection',
            //'dsn' => 'mysql:host=mysql.skeeks.com;dbname=s2_vz1005_demo-cms',
            //'username' => 's2_vz1016',
            //'password' => 'dryagtepEjsiocakVenAvyeyb',
            'charset' => 'utf8',
            'enableSchemaCache' => false,
        ],

        'storage' => [
            'class' => 'skeeks\cms\components\Storage',
            'components' =>
            [
                'local' =>
                [
                    'class'                 => 'skeeks\cms\components\storage\ClusterLocal',

                    "name"                  => "Локальное хранилище",
                    "publicBaseUrl"         => "/uploads/all",
                    "rootBasePath"          =>  Yii::getAlias("@frontend/web/uploads/all"),
                ]
            ],
        ],

        'currentSite' => ['class' => 'skeeks\cms\components\CurrentSite'],

        'registeredModels' =>
        [
            'class' => 'skeeks\cms\components\RegisteredModels',
            //Модели к которым можно крепить другие, то есть эти модели имеют ссылку на себя объект Ref
            'components' => include_once __DIR__ . '/models.php'
        ],

        'cms' =>
        [
            'class'                         => '\skeeks\cms\components\Cms',
        ],

        'imaging' =>
        [
            'class' => '\skeeks\cms\components\Imaging',
        ],

        'authManager' => [
            'class' => '\skeeks\cms\rbac\DbManager',
            //'defaultRoles' => ['user'],
        ],

        //Админское меню
        'adminMenu' =>
        [
            'class' => '\skeeks\cms\modules\admin\components\Menu',
        ],

        'dbDump' =>
        [
            'class' => '\skeeks\cms\components\db\DbDumpComponent',
        ],

        'cmsSearch' =>
        [
            'class' => '\skeeks\cms\components\CmsSearchComponent',
        ],
    ],


    'modules' => [

        'cms' =>
        [
            'class' => 'skeeks\cms\ConsoleModule',
        ],
    ],
];

return $config;
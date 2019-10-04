<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 10.11.2017
 */
$config =
    [
        'id' => 'app-skeeks-console',

        'modules' => [

            'cms' => [
                'controllerNamespace' => 'skeeks\cms\console\controllers'
            ],

            'ajaxfileupload' => [
                'controllerNamespace' => 'skeeks\yii2\ajaxfileupload\console\controllers',
                'private_tmp_dir' => '@frontend/runtime/ajaxfileupload'
            ]
        ],

        'components' => [

            'urlManager' => [
                'baseUrl' => '',
                //'hostInfo' => 'https://demo.ru'
            ]
        ],

        'controllerMap' => [
            'migrate' => [
                'class'         => 'yii\console\controllers\MigrateController',
                'migrationPath' => [
                    '@app/migrations',
                    '@skeeks/cms/migrations',
                ],
            ],
        ]
    ];

return $config;
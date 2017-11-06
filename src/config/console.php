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
    'modules' => [

        'cms' =>
        [
            'controllerNamespace' => 'skeeks\cms\console\controllers'
        ],
        
        'ajaxfileupload' => [
            'controllerNamespace'   => 'skeeks\yii2\ajaxfileupload\console\controllers',
            'private_tmp_dir'       => '@frontend/runtime/ajaxfileupload'
        ]
    ],
];

return $config;
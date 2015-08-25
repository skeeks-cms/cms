<?php
/**
 * базовые глобальные опции
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */
return
[
    'tree' =>
    [
        'modelClass'                    => 'skeeks\cms\models\Tree',
        'name'                          => 'Страница',
        'adminControllerRoute'          => 'cms/admin-tree',
    ],

    'user' =>
    [
        'modelClass'             => 'skeeks\cms\models\User',
        'name'                  => 'Пользователь',
    ],

    'userGroup' =>
    [
        'modelClass'             => 'skeeks\cms\models\UserGroup',
        'name'             => 'Группа пользователя',
    ],

    'storageFile'   =>
    [
        'modelClass' => 'skeeks\cms\models\StorageFile',
        'name' => 'Файл хранилища'
    ],

    'cmsContentElement'   =>
    [
        'modelClass'    => 'skeeks\cms\models\CmsContentElement',
        'name'          => 'Элемент контента',
        'adminControllerRoute'          => 'cms/admin-cms-content-element',
    ],


    'f-crop' =>
    [
        'modelClass'    => 'skeeks\cms\components\imaging\filters\Crop',
        'name'          => 'Фильтр обрезать'
    ],

    'f-thumbnail' =>
    [
        'modelClass'    => 'skeeks\cms\components\imaging\filters\Thumbnail',
        'name'          => 'Фильтр Thumbnail'
    ],
];
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.04.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\rbac\CmsManager;

class ElfinderFullController extends ElfinderController
{
    public function init()
    {
        $this->roots = [];

        if (\Yii::$app->user->can(CmsManager::PERMISSION_ELFINDER_USER_FILES))
        {
            $this->roots[] =
            [
                'class' => 'skeeks\cms\helpers\elfinder\UserPath',
                'path'  => 'uploads/users/{id}',
                'name'  => 'Личные файлы'
            ];
        }

        if (\Yii::$app->user->can(CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES))
        {
            $this->roots[] =
            [
                'path'  => 'uploads/inbox',
                'name'  => 'Общие файлы'
            ];
        }


        if (\Yii::$app->user->can(CmsManager::PERMISSION_ELFINDER_ADDITIONAL_FILES))
        {
            $this->roots[] =
            [
                'baseUrl'   =>'@web',
                'basePath'  =>'@webroot',
                'path'      => '/',
                'name'      => 'Корень (robots.txt тут)'
            ];

            $this->roots[] =
            [
                'class' => 'mihaildev\elfinder\UserPath',
                'path'  => 'uploads/users/{id}',
                'name'  => 'Личные файлы'
            ];

            $this->roots[] =
            [
                'basePath'  => '@frontend',
                'path'      => 'views',
                'name'      => 'frontend/views'
            ];

            $this->roots[] =
            [
                'basePath'  => '@frontend',
                'path'      => 'runtime',
                'name'      => 'frontend/runtime'
            ];

            $this->roots[] =
            [
                'basePath'  => '@console',
                'path'      => 'runtime',
                'name'      => 'console/runtime'
            ];

            $this->roots[] =
            [
                'basePath'  =>'@webroot',
                'path'      => 'assets',
                'name'      => 'Временные js и css'
            ];

            $this->roots[] =
            [
                'basePath'  => BACKUP_DIR,
                'name'      => 'Бэкапы'
            ];
        }

        parent::init();

        \Yii::$app->cmsToolbar->enabled = false;
    }
}
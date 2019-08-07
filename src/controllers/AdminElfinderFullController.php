<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.04.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\rbac\CmsManager;

class AdminElfinderFullController extends ElfinderController
{
    public function init()
    {
        $this->roots = [];

        if (\Yii::$app->user->can(CmsManager::PERMISSION_ELFINDER_USER_FILES)) {
            $this->roots[] =
                [
                    'class' => 'skeeks\cms\helpers\elfinder\UserPath',
                    'path' => 'uploads/users/{id}',
                    'name' => 'Личные файлы'
                ];
        }

        if (\Yii::$app->user->can(CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES)) {
            $this->roots[] =
                [
                    'path' => 'uploads/inbox',
                    'name' => 'Общие файлы'
                ];
        }


        if (\Yii::$app->user->can(CmsManager::PERMISSION_ELFINDER_ADDITIONAL_FILES)) {
            $this->roots[] =
                [
                    'basePath' => ROOT_DIR,
                    'path' => '/',
                    'name' => 'ROOT_DIR'
                ];

            $this->roots[] =
                [
                    'baseUrl' => '@web',
                    'basePath' => '@webroot',
                    'path' => '/',
                    'name' => 'Корень (robots.txt тут)'
                ];
        }

        parent::init();

        \Yii::$app->cmsToolbar->enabled = false;
    }
}
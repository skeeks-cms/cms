<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.04.2015
 */
namespace skeeks\cms\controllers;

class ElfinderUserFilesController extends ElfinderController
{
    public function init()
    {
        $this->roots =
        [
            [
                'class' => 'skeeks\cms\helpers\elfinder\UserPath',
                'path'  => 'uploads/users/{id}',
                'name'  => 'Личные файлы'
            ],
        ];

        parent::init();
    }
}
<?php
/**
 * Admin
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\modules\admin\actions\AdminAction;

/**
 * Class IndexController
 * @package skeeks\cms\modules\admin\controllers
 */
class IndexController extends AdminController
{
    public function init()
    {
        $this->name = "Рабочий стол";

        parent::init();
    }

    public function actions()
    {
        return
        [
            'index' =>
            [
                'class'         => AdminAction::className(),
                'name'          => 'Главная страница',
                "visible"       => false,
            ]
        ];
    }
}
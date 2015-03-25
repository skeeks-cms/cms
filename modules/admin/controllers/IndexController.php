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

/**
 * Class IndexController
 * @package skeeks\cms\modules\admin\controllers
 */
class IndexController extends AdminController
{
    public function init()
    {
        $this->_label = "Админка";

        parent::init();
    }

    public function actionIndex()
    {
        return $this->render("index");
    }
}
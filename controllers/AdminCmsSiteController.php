<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsSite;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminCmsSiteController
 * @package skeeks\cms\controllers
 */
class AdminCmsSiteController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление сайтами";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsSite::className();

        parent::init();
    }


}

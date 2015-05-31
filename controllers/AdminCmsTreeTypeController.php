<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsTreeType;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminCmsTreeTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsTreeTypeController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Настройки разделов";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsTreeType::className();

        parent::init();

    }

}

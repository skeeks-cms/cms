<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsTreeTypeProperty;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminCmsTreeTypePropertyController
 * @package skeeks\cms\controllers
 */
class AdminCmsTreeTypePropertyController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление свойствами раздела";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsTreeTypeProperty::className();

        parent::init();

    }

}

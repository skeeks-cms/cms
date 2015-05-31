<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsTreeTypePropertyEnum;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminCmsTreeTypePropertyEnumController
 * @package skeeks\cms\controllers
 */
class AdminCmsTreeTypePropertyEnumController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление значениями свойств раздела";
        $this->modelShowAttribute      = "value";
        $this->modelClassName          = CmsTreeTypePropertyEnum::className();

        parent::init();

    }

}

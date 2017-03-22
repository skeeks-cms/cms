<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsTreeTypePropertyEnum;
use skeeks\cms\models\CmsUserUniversalProperty;
use skeeks\cms\models\CmsUserUniversalPropertyEnum;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminCmsUserUniversalPropertyEnumController
 * @package skeeks\cms\controllers
 */
class AdminCmsUserUniversalPropertyEnumController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление значениями свойств пользователя";
        $this->modelShowAttribute      = "value";
        $this->modelClassName          = CmsUserUniversalPropertyEnum::className();

        parent::init();

    }

}

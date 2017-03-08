<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;

/**
 * Class AdminCmsContentPropertyEnumController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentPropertyEnumController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление значениями свойств";
        $this->modelShowAttribute      = "value";
        $this->modelClassName          = CmsContentPropertyEnum::className();

        parent::init();

    }

}

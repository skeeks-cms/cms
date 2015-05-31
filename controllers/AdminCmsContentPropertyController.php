<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;

/**
 * Class AdminCmsContentPropertyController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentPropertyController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление свойствами";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsContentProperty::className();

        parent::init();
    }

}

<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentType;
use skeeks\cms\models\CmsTreeType;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;

/**
 * Class AdminCmsTreeTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsTreeTypeController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->name                   = "Настройки разделов";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsTreeType::className();

        parent::init();

    }

}

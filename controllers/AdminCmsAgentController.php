<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsContent;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminCmsContentController
 * @package skeeks\cms\controllers
 */
class AdminCmsAgentController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление агентами";
        $this->modelShowAttribute      = "id";
        $this->modelClassName          = CmsAgent::className();

        parent::init();
    }

}

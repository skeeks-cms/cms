<?php
/**
 * AdminPublicationController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 05.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\Comment;
use skeeks\cms\models\Publication;
use skeeks\cms\models\searchs\Publication as PublicationSearch;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminPublicationController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление публикациями";
        $this->_modelShowAttribute      = "name";
        $this->_modelClassName          = Publication::className();

        $this->modelValidate = true;
        $this->enableScenarios = true;
        parent::init();

    }

}

<?php
/**
 * AdminCommentController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 05.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\Comment;
use skeeks\cms\models\Vote;
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
class AdminVoteController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление голосами";

        $this->_modelShowAttribute      = "id";

        $this->_modelClassName          = Vote::className();

        parent::init();

    }

}

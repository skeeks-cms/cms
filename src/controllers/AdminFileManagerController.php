<?php
/**
 * AdminFileManagerController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.02.2015
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Comment;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;

/**
 * Class AdminFileManagerController
 * @package skeeks\cms\controllers
 */
class AdminFileManagerController extends AdminController
{
    public function init()
    {
        if (!$this->name) {
            $this->name = "Файловый менеджер";
        }

        parent::init();
    }

    public function actionIndex()
    {
        return $this->render($this->action->id);
    }
}

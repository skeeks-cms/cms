<?php
/**
 * AdminStorageFilesController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 25.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\Comment;
use skeeks\cms\models\Publication;
use skeeks\cms\models\searchs\Publication as PublicationSearch;
use skeeks\cms\models\StorageFile;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;

/**
 * Class AdminStorageFilesController
 * @package skeeks\cms\controllers
 */
class AdminStorageFilesController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление файлами хранилища";
        $this->_modelShowAttribute      = "src";
        $this->_modelClassName          = StorageFile::className();

        parent::init();
    }

}

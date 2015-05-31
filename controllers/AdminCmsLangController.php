<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsLang;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;

/**
 * Class AdminCmsLangController
 * @package skeeks\cms\controllers
 */
class AdminCmsLangController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление языками";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsLang::className();

        parent::init();
    }
}

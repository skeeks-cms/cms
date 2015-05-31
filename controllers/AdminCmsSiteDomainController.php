<?php
/**
 * AdminSiteController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 16.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsSiteDomain;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminCmsSiteController
 * @package skeeks\cms\controllers
 */
class AdminCmsSiteDomainController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление доменами";
        $this->modelShowAttribute      = "domain";
        $this->modelClassName          = CmsSiteDomain::className();

        parent::init();
    }


}

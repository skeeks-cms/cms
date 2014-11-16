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

use skeeks\cms\App;
use skeeks\cms\models\Infoblock;
use skeeks\cms\models\Search;
use skeeks\cms\models\Site;
use skeeks\cms\models\StaticBlock;
use skeeks\cms\models\UserGroup;
use skeeks\cms\models\WidgetConfig;
use skeeks\cms\models\WidgetSettings;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\widgets\text\Text;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\helpers\ArrayHelper;

/**
 *
 * @method Infoblock getCurrentModel()
 *
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminSiteController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление сайтами";
        $this->_modelShowAttribute      = "host_name";
        $this->_modelClassName          = Site::className();
        parent::init();
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            self::BEHAVIOR_ACTION_MANAGER =>
            [
                "actions" =>
                [

                ]
            ]
        ]);
    }

}

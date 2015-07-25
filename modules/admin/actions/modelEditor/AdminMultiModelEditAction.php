<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions\modelEditor;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\authclient\AuthAction;
use yii\behaviors\BlameableBehavior;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * @property AdminModelEditorController    $controller
 *
 * Class AdminModelsGridAction
 * @package skeeks\cms\modules\admin\actions
 */
class AdminMultiModelEditAction extends AdminModelEditorAction
{
    public function init()
    {
        parent::init();

        $this->method   = 'post';
        $this->request  = 'ajax';
    }

    public function run()
    {
        return "1";
    }
}
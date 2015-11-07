<?php
/**
 * AdminTreeController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\App;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\modules\admin\widgets\DropdownControllerActions;
use skeeks\cms\validators\db\IsSame;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use skeeks\sx\validators\ChainAnd;
use skeeks\sx\validators\ChainOr;
use skeeks\sx\validators\is\IsArray;
use skeeks\sx\validators\is\IsString;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Cookie;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminToolsController extends AdminController
{
    public function init()
    {
        $this->name                   = "Управление шаблоном";
        parent::init();
    }

    public function actionViewFileEdit()
    {
        $rootViewFile = \Yii::$app->request->get('root-file');



        return $this->render($this->action->id, [
            'rootViewFile' => $rootViewFile
        ]);
    }
}

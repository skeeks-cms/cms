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
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\modules\admin\widgets\DropdownControllerActions;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminTreeController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Дерево страниц";
        $this->_modelShowAttribute      = "name";
        $this->_modelClassName          = Tree::className();

        parent::init();
    }

    public function actionIndex()
    {
        /*$parent = Tree::find()->where(["id" => 1])->one();
        $target = Tree::find()->where(["id" => 4])->one();

        $parent->processAddNode($target);
        die;*/

        $tree = new Tree();
        $models = $tree->findRoots()->all();

        $ul = Html::ul($models, [
            "item" => function($model)
            {
                $controller = App::moduleCms()->createControllerByID("admin-tree");
                $controller->setModel($model);

                return Html::tag("li",
                    Html::a($model->name, UrlHelper::construct("cms/admin-tree/index")->set("pid", $model->id)) .
                    DropdownControllerActions::begin([
                        "controller"    => $controller,
                    ])->run()
                );
            }
        ]);

        return $this->output($ul);
    }
}

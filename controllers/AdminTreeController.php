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
                    'new-children' =>
                    [
                        "label" => "Создать подраздел",
                        "rules" =>
                        [
                            [
                                "class" => HasModel::className()
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }

    public function actionNewChildren()
    {
        /**
         * @var Tree $parent
         */
        $parent = $this->getCurrentModel();

        if (\Yii::$app->request->isPost)
        {
            $childTree = new Tree();
            $childTree->load(\Yii::$app->request->post());

            $parent->processAddNode($childTree);

            $this->redirect(Url::to(["view", "id" => $childTree->primaryKey]));
        }
        else
        {
            return $this->render('_form', [
                'model' => new Tree(),
            ]);
        }
    }

    public function actionIndex()
    {
        $tree = new Tree();

        $active = [];
        if ($pid = \Yii::$app->request->getQueryParam("pid"))
        {
            $selected = Tree::find()->where(["id" => $pid])->one();
            if ($selected)
            {

                $active = $selected->hasParent() ? $selected->findParents()->all() : [];

                $active[] = $selected;
            }
        }

        $models = $tree->findRoots()->all();
        $this->_activeTmp = $active;

        return $this->output($this->renderNodes($models));
    }

    protected $_activeTmp = [];
    public function renderNodes($models)
    {
        $ul = Html::ul($models, [
            "item" => function($model)
            {
                $controller = App::moduleCms()->createControllerByID("admin-tree");
                $controller->setModel($model);

                $child = "";
                foreach ($this->_activeTmp as $active)
                {
                    if (Validate::validate(new IsSame($active), $model)->isValid() && $model->hasChildrens())
                    {
                        $child = $this->renderNodes($model->findChildrens()->all());
                    }
                }

                return Html::tag("li",
                    ($model->hasChildrens() ? " + " : "") .
                    Html::a($model->name, UrlHelper::construct("cms/admin-tree/index")->set("pid", $model->id)) .
                    DropdownControllerActions::begin([
                        "controller"    => $controller,
                    ])->run() . $child
                );


            }
        ]);

        return $ul;
    }
}

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
use skeeks\cms\models\Search;
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
use yii\web\Cookie;

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
                        "label" => "Управление подразделами",
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

            $this->redirect(Url::to(["new-children", "id" => $parent->primaryKey]));
        }
        else
        {
            $tree   = new Tree();
            $search = new Search(Tree::className());
            $dataProvider   = $search->search(\Yii::$app->request->queryParams);
            $searchModel    = $search->getLoadedModel();

            $dataProvider->query->andWhere([$tree->pidAttrName => $parent->primaryKey]);

            $controller = App::moduleCms()->createControllerByID("admin-tree");

            return $this->render('new-children', [
                'model'         => new Tree(),

                'searchModel'   => $searchModel,
                'dataProvider'  => $dataProvider,
                'controller'    => $controller,
            ]);
        }
    }

    public function actionIndex()
    {
        $tree = new Tree();
        $models = $tree->findRoots()->all();

        $widget = \skeeks\cms\modules\admin\widgets\Tree::begin([
            "models" => $models
        ]);
        return $this->output($widget->run());
    }


    /**
     * Lists all Game models.
     * @return mixed
     */
    public function actionList()
    {
        $modelSeacrhClass = $this->_modelSearchClassName;

        if (!$modelSeacrhClass)
        {
            $search = new Search($this->_modelClassName);
            $dataProvider = $search->search(\Yii::$app->request->queryParams);
            $searchModel = $search->getLoadedModel();
        } else
        {
            $searchModel = new $modelSeacrhClass();
            $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
        }

        return $this->render('list', [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'controller'    => $this,
        ]);
    }
}

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
class AdminTreeController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Дерево страниц";
        $this->modelShowAttribute     = "name";
        $this->modelClassName         = Tree::className();

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' =>
            [
                'class'         => AdminAction::className(),
                'name'          => 'Разделы',
                'viewParams'    => $this->indexData()
            ],

            'create' =>
            [
                'visible'    => false
            ]
        ]);
    }

    public function indexData()
    {
        $tree = new Tree();
        $models = $tree->findRoots()->all();

        return
        [
            'models' => $models
        ];
    }

    public function actionNewChildren()
    {
        /**
         * @var Tree $parent
         */
        $parent = $this->model;

        if (\Yii::$app->request->isPost)
        {
            $post = \Yii::$app->request->post();

            if($post["recalculate_children_priorities"])
            {
                $sort = $post["sort"]=="asc"?true:false;

                $parent->recalculateChildrenPriorities($post["column"], $sort);

                //$node1 = $tree->find()->where(['priority'=>200])->one();
                //$node2 = $tree->find()->where(['priority'=>300])->one();
                //$node1->swapPriorities($node2);
            }
            else
            {
                $childTree = new Tree();
                $parent = Tree::find()->where(['id' => $post["pid"]])->one();

                $childTree->load($post);

                if(!$childTree->priority)
                {
                    $childTree->priority = 0;
                }

                $response = ['success' => false];

                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

                try
                {
                    if ($parent && $parent->processAddNode($childTree))
                    {
                        $response['success'] = true;
                    }
                } catch (\Exception $e)
                {
                    $response['success'] = false;
                    $response['message'] = $e->getMessage();
                }

            }

            if(!$post["no_redirect"])
            {
                $this->redirect(Url::to(["new-children", "id" => $parent->primaryKey]));
            }
            else
            {
                return $response;
            }
        }
        else
        {
            $tree   = new Tree();
            $search = new Search(Tree::className());
            $dataProvider   = $search->search(\Yii::$app->request->queryParams);
            $searchModel    = $search->getLoadedModel();

            $dataProvider->query->andWhere([$tree->pidAttrName => $parent->primaryKey]);

            $controller = \Yii::$app->cms->moduleCms()->createControllerByID("admin-tree");

            return $this->render('new-children', [
                'model'         => new Tree(),

                'searchModel'   => $searchModel,
                'dataProvider'  => $dataProvider,
                'controller'    => $controller,
            ]);
        }
    }





    /**
     * Пересортирует элементы дерева при перетаскивании
     */
    //TODO от swapPriorities нет пользы, когда приоритеты нод равны (закомментировання часть)
    //TODO нужно сделать так, чтобы при равных приортетах менялись приоритеты
    //TODO пока что циклом меняем приоритеты всех нод
    public function actionResort()
    {
        $response =
        [
            'success' => false
        ];

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (\Yii::$app->request->isPost)
        {
            $tree = new Tree();

            $post = \Yii::$app->request->post();

            $ids = array_reverse(array_filter($post['ids']));

            $priority = 100;

            foreach($ids as $id)
            {
                $node = $tree->find()->where(['id'=>$id])->one();
                $node->priority = $priority;
                $node->save(false);
                $priority += 100;
            }

            $response['success'] = true;
        }

        /*
        if (\Yii::$app->request->isPost)
        {
            $tree = new Tree();

            $post = \Yii::$app->request->post();

            $resortIds = array_filter($post['ids']);
            $changeId = intval($post['changeId']);

            $changeNode = $tree->find()->where(['id' => $changeId])->one();

            $nodes = $tree->find()->where(['pid' =>$changeNode->pid])->orderBy(["priority" => SORT_DESC])->all();
            $origIds = [];
            foreach($nodes as $node)
            {
                $origIds[] = $node->id;
            }

            $origPos = array_search($changeId, $origIds);
            $resortPos = array_search($changeId, $resortIds);

            if($origPos > $resortPos)
            {
                $origIds = array_reverse($origIds);
                $offset = count($origIds) - 1;
                $origPos = $offset - $origPos;
                $resortPos = $offset - $resortPos;
            }

            for($i = $origPos+1; $i <= $resortPos; $i++)
            {
                $id = $origIds[$i];
                $node = $tree->find()->where(['id'=>$id])->one();
                $changeNode->swapPriorities($node);
            }

            $response['success'] = true;
        }
        */

        return $response;
    }
}

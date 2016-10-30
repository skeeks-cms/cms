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

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\modules\admin\widgets\DropdownControllerActions;
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
        $actions = ArrayHelper::merge(parent::actions(), [
            'index' =>
            [
                'class'         => AdminAction::className(),
                'name'          => 'Разделы',
                'viewParams'    => $this->indexData()
            ],

            'create' =>
            [
                'visible'    => false
            ],

            "update" =>
            [
                'class'         => AdminOneModelEditAction::className(),
                "callback"      => [$this, 'update'],
            ],

        ]);

        unset($actions['create']);

        return $actions;
    }




    public function update(AdminAction $adminAction)
    {
        /**
         * @var $model CmsTree
         */
        $model = $this->model;
        $relatedModel = $model->relatedPropertiesModel;

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            return \yii\widgets\ActiveForm::validateMultiple([
                $model, $relatedModel
            ]);
        }

        if ($rr->isRequestPjaxPost())
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());

            if ($model->save() && $relatedModel->save())
            {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms','Saved'));

                if (\Yii::$app->request->post('submit-btn') == 'apply')
                {

                } else
                {
                    return $this->redirect(
                        $this->indexUrl
                    );
                }

                $model->refresh();

            } else
            {
                $errors = [];

                if ($model->getErrors())
                {
                    foreach ($model->getErrors() as $error)
                    {
                        $errors[] = implode(', ', $error);
                    }
                }

                \Yii::$app->getSession()->setFlash('error', \Yii::t('skeeks/cms','Could not save') . $errors);
            }
        }

        return $this->render('_form', [
            'model'           => $model,
            'relatedModel'    => $relatedModel
        ]);
    }



    static public $indexData = [];

    public function indexData()
    {
        if (self::$indexData)
        {
            return self::$indexData;
        }

        $models = Tree::findRoots()->joinWith('cmsSiteRelation')->orderBy([CmsSite::tableName() . ".priority" => SORT_ASC])->all();

        self::$indexData =
        [
            'models' => $models
        ];

        return self::$indexData;
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


            $childTree = new Tree();
            $parent = Tree::find()->where(['id' => $post["pid"]])->one();

            $childTree->load($post);

            if (!$childTree->priority)
            {
                $childTree->priority = Tree::PRIORITY_STEP;

                //Элемент с большим приоритетом
                if ($treeChildrens = $parent->getChildren()->orderBy(['priority' => SORT_DESC])->one())
                {
                    $childTree->priority = $treeChildrens->priority + Tree::PRIORITY_STEP;
                }
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


            if (!$post["no_redirect"])
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

            $dataProvider->query->andWhere(['pid' => $parent->primaryKey]);

            $controller = \Yii::$app->cms->moduleCms->createControllerByID("admin-tree");

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

            //$ids = array_reverse(array_filter($post['ids']));
            $ids = array_filter($post['ids']);

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

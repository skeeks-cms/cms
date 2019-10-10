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

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\modelEditor\ModelEditorGridAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget;
use skeeks\yii2\form\fields\WidgetField;
use Yii;
use yii\base\DynamicModel;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @property Tree $model
 *
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminTreeController extends AdminModelEditorController
{
    public $notSubmitParam = 'sx-not-submit';

    public function init()
    {
        $this->name = "Дерево страниц";
        $this->modelShowAttribute = "name";
        $this->modelHeader = function () {
            $model = $this->model;
            return Html::tag('h1', $model->fullName . Html::a('<i class="fas fa-external-link-alt"></i>', $model->url, [
                'target' => "_blank",
                'class' => "g-ml-20",
                'title' => \Yii::t('skeeks/cms', 'Watch to site (opens new window)'),
            ]));
        };
        $this->modelClassName = Tree::className();

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            'index' => [
                'class'    => AdminAction::className(),
                'name'     => \Yii::t('skeeks/cms', 'Tree'),
                'callback' => [$this, 'indexAction'],
                'accessCallback' => true,
            ],

            'list' => [
                'class'    => ModelEditorGridAction::className(),
                'name'     => \Yii::t('skeeks/cms', 'List'),
                "icon"     => "fa fa-list",
                "priority" => 10,
            ],

            'create' => [
                'visible' => false,
            ],

            'new-children' => [
                'class' => BackendAction::class,
                'isVisible' => false,
                'name' => "Создать подраздел",
                'callback' => [$this, 'actionNewChildren'],
            ],

            'resort' => [
                'class' => BackendAction::class,
                'isVisible' => false,
                'name' => "Сортировать подразделы",
                'callback' => [$this, 'actionResort'],
            ],

            "update" => [
                'class'    => BackendModelUpdateAction::className(),
                "callback" => [$this, 'update'],
            ],

            "move" => [
                'class'          => BackendModelUpdateAction::class,
                "name"           => \Yii::t('skeeks/cms', 'Move'),
                "icon"           => "fas fa-expand-arrows-alt",
                "beforeContent"  => "Механизм перемещения раздела. Укажите новый родительский раздел. <p><b>Внимание!</b> перемещение раздела, повлияет на изменение адресов всех дочерних разделов.</p>",
                "successMessage" => "Раздел успешно перемещен",

                'on initFormModels' => function (Event $e) {
                    $model = $e->sender->model;
                    $dm = new DynamicModel(['pid']);
                    $dm->addRule(['pid'], 'integer');

                    $dm->addRule(['pid'], function ($attribute) use ($dm, $model) {
                        if ($dm->pid == $model->id) {
                            $dm->addError($attribute, \Yii::t('skeeks/cms', 'Нельзя переместить в этот раздел.'));
                            return false;
                        }

                        $newParent = CmsTree::findOne($dm->pid);
                        if ($newParent->getChildren()->andWhere(['code' => $model->code])->one()) {
                            $dm->addError($attribute, \Yii::t('skeeks/cms', 'Нельзя переместить в этот раздел, потому что в этом разделе есть подразделы с кодом: '.$model->code));
                            return false;
                        }

                    });


                    $e->sender->formModels['dm'] = $dm;
                },

                'on beforeSave' => function (Event $e) {
                    /**
                     * @var $action BackendModelUpdateAction;
                     * @var $model CmsTree;
                     */
                    $action = $e->sender;
                    $action->isSaveFormModels = false;
                    $dm = ArrayHelper::getValue($action->formModels, 'dm');

                    /*$newParent = $this->getParent()->one();
                    if ($newParent->getChildren()->andWhere(['code' => $this->code])->one()) {
                        throe
                    }*/

                    $model = $action->model;
                    if ($dm->pid != $model->pid) {
                        $parent = CmsTree::findOne($dm->pid);

                        $model->appendTo($parent);
                    }

                    if ($model->save()) {
                        //$action->afterSaveUrl = Url::to(['update', 'pk' => $newModel->id, 'content_id' => $newModel->content_id]);
                    } else {
                        throw new Exception(print_r($model->errors, true));
                    }

                },

                'fields' => function ($action) {
                    /**
                     * @var $action BackendModelUpdateAction;
                     * @var $model CmsTree;
                     */
                    $model = $action->model;
                    $childrents = $model->children;
                    if ($childrents) {
                        $childrents = ArrayHelper::map($childrents, 'id', 'id');
                        $childrents = array_keys($childrents);
                        $childrents[] = $model->id;
                    } else {
                        $childrents = [$model->id];
                    }

                    if ($model->pid) {
                        $childrents[] = $model->pid;
                    }


                    if (!$model->isRoot()) {
                        return [
                            'dm.pid' => [
                                'class'        => WidgetField::class,
                                'widgetClass'  => SelectTreeInputWidget::class,
                                'widgetConfig' => [
                                    'isAllowNodeSelectCallback' => function ($tree) use ($model, $childrents) {
                                        if (in_array($tree->id, $childrents)) {
                                            return false;
                                        }

                                        return true;
                                    },
                                ],
                                //'widgetClass' => SelectModelDialogTreeWidget::class,
                                'label'        => ['skeeks/cms', 'Новый родительский раздел'],
                            ],
                        ];
                    }

                },
            ],


        ]);

        unset($actions['create']);

        return $actions;
    }


    public function update($adminAction)
    {
        $is_saved = false;
        $redirect = "";

        /**
         * @var $model CmsTree
         */
        $model = $this->model;
        if ($post = \Yii::$app->request->post()) {
            $model->load($post);
        }
        $relatedModel = $model->relatedPropertiesModel;

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            return \yii\widgets\ActiveForm::validateMultiple([
                $model,
                $relatedModel,
            ]);
        }

        if ($post = \Yii::$app->request->post()) {
            $model->load($post);
            $relatedModel->load($post);
        }

        if ($rr->isRequestPjaxPost()) {
            if (!\Yii::$app->request->post($this->notSubmitParam)) {
                $model->load(\Yii::$app->request->post());
                $relatedModel->load(\Yii::$app->request->post());

                if ($model->save() && $relatedModel->save()) {
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Saved'));

                    $is_saved = true;

                    if (\Yii::$app->request->post('submit-btn') == 'apply') {
                    } else {
                        $redirect = $this->url;
                    }

                    $model->refresh();

                }
            }

        }

        return $this->render('_form', [
            'model'        => $model,
            'relatedModel' => $relatedModel,

            'is_saved' => $is_saved,
            'submitBtn' => \Yii::$app->request->post('submit-btn'),
            'redirect' => $redirect,
        ]);
    }


    public function indexAction()
    {
        if ($root_id = \Yii::$app->request->get('root_id')) {
            $query = CmsTree::find()->where([CmsTree::tableName().'.id' => $root_id]);
        } else {
            $query = CmsTree::findRoots();
        }

        $models = $query
            ->joinWith('cmsSiteRelation')
            ->orderBy([CmsSite::tableName().".priority" => SORT_ASC])
            ->all();

        return $this->render($this->action->id, ['models' => $models]);
    }

    public function actionNewChildren()
    {
        /**
         * @var Tree $parent
         */
        $parent = $this->model;

        if (\Yii::$app->request->isPost) {
            $post = \Yii::$app->request->post();


            $childTree = new Tree();
            $parent = Tree::find()->where(['id' => $post["pid"]])->one();

            $childTree->load($post);

            if (!$childTree->priority) {
                $childTree->priority = Tree::PRIORITY_STEP;

                //Элемент с большим приоритетом
                if ($treeChildrens = $parent->getChildren()->orderBy(['priority' => SORT_DESC])->one()) {
                    $childTree->priority = $treeChildrens->priority + Tree::PRIORITY_STEP;
                }
            }

            $response = ['success' => false];

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            try {
                if ($parent && $childTree->appendTo($parent)->save()) {
                    $response['success'] = true;
                }
            } catch (\Exception $e) {
                throw $e;
            }


            if (!$post["no_redirect"]) {
                $this->redirect(Url::to(["new-children", "id" => $parent->primaryKey]));
            } else {
                return $response;
            }
        } else {
            $tree = new Tree();
            $search = new Search(Tree::className());
            $dataProvider = $search->search(\Yii::$app->request->queryParams);
            $searchModel = $search->getLoadedModel();

            $dataProvider->query->andWhere(['pid' => $parent->primaryKey]);

            $controller = \Yii::$app->cms->moduleCms->createControllerByID("admin-tree");

            return $this->render('new-children', [
                'model' => new Tree(),

                'searchModel'  => $searchModel,
                'dataProvider' => $dataProvider,
                'controller'   => $controller,
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
                'success' => false,
            ];

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (\Yii::$app->request->isPost) {
            $tree = new Tree();

            $post = \Yii::$app->request->post();

            //$ids = array_reverse(array_filter($post['ids']));
            $ids = array_filter($post['ids']);

            $priority = 100;

            foreach ($ids as $id) {
                $node = $tree->find()->where(['id' => $id])->one();
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

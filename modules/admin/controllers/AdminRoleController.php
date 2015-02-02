<?php
/**
 * AdminRoleController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.01.2015
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\controllers;

use skeeks\cms\models\AuthItem;
use skeeks\cms\models\searchs\AuthItem as AuthItemSearch;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use yii\helpers\ArrayHelper;
use yii\rbac\Role;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\rbac\Item;
use Yii;
use yii\helpers\Html;
/**
 * AuthItemController implements the CRUD actions for AuthItem model.
 */
class AdminRoleController extends AdminModelEditorController
{


    public function init()
    {
        $this->_label                   = "Управление ролями";
        $this->_modelShowAttribute      = "name";
        $this->modelPkAttribute         = "name";
        $this->_modelClassName          = Role::className();

        parent::init();
    }

    /**
     * Finds the Game model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ActiveRecord the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function _findModel($id)
    {
        return $this->findModel($id);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],

            self::BEHAVIOR_ACTION_MANAGER =>
            [
                "actions" =>
                [
                    "index" =>
                    [
                        "label"         => "Список",
                        "icon"         => "glyphicon glyphicon-th-list",
                        "rules"         => NoModel::className()
                    ],

                    "view" =>
                    [
                        "label"         => "Смотреть",
                        "icon"          => "glyphicon glyphicon-eye-open",
                        "rules"         => HasModel::className()
                    ],

                    "create" =>
                    [
                        "label"         => "Добавить",
                        "icon"          => "glyphicon glyphicon-plus",
                        "rules"         => NoModel::className()
                    ],





/*
                    "update" =>
                    [
                        "label"         => "Редактировать",
                        "icon"          => "glyphicon glyphicon-pencil",
                        "rules"         => HasModel::className()
                    ],

                    "delete" =>
                    [
                        "label"         => "Удалить",
                        "icon"          => "glyphicon glyphicon-trash",
                        "method"        => "post",
                        "confirm"       => \Yii::t('yii', 'Are you sure you want to delete this item?'),
                        "priority"      => 9999,
                        "rules"         => HasModel::className()
                    ]*/
                ]
            ]
        ]);
    }
    /**
     * Lists all AuthItem models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AuthItemSearch(['type' => Item::TYPE_ROLE]);
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());
        return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'controller' => $this,
        ]);
    }
    /**
     * Displays a single AuthItem model.
     * @param  string $id
     * @return mixed
     */
    public function actionView()
    {
        $model = $this->getCurrentModel();
        $id = $model->name;
        $model = $this->findModel($id);
        $authManager = Yii::$app->getAuthManager();
        $avaliable = $assigned = [
            'Roles' => [],
            'Permission' => [],
            'Routes' => [],
        ];
        $children = array_keys($authManager->getChildren($id));
        $children[] = $id;
        foreach ($authManager->getRoles() as $name => $role) {
            if (in_array($name, $children)) {
                continue;
            }
            $avaliable['Roles'][$name] = $name;
        }
        foreach ($authManager->getPermissions() as $name => $role) {
            if (in_array($name, $children)) {
                continue;
            }
            $avaliable[$name[0] === '/' ? 'Routes' : 'Permission'][$name] = $name;
        }
        foreach ($authManager->getChildren($id) as $name => $child) {
            if ($child->type == Item::TYPE_ROLE) {
                $assigned['Roles'][$name] = $name;
            } else {
                $assigned[$name[0] === '/' ? 'Routes' : 'Permission'][$name] = $name;
            }
        }
        $avaliable = array_filter($avaliable);
        $assigned = array_filter($assigned);
        return $this->render('view', ['model' => $model, 'avaliable' => $avaliable, 'assigned' => $assigned]);
    }
    /**
     * Creates a new AuthItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AuthItem(null);
        $model->type = Item::TYPE_ROLE;
        if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->name]);
        } else {
            return $this->render('create', ['model' => $model,]);
        }
    }
    /**
     * Updates an existing AuthItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->name]);
        }
        return $this->render('update', ['model' => $model,]);
    }
    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        Yii::$app->getAuthManager()->remove($model->item);
        return $this->redirect(['index']);
    }
    /**
     * Assign or remove items
     * @param string $id
     * @param string $action
     * @return array
     */
    public function actionAssign($id, $action)
    {
        $post = Yii::$app->getRequest()->post();
        $roles = $post['roles'];
        $manager = Yii::$app->getAuthManager();
        $parent = $manager->getRole($id);
        $error = [];
        if ($action == 'assign') {
            foreach ($roles as $role) {
                $child = $manager->getRole($role);
                $child = $child ? : $manager->getPermission($role);
                try {
                    $manager->addChild($parent, $child);
                } catch (\Exception $e) {
                    $error[] = $e->getMessage();
                }
            }
        } else {
            foreach ($roles as $role) {
                $child = $manager->getRole($role);
                $child = $child ? : $manager->getPermission($role);
                try {
                    $manager->removeChild($parent, $child);
                } catch (\Exception $e) {
                    $error[] = $e->getMessage();
                }
            }
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return [$this->actionRoleSearch($id, 'avaliable', $post['search_av']),
            $this->actionRoleSearch($id, 'assigned', $post['search_asgn']),
            $error];
    }
    /**
     * Search role
     * @param string $id
     * @param string $target
     * @param string $term
     * @return array
     */
    public function actionRoleSearch($id, $target, $term = '')
    {
        $result = [
            'Roles' => [],
            'Permission' => [],
            'Routes' => [],
        ];
        $authManager = Yii::$app->authManager;
        if ($target == 'avaliable') {
            $children = array_keys($authManager->getChildren($id));
            $children[] = $id;
            foreach ($authManager->getRoles() as $name => $role) {
                if (in_array($name, $children)) {
                    continue;
                }
                if (empty($term) or strpos($name, $term) !== false) {
                    $result['Roles'][$name] = $name;
                }
            }
            foreach ($authManager->getPermissions() as $name => $role) {
                if (in_array($name, $children)) {
                    continue;
                }
                if (empty($term) or strpos($name, $term) !== false) {
                    $result[$name[0] === '/' ? 'Routes' : 'Permission'][$name] = $name;
                }
            }
        } else {
            foreach ($authManager->getChildren($id) as $name => $child) {
                if (empty($term) or strpos($name, $term) !== false) {
                    if ($child->type == Item::TYPE_ROLE) {
                        $result['Roles'][$name] = $name;
                    } else {
                        $result[$name[0] === '/' ? 'Routes' : 'Permission'][$name] = $name;
                    }
                }
            }
        }
        return Html::renderSelectOptions('', array_filter($result));
    }
    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param  string        $id
     * @return AuthItem      the loaded model
     * @throws HttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $item = Yii::$app->getAuthManager()->getRole($id);
        if ($item) {
            return new AuthItem($item);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
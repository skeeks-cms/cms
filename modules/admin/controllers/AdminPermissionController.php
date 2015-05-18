<?php
/**
 * AdminPermissionController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\Exception;
use skeeks\cms\models\AuthItem;
use skeeks\cms\models\searchs\AuthItem as AuthItemSearch;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use yii\helpers\ArrayHelper;
use yii\rbac\Permission;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\rbac\Item;
use Yii;
use yii\web\Response;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * AuthItemController implements the CRUD actions for AuthItem model.
 */
class AdminPermissionController extends AdminModelEditorController
{
    public function init()
    {
        $this->_label                   = "Управление привилегиями";
        $this->_modelShowAttribute      = "name";
        $this->modelPkAttribute         = "name";
        $this->_modelClassName          = Permission::className();

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

                    "update-data" =>
                    [
                        "label"         => "Обновить привилегии",
                        "icon"          => "glyphicon glyphicon-plus",
                        "rules"         => NoModel::className(),
                        "method"        => "post",
                        "request"       => "ajax",
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

    public function actionUpdateData()
    {
        $auth = Yii::$app->authManager;

        foreach (\Yii::$app->adminMenu->getData() as $group)
        {
            if (is_array($group))
            {
                foreach ($group['items'] as $itemData)
                {
                    /**
                     * @var $controller \yii\web\Controller
                     */
                    list($controller, $route) = \Yii::$app->createController($itemData['url'][0]);


                    if ($controller)
                    {
                        if ($controller instanceof AdminController)
                        {
                            $permissionCode = \Yii::$app->cms->moduleAdmin()->getPermissionCode($controller->getUniqueId());

                            //Привилегия доступу к админке
                            if (!$adminAccess = $auth->getPermission($permissionCode))
                            {
                                $adminAccess = $auth->createPermission($permissionCode);
                                $adminAccess->description = 'Администрирование | ' . $controller->getLabel();
                                $auth->add($adminAccess);

                                if ($root = $auth->getRole('root'))
                                {
                                    $auth->addChild($root, $adminAccess);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Lists all AuthItem models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AuthItemSearch(['type' => Item::TYPE_PERMISSION]);
        $dataProvider = $searchModel->search(Yii::$app->getRequest()->getQueryParams());
        return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'controller' => $this,
        ]);
    }
    /**
     * Displays a single AuthItem model.
     * @param string $id
     * @return mixed
     */
    public function actionView()
    {
        $model = $this->getCurrentModel();
        $id = $model->name;
        $model = $this->findModel($id);
        $authManager = Yii::$app->getAuthManager();
        $avaliable = $assigned = [
            'Permission' => [],
            'Routes' => [],
        ];
        $children = array_keys($authManager->getChildren($id));
        $children[] = $id;
        foreach ($authManager->getPermissions() as $name => $role) {
            if (in_array($name, $children)) {
                continue;
            }
            $avaliable[$name[0] === '/' ? 'Routes' : 'Permission'][$name] = $name . ' — ' . $role->description;;
        }
        foreach ($authManager->getChildren($id) as $name => $child) {
            $assigned[$name[0] === '/' ? 'Routes' : 'Permission'][$name] = $name . ' — ' . $child->description;;
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
        $model->type = Item::TYPE_PERMISSION;

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

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
    public function actionUpdate()
    {
        $model = $this->getCurrentModel();
        $id = $model->name;

        $model = $this->findModel($id);

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if (\Yii::$app->request->isAjax)
        {
            if ($model->load(\Yii::$app->request->post()) && $model->save())
            {
                \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
            } else
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }
        }

        return $this->render('update', ['model' => $model,]);
    }
    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  string $id
     * @return mixed
     */
    public function actionDelete()
    {
        $model = $this->getCurrentModel();
        $id = $model->name;

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
        $parent = $manager->getPermission($id);
        $error = [];
        if ($action == 'assign') {
            foreach ($roles as $role) {
                $child = $manager->getPermission($role);
                try {
                    $manager->addChild($parent, $child);
                } catch (\Exception $exc) {
                    $error[] = $exc->getMessage();
                }
            }
        } else {
            foreach ($roles as $role) {
                $child = $manager->getPermission($role);
                try {
                    $manager->removeChild($parent, $child);
                } catch (\Exception $exc) {
                    $error[] = $exc->getMessage();
                }
            }
        }
        Yii::$app->getResponse()->format = Response::FORMAT_JSON;
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
            'Permission' => [],
            'Routes' => [],
        ];
        $authManager = Yii::$app->getAuthManager();
        if ($target == 'avaliable') {
            $children = array_keys($authManager->getChildren($id));
            $children[] = $id;
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
                    $result[$name[0] === '/' ? 'Routes' : 'Permission'][$name] = $name;
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
        $item = Yii::$app->getAuthManager()->getPermission($id);
        if ($item) {
            return new AuthItem($item);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
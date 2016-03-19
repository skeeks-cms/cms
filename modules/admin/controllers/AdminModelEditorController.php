<?php
/**
 * AdminModelEditorController - базовый контроллер админки, который позволяет показывать список моделей.
 * А так же предоставляет действия создания сущьностей, редактирования, просмотра, удаления
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 2.0.0
 */

namespace skeeks\cms\modules\admin\controllers;
use skeeks\admin\components\AccessControl;
use skeeks\cms\base\widgets\ActiveForm;
use skeeks\cms\components\Cms;
use skeeks\cms\Exception;
use skeeks\cms\helpers\ComponentHelper;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\AdminModelAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorCreateAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorUpdateAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelUpdateAction;
use skeeks\cms\modules\admin\actions\modelEditor\ModelEditorGridAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\helpers\Action;
use skeeks\cms\modules\admin\controllers\helpers\ActionModel;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\ControllerModelActions;
use skeeks\cms\rbac\CmsManager;
use yii\base\ActionEvent;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\behaviors\BlameableBehavior;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @property \yii\db\ActiveRecord $model
 *
 * Class AdminModelEditorController
 * @package skeeks\cms\modules\admin\controllers
 */
class AdminModelEditorController extends AdminController
{
    /**
     * Обязателен к заполнению!
     * Класс модели с которым работает контроллер.
     *
     * @example ActiveRecord::className();
     * @see _ensure()
     * @var string
     */
    public $modelClassName;

    /**
     * Действие для управления моделью по умолчанию
     * @var string
     */
    public $modelDefaultAction      = "update";

    /**
     * Атрибут модели который будет показан в хлебных крошках, и title страницы.
     * @var string
     */
    public $modelShowAttribute      = "id";

    /**
     * PK будет использоваться для поиска модели
     * @var string
     */
    public $modelPkAttribute        = "id";

    /**
     * Названия параметра PK, в запросе
     * @var string
     */
    public $requestPkParamName        = "pk";


    /**
     * @var null|AdminMultiModelEditAction[]
     */
    protected $_multiActions    = null;

    /**
     * @var ActiveRecord
     */
    protected $_model = null;

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = ArrayHelper::merge(parent::behaviors(), [

            'verbs' =>
            [
                'class' => VerbFilter::className(),
                'actions' =>
                [
                    'delete'        => ['post'],
                    'delete-multi'  => ['post'],
                ],
            ],

            'accessDelete' =>
            [
                'class'         => AdminAccessControl::className(),
                'only'          => ['delete'],
                'rules'         =>
                [
                    [
                        'allow'         => true,
                        'matchCallback' => function($rule, $action)
                        {
                            if (ComponentHelper::hasBehavior($this->model, BlameableBehavior::className()))
                            {
                                //Если такая привилегия заведена, нужно ее проверять.
                                if ($permission = \Yii::$app->authManager->getPermission(CmsManager::PERMISSION_ALLOW_MODEL_DELETE))
                                {
                                    if (!\Yii::$app->user->can($permission->name, [
                                        'model' => $this->model
                                    ]))
                                    {
                                        return false;
                                    }
                                }
                            }

                            return true;
                        }
                    ],
                ],
            ]
        ]);

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                'index' =>
                [
                    'class'         => ModelEditorGridAction::className(),
                    'name'          => \Yii::t('app','List'),
                    "icon"          => "glyphicon glyphicon-th-list",
                    "priority"      => 10,
                ],

                'create' =>
                [
                    'class'         => AdminModelEditorCreateAction::className(),
                    'name'          => \Yii::t('app','Add'),
                    "icon"          => "glyphicon glyphicon-plus",
                ],


                "update" =>
                [
                    'class'         => AdminOneModelUpdateAction::className(),
                    "name"         => \Yii::t('app',"Edit"),
                    "icon"          => "glyphicon glyphicon-pencil",
                    "priority"      => 10,
                ],

                "delete" =>
                [
                    'class'         => AdminOneModelEditAction::className(),
                    "name"          => \Yii::t('app',"Delete"),
                    "icon"          => "glyphicon glyphicon-trash",
                    "confirm"       => \Yii::t('yii', 'Are you sure you want to delete this item?'),
                    "method"        => "post",
                    "request"       => "ajax",
                    "callback"      => [$this, 'actionDelete'],
                    "priority"      => 99999,
                ],

                "delete-multi" =>
                [
                    'class'             => AdminMultiModelEditAction::className(),
                    "name"              => \Yii::t('app',"Delete"),
                    "icon"              => "glyphicon glyphicon-trash",
                    "confirm"           => \Yii::t('yii', 'Are you sure you want to permanently delete the selected items?'),
                    "eachCallback"      => [$this, 'eachMultiDelete'],
                    "priority"          => 99999,
                ],

            ]
        );
    }

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->_ensure();
    }

    /**
     * Немного проверок для уверенности что все пойдет как надо
     * @throws InvalidConfigException
     */
    protected function _ensure()
    {
        if (!$this->modelClassName)
        {
            throw new InvalidConfigException(\Yii::t('app',"For {modelname} must specify the model class",['modelname' => 'AdminModelEditorController']));
        }

        if (!class_exists($this->modelClassName))
        {
            throw new InvalidConfigException("{$this->modelClassName} " . \Yii::t('app','the class is not found, you must specify the existing class model'));
        }
    }

    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action))
        {
            if ($action instanceof AdminOneModelEditAction)
            {
                if (($this->model !== null))
                {
                    return true;
                } else
                {
                    //throw new NotFoundHttpException('Не найдено');
                    $this->redirect($this->getIndexUrl());
                }
            }

            return true;
        } else
        {
            return false;
        }
    }

    /**
     * @return ActiveRecord
     * @throws NotFoundHttpException
     */
    public function getModel()
    {
        if ($this->_model === null)
        {
            $pk             = \Yii::$app->request->get($this->requestPkParamName);
            if ($pk)
            {
                $modelClass     = $this->modelClassName;
                $this->_model   = $modelClass::findOne($pk);
            }

        }

        return $this->_model;
    }

    /**
     * @param ActiveRecord $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->_model   = $model;
        $this->_actions = null;
        return $this;
    }

    /**
     * @return array|null|\skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction[]
     */
    public function getMultiActions()
    {
        if ($this->_multiActions !== null)
        {
            return $this->_multiActions;
        }

        $actions = $this->actions();

        if ($actions)
        {
            foreach ($actions as $id => $data)
            {
                $action                 = $this->createAction($id);

                if ($action instanceof AdminMultiModelEditAction)
                {
                    if ($action->isVisible())
                    {
                        $this->_multiActions[$id]    = $action;
                    }
                }

            }
        } else
        {
            $this->_multiActions = [];
        }

        //Сортировка по приоритетам
        if ($this->_multiActions)
        {
            ArrayHelper::multisort($this->_multiActions, 'priority');

        }

        return $this->_multiActions;
    }

    /**
     * Массив объектов действий доступных для текущего контроллера
     * Используется при построении меню.
     * @see ControllerActions
     * @return AdminAction[]
     */
    public function getActions()
    {
        if ($this->_actions !== null)
        {
            return $this->_actions;
        }

        $actions = $this->actions();

        if ($actions)
        {
            foreach ($actions as $id => $data)
            {
                $action                 = $this->createAction($id);

                if ($this->model)
                {
                    if ($action instanceof AdminOneModelEditAction)
                    {
                        if ($action->isVisible())
                        {
                            $this->_actions[$id]    = $action;
                        }
                    }
                } else
                {
                    if (!$action instanceof AdminOneModelEditAction && !$action instanceof AdminMultiModelEditAction)
                    {
                        if ($action->isVisible())
                        {
                            $this->_actions[$id]    = $action;
                        }
                    }
                }
            }
        } else
        {
            $this->_actions = [];
        }

        //Сортировка по приоритетам
        if ($this->_actions)
        {
            ArrayHelper::multisort($this->_actions, 'priority');

        }

        return $this->_actions;
    }




    /**
     * Deletes an existing Game model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionDelete()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost())
        {
            try
            {
                if ($this->model->delete())
                {
                    $rr->message = \Yii::t('app','Record deleted successfully');
                    $rr->success = true;
                } else
                {
                    $rr->message = \Yii::t('app','Record deleted unsuccessfully');
                    $rr->success = false;
                }
            } catch (\Exception $e)
            {
                $rr->message = $e->getMessage();
                $rr->success = false;
            }

            return (array) $rr;
        }
    }

    /**
     * @param $model
     * @param $action
     * @return bool
     */
    public function eachMultiDelete($model, $action)
    {
        try
        {
            return $model->delete();
        } catch (\Exception $e)
        {
            return false;
        }
    }




    /**
     * @return string
     */
    public function getIndexUrl()
    {
        return UrlHelper::construct($this->id . '/' . $this->action->id)->enableAdmin()->setRoute($this->defaultAction)->normalizeCurrentRoute()->toString();
    }



    /**
     * @return array
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionSortablePriority()
    {
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;

            if ($keys = \Yii::$app->request->post('keys'))
            {
                //$counter = count($keys);

                foreach ($keys as $counter => $key)
                {
                    $priority = ($counter + 1) * 1000;

                    $modelClassName = $this->modelClassName;
                    $model = $modelClassName::findOne($key);
                    if ($model)
                    {
                        $model->priority = $priority;
                        $model->save(false);
                    }

                    //$counter = $counter - 1;
                }
            }

            return [
                'success' => true,
                'message' => \Yii::t('app','Changes saved'),
            ];
        }
    }

}
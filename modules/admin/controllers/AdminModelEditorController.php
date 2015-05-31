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
use skeeks\cms\App;
use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\base\widgets\ActiveForm;
use skeeks\cms\Exception;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\AdminModelAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorCreateAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorUpdateAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\actions\modelEditor\ModelEditorGridAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\helpers\Action;
use skeeks\cms\modules\admin\controllers\helpers\ActionModel;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\controllers\helpers\rules\NoModel;
use skeeks\cms\modules\admin\widgets\ControllerModelActions;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\base\ActionEvent;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\behaviors\BlameableBehavior;
use yii\data\ActiveDataProvider;
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
     * @var ActiveRecord
     */
    protected $_model = null;

    /**
     * @return array
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
        ]);
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return
        [
            'index' =>
            [
                'class'         => ModelEditorGridAction::className(),
                'name'          => 'Список',
                "icon"          => "glyphicon glyphicon-th-list",
            ],

            'create' =>
            [
                'class'         => AdminModelEditorCreateAction::className(),
                'name'          => 'Добавить',
                "icon"          => "glyphicon glyphicon-plus",
            ],


            "update" =>
            [
                'class'         => AdminModelEditorUpdateAction::className(),
                "name"         => "Редактировать",
                "icon"          => "glyphicon glyphicon-pencil",
            ],

            "delete" =>
            [
                'class'         => AdminOneModelEditAction::className(),
                "name"          => "Удалить",
                "icon"          => "glyphicon glyphicon-trash",
                "confirm"       => \Yii::t('yii', 'Are you sure you want to delete this item?'),
                "method"        => "post",
                "request"       => "ajax",
                "callback"      => [$this, 'actionDelete'],
            ]
        ];
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
            throw new InvalidConfigException("Для AdminModelEditorController необходимо указать класс модели");
        }

        if (!class_exists($this->modelClassName))
        {
            throw new InvalidConfigException("{$this->modelClassName} класс не нейден, необходимо указать существующий класс модели");
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
            $modelClass     = $this->modelClassName;
            $this->_model   = $modelClass::findOne($pk);
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
                        $this->_actions[$id] = $action;
                    }
                } else
                {
                    if (!$action instanceof AdminOneModelEditAction)
                    {
                        $this->_actions[$id] = $action;
                    }
                }
            }
        } else
        {
            $this->_actions = [];
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
                    $rr->message = 'Запись успешно удалена';
                    $rr->success = true;
                } else
                {
                    $rr->message = 'Не получилось удалить запись';
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
     * @return string
     */
    public function getIndexUrl()
    {
        return UrlHelper::construct($this->id . '/' . $this->action->id)->enableAdmin()->setRoute($this->defaultAction)->normalizeCurrentRoute()->toString();
    }

}
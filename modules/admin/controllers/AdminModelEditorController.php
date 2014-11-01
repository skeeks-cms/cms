<?php
/**
 * AdminModelEditorController - базовый контроллер админки, который позволяет показывать список моделей.
 * А так же предоставляет действия создания сущьностей, редактирования, просмотра, удаления
 *
 *
 * TODO: Доработки, планы
 * 1) сейчас сплошной харкод привязка к id модели, на самом деле первичный ключ может быть не обязатльено id
 * 2) добавить проверки на наличие у модели PK  (добавил но нужно доработать)
 * 3) автоматическая генерация SearchObject опционально
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\Exception;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\widgets\ControllerModelActions;
use yii\base\ActionEvent;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\NotFoundHttpException;

/**
 * Class AdminEntityEditor
 * @package skeeks\cms\modules\admin\controllers
 */
class AdminModelEditorController extends AdminController
{
    //Действие показывается только если передана модель
    const ACTION_TYPE_MODEL = "model";

    /**
     * @var string
     */
    protected $_defaultModelAction = "view";
    protected $_modelShowAttribute = "id";

    /**
     * обязателено указывать!
     * @var null|string название сласса модели. example: ActiveRecord::className()
     */
    protected $_modelClassName  = null;

    /**
     * Опционально
     * @var null|string
     */
    protected $_modelSearchClassName = null;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this
            ->_addAction("index", ["label" => "Список"])
            ->_addAction("create", ["label" => "Добавить"])

            ->_addAction("view", [
                "label" => "Смотреть",
                "icon"  => "glyphicon glyphicon-eye-open"
            ])

            ->_addAction("update", [
                "label" => "Редактировать",
                "icon"  => "glyphicon glyphicon-pencil"
            ])

            ->_addAction("delete", [
                "label"         => "Удалить",
                "icon"          => "glyphicon glyphicon-trash",
                "method"        => "post",
                "confirm"       => \Yii::t('yii', 'Are you sure you want to delete this item?'),
                "priority"      => 9999
            ])
        ;
    }

    /**
     * Немного проверок для уверенности что все пойдет как надо
     * @throws InvalidConfigException
     */
    protected function _ensure()
    {
        parent::_ensure();

        if ($this->_modelClassName === null)
        {
            throw new InvalidConfigException("Для AdminEntityEditor необходимо указать класс модели");
        }

        if (!class_exists($this->_modelClassName))
        {
            throw new InvalidConfigException("{$this->_modelClassName} класс не нейден, необходимо указать существующий класс модели");
        }

        if (!is_subclass_of($this->_modelClassName, ActiveRecord::className()))
        {
            throw new InvalidConfigException("{$this->_modelClassName} класс должен быть дочерним классом — " . ActiveRecord::className());
        }

        //TODO: доработать
        if ($this->_modelSearchClassName !== null)
        {
            if (!class_exists($this->_modelSearchClassName))
            {
                throw new InvalidConfigException("{$this->_modelSearchClassName} класс не нейден, необходимо указать существующий класс поиска");
            }

            if (!is_subclass_of($this->_modelSearchClassName, ActiveRecord::className()))
            {
                throw new InvalidConfigException("{$this->_modelSearchClassName} класс должен быть дочерним классом — " . ActiveRecord::className());
            }
        }
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(),
        [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ]);
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
        $modelClass = $this->_modelClassName;
        if (($model = $modelClass::findOne($id)) !== null)
        {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected $_currentModel = null;

    /**
     * @return $this
     * @throws Exception
     * @throws NotFoundHttpException
     */
    protected function _loadCurrentModel()
    {
        $id = \Yii::$app->request->getQueryParam("id");
        if (!$id)
        {
            throw new Exception("Текущая модель не может быть загружена");
        }
        $this->_currentModel = $this->_findModel($id);

        if (!$this->_currentModel->primaryKey)
        {
            throw new Exception("У модели нет первичного ключа, не сможем с ней работать");
        }

        return $this;
    }

    /**
     * @return ActiveRecord
     * @throws Exception
     */
    public function getCurrentModel()
    {
        if ($this->_currentModel === null)
        {
            $this->_loadCurrentModel();
        }

        return $this->_currentModel;
    }
    /**
     * Рендер действий текущего контроллера
     * Сразу запускаем нужный виджет и формируем готовый html
     * @param ActionEvent $e
     *
     * @return $this
     */
    protected function _renderActions(ActionEvent $e)
    {
        $dataCurrentAction = [];
        if (isset($this->_actions[$e->action->id]))
        {
            $dataCurrentAction = $this->_actions[$e->action->id];
        }

        if ($this->_actionIsTypeModel($dataCurrentAction))
        {
            $this->getView()->params["actions"] = ControllerModelActions::begin([
                "currentAction" => $e->action->id,
                "controller"    => $this,
                "model"         => $this->getCurrentModel(),
            ])->run();

        } else
        {
            parent::_renderActions($e);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        $result = [];

        foreach ($this->_actions as $code => $data)
        {
            if (!$this->_actionIsTypeModel($data))
            {
                $result[$code] = $data;
            }
        }

        return $result;
    }

    /**
     * Действия для управления моделью
     * @return array
     */
    public function getModelActions()
    {
        $result = [];

        foreach ($this->_actions as $code => $data)
        {
            if ($this->_actionIsTypeModel($data))
            {
                $result[$code] = $data;
            }
        }

        return $result;
    }



    /**
     * Формируем данные для хлебных крошек.
     * Эти данные в layout - е будут передаваться в нужный виджет.
     * @param ActionEvent $e
     *
     * @return $this
     */
    protected function _renderBreadcrumbs(ActionEvent $e)
    {
        $actionTitle = Inflector::humanize($e->action->id);

        if (!isset($this->_actions[$e->action->id]))
        {
            return parent::_renderBreadcrumbs($e);
        }
        $data           = $this->_actions[$e->action->id];
        if (!$this->_actionIsTypeModel($data))
        {
            return parent::_renderBreadcrumbs($e);
        }



        if (isset($this->_actions[$e->action->id]))
        {
            $data = $this->_actions[$e->action->id];
            $actionTitle = ArrayHelper::getValue($data, "label");
        }

        if ($this->_label)
        {
            $this->getView()->params['breadcrumbs'][] = ['label' => $this->_label, 'url' => [
                'index',
                UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE
            ]];
        }

        $this->getView()->params['breadcrumbs'][] = ['label' => $this->getCurrentModel()->getAttribute($this->_modelShowAttribute), 'url' => [
            $this->_defaultModelAction,
            "id" => $this->getCurrentModel()->getPrimaryKey(),
            UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE
        ]];


        if ($this->_defaultAction != $e->action->id)
        {
            $this->getView()->params['breadcrumbs'][] = $actionTitle;
        }

        return $this;
    }


    /**
     * @param ActionEvent $e
     * @return $this
     */
    protected function _renderMetadata(ActionEvent $e)
    {
        $result = [];
        $actionTitle = Inflector::humanize($e->action->id);

        if (!isset($this->_actions[$e->action->id]))
        {
            return parent::_renderMetadata($e);
        }

        $data           = $this->_actions[$e->action->id];
        if (!$this->_actionIsTypeModel($data))
        {
            return parent::_renderMetadata($e);
        }

        $actionTitle    = ArrayHelper::getValue($data, "label", "Label");

        $result[] = $actionTitle;
        $result[] = $this->getCurrentModel()->getAttribute($this->_modelShowAttribute);
        $result[] = $this->_label;

        $this->getView()->title = implode(" / ", $result);
        return $this;
    }

    /**
     * Тип действия модель или общее?
     * @param array $dataAction
     * @return bool
     */
    protected function _actionIsTypeModel(array $dataAction)
    {
        $type = ArrayHelper::getValue($dataAction, "type");
        return $type == self::ACTION_TYPE_MODEL;
    }


    /**
     * Lists all Game models.
     * @return mixed
     */
    public function actionIndex()
    {
        $modelSeacrhClass = $this->_modelSearchClassName;
        $searchModel = new $modelSeacrhClass();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'controller'    => $this,
        ]);
    }

    /**
     * @return string
     */
    public function actionView()
    {
        return $this->output(\skeeks\cms\modules\admin\widgets\DetailView::widget([
            'model' => $this->getCurrentModel(),

        ]));
    }

    /**
     * Creates a new Game model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        /**
         * @var $model ActiveRecord
         */
        $modelClass = $this->_modelClassName;
        $model      = new $modelClass();

        if ($model->load(\Yii::$app->request->post()) && $model->save(false))
        {
            return $this->redirect(['view', 'id' => $model->id]);
        } else
        {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionUpdate()
    {
        $model = $this->getCurrentModel();

        if ($model->load(\Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Game model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionDelete()
    {
        $this->getCurrentModel()->delete();
        return $this->redirect(['index']);
    }

}
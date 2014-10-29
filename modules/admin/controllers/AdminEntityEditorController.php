<?php
/**
 * AdminEntityEditor - базовый контроллер админки, который позволяет показывать список моделей.
 * А так же предоставляет действия создания сущьностей, редактирования, просмотра, удаления
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

/**
 * Class AdminEntityEditor
 * @package skeeks\cms\modules\admin\controllers
 */
class AdminEntityEditorController extends AdminController
{
    //Действие показывается только если передана модель
    const ACTION_TYPE_MODEL = "model";

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

        $this->_actions =
        [
            "index" =>
            [
                "label" => "Список",
            ],

            "create" =>
            [
                "label" => "Добавить",
            ],


            "view" =>
            [
                "type"  => self::ACTION_TYPE_MODEL,
                "label" => "Смотреть",
                "icon"  => "glyphicon glyphicon-eye-open",
            ],

            "update" =>
            [
                "type"  => self::ACTION_TYPE_MODEL,
                "label" => "Редактировать",
                "icon"  => "glyphicon glyphicon-pencil",
            ],

            "delete" =>
            [
                "type"          => self::ACTION_TYPE_MODEL,
                "label"         => "Удалить",
                "icon"          => "glyphicon glyphicon-trash",
                "data-method"   => "post",
                'data-confirm'  => \Yii::t('yii', 'Are you sure you want to delete this item?'),
                "priority"      => 9999
            ]
        ];
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
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->_findModel($id),
        ]);
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
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->_findModel($id);

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
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->_findModel($id)->delete();
        return $this->redirect(['index']);
    }

}
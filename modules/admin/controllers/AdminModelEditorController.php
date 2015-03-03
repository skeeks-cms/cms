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
use skeeks\cms\App;
use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\base\widgets\ActiveForm;
use skeeks\cms\Exception;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
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
 * Class AdminEntityEditor
 * @package skeeks\cms\modules\admin\controllers
 */
class AdminModelEditorController extends AdminController
{
    /**
     * @var string
     */
    public $defaultActionModel      = "view";
    protected $_modelShowAttribute  = "id";
    public $modelPkAttribute        = "id";
    public $modelValidate           = false;
    public $enableScenarios         = false;

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

                    "create" =>
                    [
                        "label"         => "Добавить",
                        "icon"          => "glyphicon glyphicon-plus",
                        "rules"         => NoModel::className()
                    ],

                    /*"view" =>
                    [
                        "label"         => "Смотреть",
                        "icon"          => "glyphicon glyphicon-eye-open",
                        "rules"         => HasModel::className()
                    ],*/

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
                        "request"       => "ajax",
                        "confirm"       => \Yii::t('yii', 'Are you sure you want to delete this item?'),
                        "priority"      => 9999,
                        "rules"         => HasModel::className()
                    ]
                ]
            ]
        ]);
    }

    /**
     * @param ActionEvent $e
     */
    protected function _beforeAction(ActionEvent $e)
    {
        parent::_beforeAction($e);

        if ($this->enableScenarios)
        {
            if (!$this->getCurrentModel())
            {
                $this->setCurrentModel($this->createCurrentModel());
            }

            $this->getCurrentModel()->scenario = $e->action->id;
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
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
            //throw new InvalidConfigException("{$this->_modelClassName} класс должен быть дочерним классом — " . ActiveRecord::className());
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
                //throw new InvalidConfigException("{$this->_modelSearchClassName} класс должен быть дочерним классом — " . ActiveRecord::className());
            }
        }
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
            //throw new Exception("Текущая модель не может быть загружена");
            $this->_currentModel = false;
            return $this;
        }
        $this->_currentModel = $this->_findModel($id);


        //if (!$this->_currentModel->primaryKey)
        //{
            //throw new Exception("У модели нет первичного ключа, не сможем с ней работать");
        //    $this->_currentModel = false;
        //    return $this;
        //}

        $this->_setLangAndSite($this->_currentModel);

        return $this;
    }

    /**
     * @param Model $model
     * @return $this
     */
    protected function _setLangAndSite(Model $model)
    {
        try
        {
            if ($site = \Yii::$app->cms->moduleAdmin()->getCurrentSite())
            {
                $model->setCurrentSite($site);
            } else
            {
                $model->setCurrentSite(null);
            }

            if ($lang = \Yii::$app->cms->moduleAdmin()->getCurrentLang())
            {
                $model->setCurrentLang($lang);
            } else
            {
                $model->setCurrentLang(null);
            }
        } catch (\Exception $e)
        {}

        return $this;
    }

    /**
     * TODO: use $this->getModel();
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
     * @return ActiveRecord
     */
    public function createCurrentModel()
    {
        $modelClass = $this->_modelClassName;
        $model = new $modelClass();

        $this->_setLangAndSite($model);

        return $model;
    }

    /**
     * @return ActiveRecord
     */
    public function getModel()
    {
        return $this->getCurrentModel();
    }

    /**
     * @param Component $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->_currentModel = $model;
        return $this;
    }
    /**
     * TODO: use $this->getModel();
     * @param ActiveRecord $model
     * @return $this
     */
    public function setCurrentModel(\yii\db\ActiveRecord $model)
    {
        $this->_currentModel = $model;
        return $this;
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
        $currentAction = $this->_getActionFromEvent($e);
        if (!$currentAction instanceof Action || !$this->getCurrentModel())
        {
            return parent::_renderBreadcrumbs($e);
        }

        if ($this->_label)
        {
            $this->getView()->params['breadcrumbs'][] = ['label' => $this->_label, 'url' => [
                'index',
                UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE
            ]];
        }

        $this->getView()->params['breadcrumbs'][] = ['label' => $this->getCurrentModel()->{$this->_modelShowAttribute}, 'url' => [
            $this->defaultActionModel,
            "id" => $this->getCurrentModel()->{$this->modelPkAttribute},
            UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE
        ]];


        if ($this->defaultAction != $e->action->id)
        {
            $this->getView()->params['breadcrumbs'][] = $currentAction->label;
        }

        return $this;
    }


    /**
     * @param ActionEvent $e
     * @return $this
     */
    protected function _renderMetadata(ActionEvent $e)
    {
        //Если текущее действие не описано, делаем как нужно по умолчанию
        $currentAction = $this->_getActionFromEvent($e);
        if (!$currentAction instanceof Action || !$this->getCurrentModel())
        {
            return parent::_renderMetadata($e);
        }

        $actionTitle    = $currentAction->label;

        $result[] = $actionTitle;
        $result[] = $this->getCurrentModel()->{$this->_modelShowAttribute};
        $result[] = $this->_label;

        $this->getView()->title = implode(" / ", $result);
        return $this;
    }







    /**
     * Lists all Game models.
     * @var asdasd
     * @return mixed
     */
    public function actionIndex()
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

        try
        {
            return $this->render('index', [
                'searchModel'   => $searchModel,
                'dataProvider'  => $dataProvider,
                'controller'    => $this,
            ]);
        } catch (InvalidParamException $e)
        {
            $output = \Yii::$app->cms->moduleAdmin()->renderFile('base-actions/index.php',
                [
                    'searchModel'   => $searchModel,
                    'dataProvider'  => $dataProvider,
                    'controller'    => $this,
                    'columns'       => $this->getIndexColumns(),
                ]
            );

            return $this->output($output);
        }

    }

    public function getIndexColumns()
    {
        $columns = [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                'controller'    => $this
            ],

            'name',
        ];

        return $columns;
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

        if (!$model = $this->getCurrentModel())
        {
            $model = $this->createCurrentModel();
        }

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(\Yii::$app->request->post()) && $model->save($this->modelValidate))
        {
            \Yii::$app->getSession()->setFlash('success', 'Успешно добавлено');

            //\Yii::$app->response->getHeaders()->set('X-PJAX-Url', '/?aaa');
            if (\Yii::$app->request->isPjax)
            {

                //\Yii::$app->response->getHeaders()->set('X-PJAX-Url', \Yii::$app->request->referrer);

                //\Yii::$app->response->getHeaders()->set('X-PJAX-Url', UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute('update')->normalizeCurrentRoute()->addData(['id' => $model->id])->toString());
                \Yii::$app->response->getHeaders()->set('X-PJAX-Url', UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute('update')->normalizeCurrentRoute()->addData(['id' => $model->id])->toString());
            } else
            {
                return $this->redirect(
                    UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute('update')->normalizeCurrentRoute()->addData(['id' => $model->id])->toString()
                );
            }

        } else
        {
            if (\Yii::$app->request->isPost)
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }

            try
            {
                return $this->render('_form', [
                    'model' => $model,
                ]);

            } catch (InvalidParamException $e)
            {
                $output = \Yii::$app->cms->moduleAdmin()->renderFile('base-actions/_form.php',
                    [
                        'model' => $model,
                    ]
                );

                return $this->output($output);
            }

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

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if (\Yii::$app->request->isAjax)
        {
            $model->load(\Yii::$app->request->post());
            if ($model->load(\Yii::$app->request->post()) && $model->save($this->modelValidate))
            {
                \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                //return $this->redirectRefresh();
                //return $this->redirectRefresh();
                //print_r(UrlHelper::constructCurrent()->setRoute($this->action->id)->normalizeCurrentRoute()->enableAdmin()->toString() . '&asdasdasd');die;


            } else
            {
                //if (\Yii::$app->request->isPost)
                //{
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                //}
            }

            try
            {
                return $this->render('_form', [
                    'model'     => $model,
                ]);

            } catch (InvalidParamException $e)
            {
                $output = \Yii::$app->cms->moduleAdmin()->renderFile('base-actions/_form.php',
                    [
                        'model' => $model,
                    ]
                );

                return $this->output($output);
            }


        } else
        {
            if ($model->load(\Yii::$app->request->post()) && $model->save($this->modelValidate))
            {
                \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                return $this->redirectRefresh();
            } else
            {

                if (\Yii::$app->request->isPost)
                {
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                }

                try
                {
                    return $this->render('_form', [
                        'model' => $model,
                    ]);

                } catch (InvalidParamException $e)
                {
                    $output = \Yii::$app->cms->moduleAdmin()->renderFile('base-actions/_form.php',
                        [
                            'model' => $model,
                        ]
                    );

                    return $this->output($output);
                }
            }
        }
    }

    /**
     * Deletes an existing Game model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionDelete()
    {
        if (\Yii::$app->request->isAjax)
        {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $success = false;

            try
            {
                if ($this->getCurrentModel()->delete())
                {
                    $message = 'Запись успешно удалена';
                    //\Yii::$app->getSession()->setFlash('success', $message);
                    $success = true;
                } else
                {
                    $message = 'Не получилось удалить запись';
                    //\Yii::$app->getSession()->setFlash('error', $message);
                    $success = false;
                }
            } catch (\Exception $e)
            {
                $message = $e->getMessage();
                    //\Yii::$app->getSession()->setFlash('error', $message);
                $success = false;
            }



            return [
                'message' => $message,
                'success' => $success,
            ];

        } else
        {
            if ($this->getCurrentModel()->delete())
            {
                \Yii::$app->getSession()->setFlash('success', 'Запись успешно удалена');
            } else
            {
                \Yii::$app->getSession()->setFlash('error', 'Не получилось удалить запись');
            }

            if ($ref = UrlHelper::getCurrent()->getRef())
            {
                return $this->redirect($ref);
            } else
            {
                return $this->goBack();
            }
        }

        //return $this->redirect(\Yii::$app->request->getReferrer());
    }

}
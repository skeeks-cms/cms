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
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\AdminModelAction;
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
    public $model = null;

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
                'class'         => AdminAction::className(),
                'name'          => 'Добавить',
                "icon"          => "glyphicon glyphicon-plus",
            ],


            "update" =>
            [
                'class'         => AdminOneModelEditAction::className(),
                "name"         => "Редактировать",
                "icon"          => "glyphicon glyphicon-pencil",
            ],

            "delete" =>
            [
                'class'         => AdminOneModelEditAction::className(),
                "name"          => "Удалить",
                "icon"          => "glyphicon glyphicon-trash",
                "confirm"       => \Yii::t('yii', 'Are you sure you want to delete this item?'),
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
                $pk = \Yii::$app->request->get($this->requestPkParamName);
                $modelClass = $this->modelClassName;
                if (($this->model = $modelClass::findOne($pk)) !== null)
                {
                    return true;
                } else
                {
                    throw new NotFoundHttpException('Не найдено');
                }
            }

            return true;
        } else
        {
            return false;
        }
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
        ];

        /**
         * @var ActiveRecord $model
         */
        $modelClassName = $this->modelClassName;
        $model = new $modelClassName();

        $gridColumns = [];
        if ((array) $this->gridColumns)
        {
            foreach ($this->gridColumns as $data)
            {
                if (is_string($data))
                {
                    if ($model->hasAttribute($data))
                    {
                        $gridColumns[] = $data;
                    }
                } else
                {
                    $gridColumns[] = $data;
                }
            }
        }

        return ArrayHelper::merge($columns, $gridColumns);
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

            if (\Yii::$app->request->post('submit-btn') == 'apply')
            {
                return $this->redirect(
                    UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute('update')->normalizeCurrentRoute()->addData(['id' => $model->id])->toString()
                );
            } else
            {
                return $this->redirect(
                    $this->indexUrl
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

                if (\Yii::$app->request->post('submit-btn') == 'apply')
                {
                    \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                } else
                {
                    \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');

                    return $this->redirect(
                        $this->indexUrl
                    );
                }


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



    /**
     * @return string
     */
    public function getIndexUrl()
    {
        return UrlHelper::construct($this->id . '/' . $this->action->id)->enableAdmin()->setRoute('index')->normalizeCurrentRoute()->toString();
    }

}
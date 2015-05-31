<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions\modelEditor;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\filters\AccessControl;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\base\InvalidParamException;
use yii\behaviors\BlameableBehavior;
use yii\web\Response;

/**
 * Class AdminModelsGridAction
 * @package skeeks\cms\modules\admin\actions\modelEditor
 */
class AdminModelEditorCreateAction extends AdminModelEditorAction
{
    /**
     * @var bool
     */
    public $modelValidate = true;

    /**
     * @var string
     */
    public $modelScenario = "";

    public function init()
    {
        parent::init();

        $this->controller->attachBehavior('accessCreate',
        [
            'class'         => AccessControl::className(),
            'only'          => [$this->id],
            'rules'         =>
            [
                [
                    'allow'         => true,
                    'matchCallback' => function($rule, $action)
                    {
                        //Если такая привилегия заведена, нужно ее проверять.
                        if ($permission = \Yii::$app->authManager->getPermission(CmsManager::PERMISSION_ALLOW_MODEL_CREATE))
                        {
                            if (!\Yii::$app->user->can($permission->name))
                            {
                                return false;
                            }
                        }

                        return true;
                    }
                ],
            ],
        ]);
    }

    public function run()
    {
        $modelClassName = $this->controller->modelClassName;
        $model          = new $modelClassName();

        if ($scenarios = $model->scenarios() && $this->modelScenario)
        {
            if (isset($scenarios[$this->modelScenario]))
            {
                $model->scenario = $this->modelScenario;
            }
        }

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($model);
        }

        if ($rr->isRequestPjaxPost())
        {
            if ($model->load(\Yii::$app->request->post()) && $model->save($this->modelValidate))
            {
                \Yii::$app->getSession()->setFlash('success', 'Сохранено');

                if (\Yii::$app->request->post('submit-btn') == 'apply')
                {
                    return $this->controller->redirect(
                        UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute($this->controller->modelDefaultAction)->normalizeCurrentRoute()
                            ->addData([$this->controller->requestPkParamName => $model->{$this->controller->modelPkAttribute}])
                            ->toString()
                    );
                } else
                {
                    return $this->controller->redirect(
                        $this->controller->indexUrl
                    );
                }

            } else
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }
        }

        $this->viewParams =
        [
            'model' => $model
        ];

        return parent::run();
    }

    /**
     * Renders a view
     *
     * @param string $viewName view name
     * @return string result of the rendering
     */
    protected function render($viewName)
    {
        try
        {
            $output = parent::render($viewName);
        } catch (InvalidParamException $e)
        {
            $output = parent::render('_form');
        }

        return $output;
    }
}
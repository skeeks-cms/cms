<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentType;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorCreateAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiDialogModelEditAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use skeeks\cms\modules\admin\widgets\GridViewStandart;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\base\ActionEvent;
use yii\bootstrap\ActiveForm;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentElementController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name                     = "Элементы";
        $this->modelShowAttribute       = "name";
        $this->modelClassName           = CmsContentElement::className();

        parent::init();
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
                    "dataProviderCallback" => function(ActiveDataProvider $dataProvider)
                    {
                        $query = $dataProvider->query;
                        /**
                         * @var ActiveQuery $query
                         */
                        //$query->select(['app_company.*', 'count(`app_company_officer_user`.`id`) as countOfficer']);

                        $query->with('image');
                        $query->with('cmsTree');
                        $query->with('cmsContentElementTrees');
                        $query->with('cmsContentElementTrees.tree');
                    },
                ],

                "create" =>
                [
                    'class'         => AdminModelEditorCreateAction::className(),
                    "callback"      => [$this, 'create'],
                ],

                "update" =>
                [
                    'class'         => AdminOneModelEditAction::className(),
                    "callback"      => [$this, 'update'],
                ],

                /*'settings' =>
                [
                    'class'         => AdminModelEditorAction::className(),
                    'name'          => 'Настройки',
                    "icon"          => "glyphicon glyphicon-cog",
                ],*/

                "activate-multi" =>
                [
                    'class' => AdminMultiModelEditAction::className(),
                    "name" => "Активировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiActivate'],
                ],

                "inActivate-multi" =>
                [
                    'class' => AdminMultiModelEditAction::className(),
                    "name" => "Деактивировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiInActivate'],
                ],

                "change-tree-multi" =>
                [
                    'class'             => AdminMultiDialogModelEditAction::className(),
                    "name"              => "Основной раздел",
                    "viewDialog"        => "change-tree-form",
                    "eachCallback"      => [$this, 'eachMultiChangeTree'],
                ],

                "change-trees-multi" =>
                [
                    'class'             => AdminMultiDialogModelEditAction::className(),
                    "name"              => "Дополнительные разделы",
                    "viewDialog"        => "change-trees-form",
                    "eachCallback"      => [$this, 'eachMultiChangeTrees'],
                ],



            ]
        );
    }


    public function create(AdminAction $adminAction)
    {
        $modelClassName = $this->modelClassName;
        $model          = new $modelClassName();

        if ($content_id = \Yii::$app->request->get("content_id"))
        {
            $contentModel       = \skeeks\cms\models\CmsContent::findOne($content_id);
            $model->content_id  = $content_id;
        }

        $relatedModel = $model->relatedPropertiesModel;

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());

            return \yii\widgets\ActiveForm::validateMultiple([
                $model, $relatedModel
            ]);
        }


        if ($rr->isRequestPjaxPost())
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());

            if ($model->save() && $relatedModel->save())
            {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('app','Saved'));

                if (\Yii::$app->request->post('submit-btn') == 'apply')
                {
                    return $this->redirect(
                        UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute($this->modelDefaultAction)->normalizeCurrentRoute()
                            ->addData([$this->requestPkParamName => $model->{$this->modelPkAttribute}])
                            ->toString()
                    );
                } else
                {
                    return $this->redirect(
                        $this->indexUrl
                    );
                }

            } else
            {
                \Yii::$app->getSession()->setFlash('error', \Yii::t('app','Could not save'));
            }
        }

        return $this->render('_form', [
            'model'           => $model,
            'relatedModel'    => $relatedModel
        ]);
    }

    public function update(AdminAction $adminAction)
    {
        /**
         * @var $model CmsContentElement
         */
        $model = $this->model;
        $relatedModel = $model->relatedPropertiesModel;

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            return \yii\widgets\ActiveForm::validateMultiple([
                $model, $relatedModel
            ]);
        }

        if ($rr->isRequestPjaxPost())
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());

            if ($model->save() && $relatedModel->save())
            {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('app','Saved'));

                if (\Yii::$app->request->post('submit-btn') == 'apply')
                {

                } else
                {
                    return $this->redirect(
                        $this->indexUrl
                    );
                }

                $model->refresh();

            } else
            {
                $errors = [];

                if ($model->getErrors())
                {
                    foreach ($model->getErrors() as $error)
                    {
                        $errors[] = implode(', ', $error);
                    }
                }

                \Yii::$app->getSession()->setFlash('error', \Yii::t('app','Could not save') . $errors);
            }
        }

        return $this->render('_form', [
            'model'           => $model,
            'relatedModel'    => $relatedModel
        ]);
    }

    /**
     * @param CmsContentElement $model
     * @param $action
     * @return bool
     */
    public function eachMultiChangeTree($model, $action)
    {
        try
        {
            $formData = [];
            parse_str(\Yii::$app->request->post('formData'), $formData);
            $tmpModel = new CmsContentElement();
            $tmpModel->load($formData);
            if ($tmpModel->tree_id && $tmpModel->tree_id != $model->tree_id)
            {
                $model->tree_id = $tmpModel->tree_id;
                return $model->save(false);
            }

            return false;
        } catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * @param CmsContentElement $model
     * @param $action
     * @return bool
     */
    public function eachMultiChangeTrees($model, $action)
    {
        try
        {
            $formData = [];
            parse_str(\Yii::$app->request->post('formData'), $formData);
            $tmpModel = new CmsContentElement();
            $tmpModel->load($formData);

            if (ArrayHelper::getValue($formData, 'removeCurrent'))
            {
                $model->treeIds = [];
            }

            if ($tmpModel->treeIds)
            {
                $model->treeIds = array_merge($model->treeIds, $tmpModel->treeIds);
                $model->treeIds = array_unique($model->treeIds);
            }

            return $model->save(false);
        } catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * @var CmsContent
     */
    public $content;

    /**
     * @return string
     */
    public function getPermissionName()
    {
        if ($this->content)
        {
            return $this->content->adminPermissionName;
        }

        return parent::getPermissionName();
    }

    public function beforeAction($action)
    {
        if ($content_id = \Yii::$app->request->get('content_id'))
        {
            $this->content = CmsContent::findOne($content_id);
        }

        if ($this->content)
        {
            if ($this->content->name_meny)
            {
                $this->name = $this->content->name_meny;
            } else
            {
                $this->name = $this->content->name;
            }
        }

        return parent::beforeAction($action);
    }


    /**
     * @return string
     */
    public function getIndexUrl()
    {
        return UrlHelper::construct($this->id . '/' . $this->action->id, [
            'content_id' => \Yii::$app->request->get('content_id')
        ])->enableAdmin()->setRoute('index')->normalizeCurrentRoute()->toString();
    }

}

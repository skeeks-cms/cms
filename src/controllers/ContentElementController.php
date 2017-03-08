<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.04.2016
 */
namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\filters\CmsAccessControl;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Tree;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * @property CmsContentElement $model
 *
 * Class ContentElementController
 * @package skeeks\cms\controllers
 */
class ContentElementController extends Controller
{
    /**
     * @var CmsContentElement
     */
    public $_model = false;

    public function init()
    {
        if ($this->model && \Yii::$app->cmsToolbar)
        {
            $controller = \Yii::$app->createController('cms/admin-cms-content-element')[0];
            $adminControllerRoute = ['cms/admin-cms-content-element/update', $controller->requestPkParamName => $this->model->{$controller->modelPkAttribute}];

            $urlEditModel = UrlHelper::construct($adminControllerRoute)->enableAdmin()
                ->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true')->toString();

            \Yii::$app->cmsToolbar->editUrl = $urlEditModel;
        }

        parent::init();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'viewAccess' =>
            [
                'class'         => CmsAccessControl::className(),
                'only'          => ['view'],
                'rules'         =>
                [
                    [
                        'allow'         => true,
                        'matchCallback' => function($rule, $action)
                        {
                            if ($this->model->cmsContent && $this->model->cmsContent->access_check_element == 'Y')
                            {
                                //Если такая привилегия заведена, нужно ее проверять.
                                if ($permission = \Yii::$app->authManager->getPermission($this->model->permissionName))
                                {
                                    if (!\Yii::$app->user->can($permission->name))
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
    }

    /**
     * @return array|bool|null|CmsTree|\yii\db\ActiveRecord
     */
    public function getModel()
    {
        if ($this->_model !== false)
        {
            return $this->_model;
        }

        if (!$id = \Yii::$app->request->get('id'))
        {
            $this->_model = null;
            return false;
        }

        $this->_model = CmsContentElement::findOne(['id' => $id]);

        return $this->_model;
    }

    /**
     * @return $this|string
     * @throws NotFoundHttpException
     */
    public function actionView()
    {
        if (!$this->model) {
            throw new NotFoundHttpException(\Yii::t('skeeks/cms', 'Page not found'));
        }

        $contentElement     = $this->model;
        $tree               = $contentElement->cmsTree;

        if ($tree)
        {
            \Yii::$app->cms->setCurrentTree($tree);
            \Yii::$app->breadcrumbs->setPartsByTree($tree);

            \Yii::$app->breadcrumbs->append([
                'url'   => $contentElement->url,
                'name'  => $contentElement->name
            ]);
        }

        $viewFile = $this->action->id;

        $cmsContent = $this->model->cmsContent;
        if ($cmsContent)
        {
            if ($cmsContent->viewFile)
            {
                $viewFile = $cmsContent->viewFile;
            } else
            {
                $viewFile = $cmsContent->code;
            }
        }

        $this->_initStandartMetaData();

        return $this->render($viewFile, [
            'model' => $this->model
        ]);
    }

    /**
     *
     * TODO: Вынести в seo компонент
     *
     * Установка метаданных страницы
     * @return $this
     */
    protected function _initStandartMetaData()
    {
        $model = $this->model;

        if ($title = $model->meta_title)
        {
            $this->view->title = $title;
        } else
        {
            if (isset($model->name))
            {
                $this->view->title = $model->name;
            }
        }

        if ($meta_keywords = $model->meta_keywords)
        {
            $this->view->registerMetaTag([
                "name"      => 'keywords',
                "content"   => $meta_keywords
            ], 'keywords');
        }

        if ($meta_descripption = $model->meta_description)
        {
            $this->view->registerMetaTag([
                "name"      => 'description',
                "content"   => $meta_descripption
            ], 'description');
        }
        else
        {
            if (isset($model->name))
            {
                $this->view->registerMetaTag([
                    "name"      => 'description',
                    "content"   => $model->name
                ], 'description');
            }
        }

        return $this;
    }
}

<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (�����)
 * @date 14.04.2016
 */

namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsSavedFilter;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Tree;
use Yii;
use yii\web\NotFoundHttpException;

/**
 *
 * @property CmsSavedFilter $model
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class SavedFilterController extends Controller
{
    /**
     * @var CmsSavedFilter
     */
    public $_model = false;

    public function init()
    {
        if ($this->model && \Yii::$app->cmsToolbar) {
            $controller = \Yii::$app->createController('cms/admin-cms-saved-filter')[0];
            $adminControllerRoute = [
                '/cms/admin-cms-saved-filter/update',
                $controller->requestPkParamName => $this->model->{$controller->modelPkAttribute}
            ];

            $urlEditModel = \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams($adminControllerRoute)
                ->enableEmptyLayout()
                ->url;

            \Yii::$app->cmsToolbar->editUrl = $urlEditModel;
        }

        parent::init();
    }

    /**
     * @return array|bool|null|CmsSavedFilter
     */
    public function getModel()
    {
        if ($this->_model !== false) {
            return $this->_model;
        }

        if (!$id = \Yii::$app->request->get('id')) {
            $this->_model = null;
            return false;
        }

        if (!$this->_model) {
            $this->_model = CmsSavedFilter::find()->where([
                'id' => $id
            ])->one();
        }


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

        $cmsTree = $this->model->cmsTree;
        \Yii::$app->cms->setCurrentTree($cmsTree);
        \Yii::$app->breadcrumbs->setPartsByTree($cmsTree)->append([
            'name' => $this->model->name ? $this->model->name : $this->model->seoName,
            'url' => $this->model->url,
        ]);

        $viewFile = $this->action->id;

        if ($cmsTree) {
            if ($cmsTree->view_file) {
                $viewFile = $cmsTree->view_file;
            } else {
                if ($cmsTree->treeType) {
                    if ($cmsTree->treeType->view_file) {
                        $viewFile = $cmsTree->treeType->view_file;
                    } else {
                        $viewFile = $cmsTree->treeType->code;
                    }
                }
            }
        }

        $viewFile = "@app/views/modules/cms/tree/" . $viewFile;

        $this->_initStandartMetaData();

        $cmsTree->description_short = $this->model->description_short;
        $cmsTree->description_full = $this->model->description_full;

        $cmsTree->name = $this->model->seoName;
        $cmsTree->seoName = $this->model->seoName;

        return $this->render($viewFile, [
            'model' => $cmsTree,
            'savedFilter' => $this->model
        ]);
    }

    /**
     * @return $this
     */
    protected function _initStandartMetaData()
    {
        $model = $this->model;

        if ($title = $model->meta_title) {
            $this->view->title = $title;
        } else {
            if (isset($model->seoName)) {
                $this->view->title = $model->seoName;
            }
        }

        if ($meta_keywords = $model->meta_keywords) {
            $this->view->registerMetaTag([
                "name" => 'keywords',
                "content" => $meta_keywords
            ], 'keywords');
        }

        if ($meta_descripption = $model->meta_description) {
            $this->view->registerMetaTag([
                "name" => 'description',
                "content" => $meta_descripption
            ], 'description');
        } else {
            if (isset($model->seoName)) {
                $this->view->registerMetaTag([
                    "name" => 'description',
                    "content" => $model->seoName
                ], 'description');
            }
        }

        return $this;
    }
}

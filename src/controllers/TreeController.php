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
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Tree;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * @property CmsTree $model
 *
 * Class TreeController
 * @package skeeks\cms\controllers
 */
class TreeController extends Controller
{
    /**
     * @var CmsTree
     */
    public $_model = false;

    public function init()
    {
        if ($this->model && \Yii::$app->cmsToolbar) {
            $controller = \Yii::$app->createController('cms/admin-tree')[0];
            $adminControllerRoute = [
                '/cms/admin-tree/update',
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
     * @return array|bool|null|CmsTree|\yii\db\ActiveRecord
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

        $this->_model = CmsTree::find()->where([
            'id' => $id
        ])->one();

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

        \Yii::$app->cms->setCurrentTree($this->model);
        \Yii::$app->breadcrumbs->setPartsByTree($this->model);

        if ($this->model->redirect || $this->model->redirect_tree_id) {
            return \Yii::$app->response->redirect($this->model->url, $this->model->redirect_code);
        }

        $viewFile = $this->action->id;
        if ($this->model) {
            if ($this->model->view_file) {
                $viewFile = $this->model->view_file;

            } else {
                if ($this->model->treeType) {
                    if ($this->model->treeType->view_file) {
                        $viewFile = $this->model->treeType->view_file;
                    } else {
                        $viewFile = $this->model->treeType->code;
                    }
                }
            }
        }

        $this->_initStandartMetaData();

        return $this->render($viewFile, [
            'model' => $this->model
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
            if (isset($model->name)) {
                $this->view->title = $model->name;
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
            if (isset($model->name)) {
                $this->view->registerMetaTag([
                    "name" => 'description',
                    "content" => $model->name
                ], 'description');
            }
        }

        return $this;
    }
}

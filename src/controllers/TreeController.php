<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\base\Controller;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsTree;
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

        $this->_model = \Yii::$app->cms->currentTree;
        if (!$this->_model) {
            $this->_model = CmsTree::find()->where([
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

        \Yii::$app->cms->setCurrentTree($this->model);
        \Yii::$app->breadcrumbs->setPartsByTree($this->model);

        if ($this->model->redirect || $this->model->redirect_tree_id) {
            return \Yii::$app->response->redirect($this->model->url, $this->model->redirect_code);
        }

        if ($this->model->isCanonical) {
            \Yii::$app->seo->setCanonical($this->model->canonicalUrl);
        }

        if (!$this->model->isAllowIndex) {
            \Yii::$app->seo->setNoIndexNoFollow();
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

        //Заголовок
        if (!$title = $model->meta_title) {
            if (isset($model->seoName)) {
                $title = $model->seoName;
            }
        }

        $this->view->title = $title;
        $this->view->registerMetaTag([
            'property' => 'og:title',
            'content'  => $title,
        ], 'og:title');

        //Ключевые слова
        if ($meta_keywords = $model->meta_keywords) {
            $this->view->registerMetaTag([
                "name" => 'keywords',
                "content" => $meta_keywords
            ], 'keywords');
        }


        //Описание
        if ($meta_descripption = $model->meta_description) {
            $description = $meta_descripption;
        } elseif ($model->description_short) {
            $description = $model->description_short;
        } else {
            if (isset($model->name)) {
                if ($model->name != $model->seoName) {
                    $description = $model->seoName;
                } else {
                    $description = $model->name;
                }
            }
        }

        $description = trim(strip_tags($description));

        $this->view->registerMetaTag([
            "name" => 'description',
            "content" => $description
        ], 'description');

        $this->view->registerMetaTag([
            'property' => 'og:description',
            'content'  => $description,
        ], 'og:description');

        //Картика
        $imageAbsoluteSrc = null;
        if ($model->image_id) {
            $imageAbsoluteSrc = $model->image->absoluteSrc;
        } elseif ($model->image_full_id) {
            $imageAbsoluteSrc = $model->fullImage->absoluteSrc;
        }
        if ($imageAbsoluteSrc) {
            $this->view->registerMetaTag([
                'property' => 'og:image',
                'content'  => $imageAbsoluteSrc,
            ], 'og:image');
        }


        $this->view->registerMetaTag([
            'property' => 'og:url',
            'content'  => $model->getUrl(true),
        ], 'og:url');

        $this->view->registerMetaTag([
            'property' => 'og:type',
            'content'  => 'website',
        ], 'og:type');

        return $this;
    }
}

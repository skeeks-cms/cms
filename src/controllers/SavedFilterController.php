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

        $cmsTree = clone $this->model->cmsTree;

        \Yii::$app->cms->setCurrentTree($cmsTree);
        \Yii::$app->breadcrumbs->setPartsByTree($cmsTree)->append([
            'name' => $this->model->name,
            'url' => $this->model->url,
        ]);

        $viewFile = $this->action->id;

        if (!$this->model->isAllowIndex) {
            \Yii::$app->seo->setNoIndexNoFollow();
        }

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

        $result = $this->render($viewFile, [
            'model' => $cmsTree,
            'savedFilter' => $this->model
        ]);


        return $result;
    }

    /**
     * @return $this
     */
    protected function _initStandartMetaData()
    {
        /**
         * @var $model CmsSavedFilter
         */
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
        if ($model->image) {
            $imageAbsoluteSrc = $model->image->absoluteSrc;
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

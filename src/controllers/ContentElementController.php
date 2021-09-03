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
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\models\CmsSavedFilter;
use skeeks\cms\models\CmsTree;
use skeeks\cms\relatedProperties\PropertyType;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
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

    /**
     * @var string
     */
    public $modelClassName = CmsContentElement::class;

    /**
     * @var string
     */
    public $editControllerRoute = "cms/admin-cms-content-element";

    public function init()
    {
        parent::init();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'viewAccess' => [
                'class' => CmsAccessControl::className(),
                'only'  => ['view'],
                'rules' =>
                    [
                        [
                            'allow'         => true,
                            'matchCallback' => function ($rule, $action) {
                                if ($this->model && $this->model->cmsContent && $this->model->cmsContent->is_access_check_element) {
                                    //Если такая привилегия заведена, нужно ее проверять.
                                    if ($permission = \Yii::$app->authManager->getPermission($this->model->permissionName)) {
                                        if (!\Yii::$app->user->can($permission->name)) {
                                            return false;
                                        }
                                    }
                                }

                                return true;
                            },
                        ],
                    ],
            ],
        ]);
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

        $modelClassName = $this->modelClassName;
        $this->_model = $modelClassName::findOne(['id' => $id]);

        return $this->_model;
    }

    /**
     * @param $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->_model = $model;
        return $this;
    }

    public function beforeAction($action)
    {
        if ($this->model && \Yii::$app->cmsToolbar) {
            $controller = \Yii::$app->createController($this->editControllerRoute)[0];
            $adminControllerRoute = [
                '/'.$this->editControllerRoute.'/' . $controller->modelDefaultAction,
                $controller->requestPkParamName => $this->model->{$controller->modelPkAttribute},
            ];

            $urlEditModel = \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams($adminControllerRoute)
                ->enableEmptyLayout()
                ->url;

            \Yii::$app->cmsToolbar->editUrl = $urlEditModel;
        }

        return parent::beforeAction($action);
    }

    protected function _getSavedFilter()
    {
        $contentElement = $this->model;
        if (!$contentElement->cmsContent) {
            return false;
        }


        if (!$contentElement->cmsContent->saved_filter_tree_type_id) {
            return false;
        }

        $mainCmsTree = CmsTree::find()
            ->cmsSite()
            ->andWhere(['tree_type_id' => $contentElement->cmsContent->saved_filter_tree_type_id])
            ->orderBy(['level' => SORT_ASC, 'priority' => SORT_ASC])
            ->limit(1)
            ->one();

        if (!$mainCmsTree) {
            return false;
        }

        $savedFilter = CmsSavedFilter::find()->cmsSite()
            ->andWhere([
                'cms_tree_id' => $mainCmsTree->id,
            ])
            ->andWhere([
                'value_content_element_id' => $contentElement->id,
            ])
            ->one();
        ;

        if ($savedFilter) {
            return $savedFilter;
        }

        //Создать сохраненный фильтр
        //Нужно определить свойство в этом разделе
            $q = CmsContentProperty::find()->cmsSite();

            $q->joinWith('cmsContentProperty2trees as map2trees')
                ->groupBy(\skeeks\cms\models\CmsContentProperty::tableName().".id");

            $q->andWhere([
                'or',
                ['map2trees.cms_tree_id' => $mainCmsTree->id],
                ['map2trees.cms_tree_id' => null],
            ]);
            $q->orderBy(['priority' => SORT_ASC])
            ;
        /**
         * @var $cmsContentProperty CmsContentProperty
         */
        $property = null;
        foreach ($q->each(10) as $cmsContentProperty)
        {
            if ($cmsContentProperty->property_type == PropertyType::CODE_ELEMENT) {
                if ($cmsContentProperty->handler->content_id == $contentElement->cmsContent->id) {
                    $property = $cmsContentProperty;
                    break;
                }
            }
        }

        if ($property) {
            $savedFilter = new CmsSavedFilter();
            $savedFilter->cms_tree_id = $mainCmsTree->id;
            $savedFilter->value_content_element_id = $contentElement->id;

            $savedFilter->cms_content_property_id = $property->id;
            if (!$savedFilter->save()) {
                $savedFilter = null;
            }
            
            return $savedFilter;
        }
            


        return false;
    }
    /**
     * @return $this|string
     * @throws NotFoundHttpException
     */
    public function actionView()
    {
        if (!$this->model) {
            throw new NotFoundHttpException(\Yii::t('skeeks/cms', 'Page not found: '.\Yii::$app->request->absoluteUrl));
        }

        $contentElement = $this->model;
        $tree = $contentElement->cmsTree;

        $this->_getSavedFilter();

        //TODO: Может быть не сбрасывать GET параметры
        if (Url::isRelative($contentElement->url)) {

            $url = \Yii::$app->request->absoluteUrl;
            if ($pos = strpos($url, '?')) {
                $url = substr($url, 0, $pos);
            }

            if ($contentElement->getUrl(true) != $url) {
                $url = $contentElement->getUrl(true);
                \Yii::$app->response->redirect($url, 301);
                \Yii::$app->end();
            }
        } else {

            if ($urlData = parse_url($contentElement->getUrl(true))) {
                $url = \Yii::$app->request->absoluteUrl;
                if ($pos = strpos($url, '?')) {
                    $url = substr($url, 0, $pos);
                }
                $requestUrlData = parse_url($url);

                if (ArrayHelper::getValue($urlData, 'path') != ArrayHelper::getValue($requestUrlData, 'path')) {
                    $url = $contentElement->getUrl(true);
                    \Yii::$app->response->redirect($url, 301);
                    \Yii::$app->end();
                }
            }
        }


        if ($tree) {
            \Yii::$app->cms->setCurrentTree($tree);
            \Yii::$app->breadcrumbs->setPartsByTree($tree);

            \Yii::$app->breadcrumbs->append([
                'url'  => $contentElement->url,
                'name' => $contentElement->name,
            ]);
        }

        $viewFile = $this->action->id;

        $cmsContent = $this->model->cmsContent;
        if ($cmsContent) {

            //Если элементы этого контента не разрешено показывать на всех сайтах, то нужно проверить соответствие сайта.
            if (!$cmsContent->is_show_on_all_sites) {
                if ($this->model->cms_site_id != \Yii::$app->skeeks->site->id) {
                    throw new NotFoundHttpException("Элемент не найден");
                }
            }

            if ($cmsContent->view_file) {
                $viewFile = $cmsContent->view_file;
            } else {
                $viewFile = $cmsContent->code;
            }

            /**
             * У этого контента нужно считать количество просмотров
             */
            if ($cmsContent->is_count_views) {
                $model = $this->model;
                $model->show_counter = $model->show_counter + 1;

                //TODO:это сбрасывает кэш таблицы ActiveRecord.php
                //$model->update(false, ['show_counter']);
                CmsContentElement::updateAll(['show_counter' => $model->show_counter], ['id' => $model->id]);
            }
        }

        $this->_initStandartMetaData();

        return $this->render($viewFile, [
            'model' => $this->model,
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
            'content'  => 'article',
        ], 'og:type');

        return $this;
    }
}

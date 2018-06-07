<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\IHasUrl;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorAction;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\yii2\form\fields\BoolField;
use yii\base\Event;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\web\Application;

/**
 * @property CmsContent|static $content
 *
 * Class AdminCmsContentTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsContentElementController extends BackendModelStandartController
{
    public $notSubmitParam = 'sx-not-submit';

    public $modelClassName = CmsContentElement::class;
    public $modelShowAttribute = "name";
    /**
     * @var CmsContent
     */
    protected $_content = null;


    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Elements');
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index'  => [
                "filters" => [
                    'visibleFilters' => [
                        'id',
                        'name',
                        'active',
                    ],
                    'filtersModel'   => [
                        'fields' => [

                            'active' => [
                                'field' => [
                                    'class'      => BoolField::class,
                                    'trueValue'  => 'Y',
                                    'falseValue' => 'N',
                                ],
                                'defaultMode' => FilterModeEq::ID,
                                'isAllowChangeMode' => false,
                            ],
                        ],
                    ],
                ],
                'grid'    => [
                    'on init'        => function (Event $event) {
                        /**
                         * @var $query ActiveQuery
                         */
                        $query = $event->sender->dataProvider->query;
                        if ($this->content) {
                            $query->andWhere(['content_id' => $this->content->id]);
                        }
                    },
                    'defaultOrder'   => [
                        'id' => SORT_DESC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        'image_id',
                        'name',
                        'tree_id',
                        'active',
                        'published_at',
                        'priority',
                    ],
                    'columns'        => [
                        'active'       => [
                            'class' => BooleanColumn::class,
                        ],
                        'image_id'     => [
                            'class' => ImageColumn2::class,
                        ],
                        'published_at' => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'created_at'   => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'updated_at'   => [
                            'class' => DateTimeColumnData::class,
                        ],
                    ],
                ],
            ],
            "create" => [
                "callback" => [$this, 'create'],
            ],
            "update" => [
                "callback" => [$this, 'update'],
            ],

            "activate-multi" => [
                'class' => BackendModelMultiActivateAction::class,
            ],

            "deactivate-multi" => [
                'class' => BackendModelMultiDeactivateAction::class,
            ],
        ]);
    }


    public function create($adminAction)
    {
        $modelClassName = $this->modelClassName;
        $model = new $modelClassName;

        $model->loadDefaultValues();

        if ($content_id = \Yii::$app->request->get("content_id")) {
            $contentModel = \skeeks\cms\models\CmsContent::findOne($content_id);
            $model->content_id = $content_id;
        }

        $relatedModel = $model->relatedPropertiesModel;
        $relatedModel->loadDefaultValues();

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());

            return \yii\widgets\ActiveForm::validateMultiple([
                $model,
                $relatedModel,
            ]);
        }

        if ($post = \Yii::$app->request->post()) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
        }

        if ($rr->isRequestPjaxPost()) {
            if (!\Yii::$app->request->post($this->notSubmitParam)) {
                $model->load(\Yii::$app->request->post());
                $relatedModel->load(\Yii::$app->request->post());

                if ($model->save() && $relatedModel->save()) {
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Saved'));

                    if (\Yii::$app->request->post('submit-btn') == 'apply') {
                        $url = '';
                        $this->model = $model;

                        if ($this->modelActions) {
                            if ($action = ArrayHelper::getValue($this->modelActions, $this->modelDefaultAction)) {
                                $url = $action->url;
                            }
                        }

                        if (!$url) {
                            $url = $this->url;
                        }

                        return $this->redirect($url);
                    } else {
                        return $this->redirect(
                            $this->url
                        );
                    }
                }
            }

        }

        return $this->render('_form', [
            'model'        => $model,
            'relatedModel' => $relatedModel,
        ]);
    }
    public function update($adminAction)
    {
        /**
         * @var $model CmsContentElement
         */
        $model = $this->model;
        $relatedModel = $model->relatedPropertiesModel;

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            return \yii\widgets\ActiveForm::validateMultiple([
                $model,
                $relatedModel,
            ]);
        }

        if ($post = \Yii::$app->request->post()) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
        }

        if ($rr->isRequestPjaxPost()) {
            if (!\Yii::$app->request->post($this->notSubmitParam)) {
                $model->load(\Yii::$app->request->post());
                $relatedModel->load(\Yii::$app->request->post());

                if ($model->save() && $relatedModel->save()) {
                    \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Saved'));

                    if (\Yii::$app->request->post('submit-btn') == 'apply') {
                    } else {
                        return $this->redirect(
                            $this->url
                        );
                    }

                    $model->refresh();

                }
            }

        }

        return $this->render('_form', [
            'model'        => $model,
            'relatedModel' => $relatedModel,
        ]);
    }
    /**
     * @param CmsContentElement $model
     * @param                   $action
     * @return bool
     */
    public function eachMultiChangeTree($model, $action)
    {
        try {
            $formData = [];
            parse_str(\Yii::$app->request->post('formData'), $formData);
            $tmpModel = new CmsContentElement();
            $tmpModel->load($formData);
            if ($tmpModel->tree_id && $tmpModel->tree_id != $model->tree_id) {
                $model->tree_id = $tmpModel->tree_id;
                return $model->save(false);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function eachRelatedProperties($model, $action)
    {
        try {
            $formData = [];
            parse_str(\Yii::$app->request->post('formData'), $formData);

            if (!$formData) {
                return false;
            }

            if (!$content_id = ArrayHelper::getValue($formData, 'content_id')) {
                return false;
            }

            if (!$fields = ArrayHelper::getValue($formData, 'fields')) {
                return false;
            }


            /**
             * @var CmsContent $content
             */
            $content = CmsContent::findOne($content_id);
            if (!$content) {
                return false;
            }


            $element = $content->createElement();
            $relatedProperties = $element->relatedPropertiesModel;
            $relatedProperties->load($formData);
            /**
             * @var $model CmsContentElement
             */
            $rpForSave = $model->relatedPropertiesModel;

            foreach ((array)ArrayHelper::getValue($formData, 'fields') as $code) {
                if ($rpForSave->hasAttribute($code)) {
                    $rpForSave->setAttribute($code,
                        ArrayHelper::getValue($formData, 'RelatedPropertiesModel.'.$code));
                }
            }

            return $rpForSave->save(false);
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * @param CmsContentElement $model
     * @param                   $action
     * @return bool
     */
    public function eachMultiChangeTrees($model, $action)
    {
        try {
            $formData = [];
            parse_str(\Yii::$app->request->post('formData'), $formData);
            $tmpModel = new CmsContentElement();
            $tmpModel->load($formData);

            if (ArrayHelper::getValue($formData, 'removeCurrent')) {
                $model->treeIds = [];
            }

            if ($tmpModel->treeIds) {
                $model->treeIds = array_merge($model->treeIds, $tmpModel->treeIds);
                $model->treeIds = array_unique($model->treeIds);
            }

            return $model->save(false);
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * @return string
     */
    public function getPermissionName()
    {
        $unique = parent::getPermissionName();

        if ($this->content) {
            $unique = $unique."__".$this->content->id;
        }

        return $unique;
    }
    /**
     * @return CmsContent|static
     */
    public function getContent()
    {
        if ($this->_content === null) {
            if ($this->model) {
                $this->_content = $this->model->cmsContent;
                return $this->_content;
            }

            if (\Yii::$app instanceof Application && \Yii::$app->request->get('content_id')) {
                $content_id = \Yii::$app->request->get('content_id');

                $dependency = new TagDependency([
                    'tags' =>
                        [
                            (new CmsContent())->getTableCacheTag(),
                        ],
                ]);

                $this->_content = CmsContent::getDb()->cache(function ($db) use ($content_id) {
                    return CmsContent::find()->where([
                        "id" => $content_id,
                    ])->one();
                }, null, $dependency);

                return $this->_content;
            }
        }

        return $this->_content;
    }
    /**
     * @param $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }
    public function getModelActions()
    {
        /**
         * @var AdminAction $action
         */
        $actions = parent::getModelActions();
        if ($actions) {
            foreach ($actions as $action) {
                $action->url = ArrayHelper::merge($action->urlData,
                    ['content_id' => $this->content ? $this->content->id : ""]);
            }
        }

        return $actions;
    }
    public function beforeAction($action)
    {
        if ($this->content) {
            if ($this->content->name_meny) {
                $this->name = $this->content->name_meny;
            } else {
                $this->name = $this->content->name;
            }
        }

        return parent::beforeAction($action);
    }
    /**
     * @return string
     */
    public function getUrl()
    {
        $actions = $this->getActions();
        $index = ArrayHelper::getValue($actions, 'index');
        if ($index && $index instanceof IHasUrl) {
            return $index->url;
        }

        return '';
    }
    public function getActions()
    {
        /**
         * @var AdminAction $action
         */
        $actions = parent::getActions();
        if ($actions) {
            foreach ($actions as $action) {
                if ($this->content) {
                    $action->url = ArrayHelper::merge($action->urlData, ['content_id' => $this->content->id]);
                }
            }
        }

        return $actions;
    }
}

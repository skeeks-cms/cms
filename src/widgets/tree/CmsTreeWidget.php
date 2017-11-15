<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.12.2016
 */

namespace skeeks\cms\widgets\tree;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTree;
use skeeks\cms\widgets\tree\assets\CmsTreeWidgetAsset;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * @property int[] $openedIds
 *
 * Class CmsTreeWidget
 * @package skeeks\cms\widgets\tree
 */
class CmsTreeWidget extends Widget
{
    public static $autoIdPrefix = 'cmsTreeWidget';

    /**
     * @var array Widget wrapper options
     */
    public $options = [];

    /**
     * @var array Nodes for which to build a tree.
     */
    public $models = [];

    /**
     * @var string Widget session param name
     */
    public $sessionName = "cms-tree-opened";

    /**
     * @var string
     */
    public $openedRequestName = "o";

    /**
     * @var string Widget view file
     */
    public $viewFile = 'tree';
    /**
     * @var string Widget one node view file
     */
    public $viewNodeFile = '_node';
    /**
     * @var string Inner node content file
     */
    public $viewNodeContentFile = '_node-content';

    /**
     * @var array Additional information in the context of a call widget
     */
    public $contextData = [];

    /**
     * @var \yii\widgets\Pjax
     */
    public $pjax = null;
    public $pjaxClass = 'skeeks\cms\widgets\Pjax';
    public $pjaxOptions = [
        'isBlock' => true
    ];


    protected $_pjaxIsStart = false;

    public function init()
    {
        parent::init();

        $this->options['id'] = $this->id;
        Html::addCssClass($this->options, 'sx-tree');

        //Automatic filling models
        if ($this->models !== false && is_array($this->models) && count($this->models) == 0) {
            $this->models = CmsTree::findRoots()
                ->joinWith('cmsSiteRelation')
                ->orderBy([CmsSite::tableName() . ".priority" => SORT_ASC])->all();
        }

        $this->_beginPjax();
    }

    /**
     * @return array
     */
    public function getOpenedIds()
    {
        $opened = [];

        if ($fromRequest = (array)\Yii::$app->request->getQueryParam($this->openedRequestName)) {
            $opened = array_unique($fromRequest);
            if ($this->sessionName) {
                \Yii::$app->getSession()->set($this->sessionName, $opened);
            }
        } else {
            if ($this->sessionName) {
                $opened = array_unique(\Yii::$app->getSession()->get($this->sessionName, []));
            }
        }

        return $opened;
    }


    /**
     * @return string
     */
    public function run()
    {
        $this->registerAssets();
        echo $this->render($this->viewFile);
        $this->_endPjax();
    }

    /**
     * @return $this
     */
    protected function _beginPjax()
    {
        if (!$this->pjax) {
            $pjaxClass = $this->pjaxClass;
            $pjaxOptions = ArrayHelper::merge($this->pjaxOptions, [
                'id' => 'sx-pjax-' . $this->id,
                //'enablePushState' => false,
            ]);
            $this->_pjaxIsStart = true;
            $this->pjax = $pjaxClass::begin($pjaxOptions);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function _endPjax()
    {
        if ($this->_pjaxIsStart === true) {
            $className = $this->pjax->className();
            $className::end();
        }

        return $this;
    }

    /**
     * @param $models
     * @return string
     */
    public function renderNodes($models)
    {
        $options["item"] = [$this, 'renderNode'];
        $ul = Html::ul($models, $options);

        return $ul;
    }

    /**
     * @param $model
     * @return string
     */
    public function renderNode($model)
    {
        return $this->render($this->viewNodeFile, [
            'model' => $model,
        ]);
    }

    /**
     * @param $model
     * @return string
     */
    public function renderNodeContent($model)
    {
        return $this->render($this->viewNodeContentFile, [
            'model' => $model,
        ]);
    }


    /**
     * @param $model
     * @return $this|string
     */
    public function getOpenCloseLink($model)
    {
        $currentLink = "";

        if ($model->children) {
            $openedIds = $this->openedIds;

            if ($this->isOpenNode($model)) {
                $newOptionsOpen = [];
                foreach ($openedIds as $id) {
                    if ($id != $model->id) {
                        $newOptionsOpen[] = $id;
                    }
                }

                $urlOptionsOpen = array_unique($newOptionsOpen);
                $params = \Yii::$app->request->getQueryParams();
                $pathInfo = \Yii::$app->request->pathInfo;
                $params[$this->openedRequestName] = $urlOptionsOpen;

                $currentLink = "/{$pathInfo}?" . http_build_query($params);
            } else {
                $urlOptionsOpen = array_unique(array_merge($openedIds, [$model->id]));
                $params = \Yii::$app->request->getQueryParams();
                $params[$this->openedRequestName] = $urlOptionsOpen;
                $pathInfo = \Yii::$app->request->pathInfo;

                $currentLink = "/{$pathInfo}?" . http_build_query($params);
            }
        }

        return $currentLink;
    }

    /**
     * Нода для этой модели открыта?
     *
     * @param $model
     * @return bool
     */
    public function isOpenNode($model)
    {
        $isOpen = false;

        if ($openedIds = (array)$this->openedIds) {
            if (in_array($model->id, $openedIds)) {
                $isOpen = true;
            }
        }

        return $isOpen;
    }

    /**
     *
     *
     * @param $model
     * @return string
     */
    public function getNodeName($model)
    {
        /**
         * @var $model \skeeks\cms\models\Tree
         */

        $result = $model->name;

        $additionalName = '';
        if ($model->level == 0) {
            $site = CmsSite::findOne(['id' => $model->cms_site_id]);
            if ($site) {
                $additionalName = $site->name;
            }
        } else {
            if ($model->name_hidden) {
                $additionalName = $model->name_hidden;
            }
        }

        if ($additionalName) {
            $result .= " [{$additionalName}]";
        }

        return $result;
    }


    public function registerAssets()
    {
        $options = Json::encode([
            'id' => $this->id,
            'pjaxid' => $this->pjax->id
        ]);

        CmsTreeWidgetAsset::register($this->getView());
        $this->getView()->registerJs(<<<JS

        (function(window, sx, $, _)
        {
            sx.createNamespace('classes.tree', sx);

            sx.classes.tree.CmsTreeWidget = sx.classes.Component.extend({

                _init: function()
                {
                    var self = this;
                },

                _onDomReady: function()
                {
                    var self = this;
                },
            });

            new sx.classes.tree.CmsTreeWidget({$options});

        })(window, sx, sx.$, sx._);
JS
        );

        $this->getView()->registerCss(<<<CSS


CSS
        );
    }
}
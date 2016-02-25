<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */
namespace skeeks\cms\cmsWidgets\filters;

use skeeks\cms\base\Widget;
use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\cmsWidgets\filters\models\SearchProductsModel;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\models\searchs\SearchRelatedPropertiesModel;
use skeeks\cms\shop\models\ShopTypePrice;
use yii\base\DynamicModel;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * @property CmsContent         $cmsContent;
 *
 * Class ShopProductFiltersWidget
 * @package skeeks\cms\cmsWidgets\filters
 */
class ContentElementFiltersWidget extends WidgetRenderable
{
    //Навигация
    public $content_id;
    public $searchModelAttributes       = [];

    public $realatedProperties          = [];

    /**
     * @var array (Массив ids записей, для показа только нужных фильтров)
     */
    public $elementIds          = [];

    /**
     * @var SearchProductsModel
     */
    public $searchModel                 = null;

    /**
     * @var SearchRelatedPropertiesModel
     */
    public $searchRelatedPropertiesModel  = null;

    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name'          => 'Фильтры',
        ]);
    }

    public function init()
    {
        parent::init();

        if (!$this->searchModelAttributes)
        {
            $this->searchModelAttributes = [];
        }

        if (!$this->searchModel)
        {
            $this->searchModel = new \skeeks\cms\cmsWidgets\filters\models\SearchProductsModel();
        }

        if (!$this->searchRelatedPropertiesModel && $this->cmsContent)
        {
            $this->searchRelatedPropertiesModel = new SearchRelatedPropertiesModel();
            $this->searchRelatedPropertiesModel->initCmsContent($this->cmsContent);
        }

        $this->searchModel->load(\Yii::$app->request->get());

        if ($this->searchRelatedPropertiesModel)
        {
            $this->searchRelatedPropertiesModel->load(\Yii::$app->request->get());
        }
    }




    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'content_id'                => \Yii::t('app', 'Content'),
            'searchModelAttributes'     => \Yii::t('app', 'Fields'),
            'realatedProperties'        => \Yii::t('app', 'Properties'),
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            [['content_id'], 'integer'],
            [['searchModelAttributes'], 'safe'],
            [['realatedProperties'], 'safe'],
        ]);
    }
    /**
     * @return CmsContent
     */
    public function getCmsContent()
    {
        return CmsContent::findOne($this->content_id);
    }

    /**
     * @param ActiveDataProvider $activeDataProvider
     */
    public function search(ActiveDataProvider $activeDataProvider)
    {
        $this->searchModel->search($activeDataProvider);

        if ($this->searchRelatedPropertiesModel)
        {
            $this->searchRelatedPropertiesModel->search($activeDataProvider);
        }
    }
}
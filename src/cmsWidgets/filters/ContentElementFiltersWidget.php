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
use yii\widgets\ActiveForm;

/**
 * @property CmsContent $cmsContent;
 *
 * Class ShopProductFiltersWidget
 * @package skeeks\cms\cmsWidgets\filters
 */
class ContentElementFiltersWidget extends WidgetRenderable
{
    //Навигация
    public $content_id;
    public $searchModelAttributes = [];

    public $realatedProperties = [];

    /**
     * @var bool Учитывать только доступные фильтры для текущей выборки
     */
    public $onlyExistsFilters = false;
    /**
     * @var array (Массив ids записей, для показа только нужных фильтров)
     */
    public $elementIds = [];


    /**
     * @var SearchProductsModel
     */
    public $searchModel = null;

    /**
     * @var SearchRelatedPropertiesModel
     */
    public $searchRelatedPropertiesModel = null;

    public static function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Фильтры',
        ]);
    }

    public function init()
    {
        parent::init();

        if (!$this->searchModelAttributes) {
            $this->searchModelAttributes = [];
        }

        if (!$this->searchModel) {
            $this->searchModel = new \skeeks\cms\cmsWidgets\filters\models\SearchProductsModel();
        }

        if (!$this->searchRelatedPropertiesModel && $this->cmsContent) {
            $this->searchRelatedPropertiesModel = new SearchRelatedPropertiesModel();
            $this->searchRelatedPropertiesModel->initCmsContent($this->cmsContent);
        }

        $this->searchModel->load(\Yii::$app->request->get());

        if ($this->searchRelatedPropertiesModel) {
            $this->searchRelatedPropertiesModel->load(\Yii::$app->request->get());
        }
    }


    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
            [
                'content_id' => \Yii::t('skeeks/cms', 'Content'),
                'searchModelAttributes' => \Yii::t('skeeks/cms', 'Fields'),
                'realatedProperties' => \Yii::t('skeeks/cms', 'Properties'),
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

    public function renderConfigForm(ActiveForm $form)
    {
        echo \Yii::$app->view->renderFile(__DIR__ . '/_form.php', [
            'form' => $form,
            'model' => $this
        ], $this);
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
        if ($this->onlyExistsFilters) {
            /**
             * @var $query \yii\db\ActiveQuery
             */
            $query = clone $activeDataProvider->query;

            $query->with = [];
            $query->select(['cms_content_element.id as mainId', 'cms_content_element.id as id'])->indexBy('mainId');
            $ids = $query->asArray()->all();

            $this->elementIds = array_keys($ids);
        }

        $this->searchModel->search($activeDataProvider);

        if ($this->searchRelatedPropertiesModel) {
            $this->searchRelatedPropertiesModel->search($activeDataProvider);
        }
    }


    /**
     *
     * Получение доступных опций для свойства
     * @param CmsContentProperty $property
     * @return $this|array|\yii\db\ActiveRecord[]
     */
    public function getRelatedPropertyOptions($property)
    {
        $options = [];

        if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT) {
            $propertyType = $property->handler;

            if ($this->elementIds) {
                $availables = \skeeks\cms\models\CmsContentElementProperty::find()
                    ->select(['value_enum'])
                    ->indexBy('value_enum')
                    ->andWhere(['element_id' => $this->elementIds])
                    ->andWhere(['property_id' => $property->id])
                    ->asArray()
                    ->all();

                $availables = array_keys($availables);
            }

            $options = \skeeks\cms\models\CmsContentElement::find()
                ->active()
                ->andWhere(['content_id' => $propertyType->content_id]);
            if ($this->elementIds) {
                $options->andWhere(['id' => $availables]);
            }

            $options = $options->select(['id', 'name'])->asArray()->all();

            $options = \yii\helpers\ArrayHelper::map(
                $options, 'id', 'name'
            );

        } elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_LIST) {
            $options = $property->getEnums()->select(['id', 'value']);

            if ($this->elementIds) {
                $availables = \skeeks\cms\models\CmsContentElementProperty::find()
                    ->select(['value_enum'])
                    ->indexBy('value_enum')
                    ->andWhere(['element_id' => $this->elementIds])
                    ->andWhere(['property_id' => $property->id])
                    ->asArray()
                    ->all();

                $availables = array_keys($availables);
                $options->andWhere(['id' => $availables]);
            }

            $options = $options->asArray()->all();

            $options = \yii\helpers\ArrayHelper::map(
                $options, 'id', 'value'
            );
        }

        return $options;
    }
}
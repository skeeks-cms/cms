<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\filters\models;

use skeeks\cms\base\Widget;
use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\shop\cmsWidgets\filters\ShopProductFiltersWidget;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class SearchProductsModel
 * @package skeeks\cms\shop\cmsWidgets\filters\models
 */
class SearchProductsModel extends Model
{
    public $image;

    public function rules()
    {
        return [
            [['image'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'image' => \Yii::t('skeeks/cms', 'With photo'),
        ];
    }

    /**
     * @param $params
     * @return ActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     */
    public function search(ActiveDataProvider $dataProvider)
    {
        $query = $dataProvider->query;

        if ($this->image == Cms::BOOL_Y) {
            $query->andWhere([
                'or',
                ['!=', 'cms_content_element.image_id', null],
                ['!=', 'cms_content_element.image_id', ""],
            ]);
        } else {
            if ($this->image == Cms::BOOL_N) {
                $query->andWhere([
                    'or',
                    ['cms_content_element.image_id' => null],
                    ['cms_content_element.image_id' => ""],
                ]);
            }
        }


        return $dataProvider;
    }
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\models\searchs;

use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementProperty;
use skeeks\cms\models\CmsContentProperty;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use yii\base\DynamicModel;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;

/**
 * Class SearchRelatedPropertiesModel
 * @package skeeks\cms\models\searchs
 */
class SearchChildrenRelatedPropertiesModel extends SearchRelatedPropertiesModel
{
    /**
     * @param ActiveDataProvider $activeDataProvider
     */
    public function search(ActiveDataProvider $activeDataProvider, $tableName = 'cms_content_element')
    {
        $classSearch = $this->propertyElementClassName;

        /**
         * @var $activeQuery ActiveQuery
         */
        $activeQuery = $activeDataProvider->query;
        $elementIdsGlobal = [];
        $applyFilters = false;

        foreach ($this->toArray() as $propertyCode => $value) {
            //TODO: add to validator related properties
            if ($propertyCode == 'properties') {
                continue;
            }

            if ($property = $this->getProperty($propertyCode)) {
                if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_NUMBER) {
                    $elementIds = [];

                    $query = $classSearch::find()->select(['element_id'])->where([
                        "property_id" => $property->id
                    ])->indexBy('element_id');

                    if ($fromValue = $this->{$this->getAttributeNameRangeFrom($propertyCode)}) {
                        $applyFilters = true;

                        $query->andWhere(['>=', 'value_num', (float)$fromValue]);
                    }

                    if ($toValue = $this->{$this->getAttributeNameRangeTo($propertyCode)}) {

                        $applyFilters = true;

                        $query->andWhere(['<=', 'value_num', (float)$toValue]);
                    }

                    if (!$fromValue && !$toValue) {
                        continue;
                    }

                    $elementIds = $query->all();

                } else {
                    if (!$value) {
                        continue;
                    }

                    $applyFilters = true;

                    $elementIds = $classSearch::find()->select(['element_id'])->where([
                        "value" => $value,
                        "property_id" => $property->id
                    ])->indexBy('element_id')->all();
                }

                $elementIds = array_keys($elementIds);

                if ($elementIds) {
                    $realElements = CmsContentElement::find()->where(['id' => $elementIds])->select([
                        'id',
                        'parent_content_element_id'
                    ])->indexBy('parent_content_element_id')->groupBy(['parent_content_element_id'])->asArray()->all();
                    $elementIds = array_keys($realElements);
                }

                if (!$elementIds) {
                    $elementIdsGlobal = [];
                }

                if ($elementIdsGlobal) {
                    $elementIdsGlobal = array_intersect($elementIds, $elementIdsGlobal);
                } else {
                    $elementIdsGlobal = $elementIds;
                }
            }
        }


        if ($applyFilters) {
            //$activeQuery->andWhere(['cms_content_element.id' => $elementIdsGlobal]);
            $activeQuery->andWhere([$tableName . '.id' => $elementIdsGlobal]);
        }

    }
}
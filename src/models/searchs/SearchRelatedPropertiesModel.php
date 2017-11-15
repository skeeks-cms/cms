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
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Class SearchRelatedPropertiesModel
 * @package skeeks\cms\models\searchs
 */
class SearchRelatedPropertiesModel extends DynamicModel
{
    /**
     * TODO: IS DEPRECATED > 3.0
     * @var CmsContent
     */
    public $cmsContent = null;
    /**
     * @var CmsContentProperty[]
     */
    public $properties = [];

    /**
     * @var string
     */
    public $propertyElementClassName = '\skeeks\cms\models\CmsContentElementProperty';

    /**
     * TODO: IS DEPRECATED > 3.0
     * @param CmsContent $cmsContent
     */
    public function initCmsContent(CmsContent $cmsContent)
    {
        $this->cmsContent = $cmsContent;

        /**
         * @var $prop CmsContentProperty
         */
        if ($props = $this->cmsContent->cmsContentProperties) {
            $this->initProperties($props);
        }
    }


    public function initProperties($props = [])
    {
        foreach ($props as $prop) {
            if ($prop->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_NUMBER) {
                $this->defineAttribute($this->getAttributeNameRangeFrom($prop->code), '');
                $this->defineAttribute($this->getAttributeNameRangeTo($prop->code), '');

                $this->addRule([
                    $this->getAttributeNameRangeFrom($prop->code),
                    $this->getAttributeNameRangeTo($prop->code)
                ], "safe");

            }

            $this->defineAttribute($prop->code, "");
            $this->addRule([$prop->code], "safe");

            $this->properties[$prop->code] = $prop;
        }
    }

    /**
     * @param $code
     * @return CmsContentProperty
     */
    public function getProperty($code)
    {
        return ArrayHelper::getValue($this->properties, $code);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $result = [];

        foreach ($this->attributes() as $code) {
            if ($property = $this->getProperty($code)) {
                $result[$code] = $property->name;
            } else {
                $result[$code] = $code;
            }

        }

        return $result;
    }


    public $prefixRange = "Sxrange";

    /**
     * @param $propertyCode
     * @return string
     */
    public function getAttributeNameRangeFrom($propertyCode)
    {
        return $propertyCode . $this->prefixRange . "From";
    }

    /**
     * @param $propertyCode
     * @return string
     */
    public function getAttributeNameRangeTo($propertyCode)
    {
        return $propertyCode . $this->prefixRange . "To";
    }


    /**
     * @param $propertyCode
     * @return bool
     */
    public function isAttributeRange($propertyCode)
    {
        if (strpos($propertyCode, $this->prefixRange)) {
            return true;
        }

        return false;
    }


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
        $unionQueries = [];

        foreach ($this->toArray() as $propertyCode => $value) {
            //TODO: add to validator related properties
            if ($propertyCode == 'properties') {
                continue;
            }

            if ($property = $this->getProperty($propertyCode)) {
                if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_NUMBER) {
                    $elementIds = [];

                    $query = $classSearch::find()->select(['element_id as id'])->where([
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

                    $unionQueries[] = $query;
                    //$elementIds = $query->all();

                } else {
                    if (!$value) {
                        continue;
                    }

                    $applyFilters = true;

                    if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_STRING) {
                        $query = $classSearch::find()->select(['element_id as id'])
                            ->where([
                                "property_id" => $property->id
                            ])
                            ->andWhere([
                                'like',
                                'value',
                                $value
                            ]);

                        /*->indexBy('element_id')
                        ->all();*/
                        $unionQueries[] = $query;

                    } else {
                        if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_BOOL) {
                            $query = $classSearch::find()->select(['element_id as id'])->where([
                                "value_bool" => $value,
                                "property_id" => $property->id
                            ]);
                            //print_r($query->createCommand()->rawSql);die;
                            //$elementIds = $query->indexBy('element_id')->all();
                            $unionQueries[] = $query;
                        } else {
                            if (in_array($property->property_type, [
                                \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT
                                ,
                                \skeeks\cms\relatedProperties\PropertyType::CODE_LIST
                                ,
                                \skeeks\cms\relatedProperties\PropertyType::CODE_TREE
                            ])) {
                                $query = $classSearch::find()->select(['element_id as id'])->where([
                                    "value_enum" => $value,
                                    "property_id" => $property->id
                                ]);
                                //print_r($query->createCommand()->rawSql);die;
                                //$elementIds = $query->indexBy('element_id')->all();
                                $unionQueries[] = $query;
                            } else {
                                $query = $classSearch::find()->select(['element_id as id'])->where([
                                    "value" => $value,
                                    "property_id" => $property->id
                                ]);
                                //print_r($query->createCommand()->rawSql);die;
                                //$elementIds = $query->indexBy('element_id')->all();
                                $unionQueries[] = $query;
                            }
                        }
                    }
                }

                /*$elementIds = array_keys($elementIds);

                \Yii::beginProfile('array_intersect');

                if (!$elementIds)
                {
                    $elementIdsGlobal = [];
                }

                if ($elementIdsGlobal)
                {
                    $elementIdsGlobal = array_intersect($elementIds, $elementIdsGlobal);
                } else
                {
                    $elementIdsGlobal = $elementIds;
                }

                \Yii::endProfile('array_intersect');*/

            }
        }


        if ($applyFilters) {
            if ($unionQueries) {
                /**
                 * @var $unionQuery ActiveQuery
                 */
                $lastQuery = null;
                $unionQuery = null;
                $unionQueriesStings = [];
                foreach ($unionQueries as $query) {
                    if ($lastQuery) {
                        $lastQuery->andWhere(['in', 'element_id', $query]);
                        $lastQuery = $query;
                        continue;
                    }

                    if ($unionQuery === null) {
                        $unionQuery = $query;
                    } else {
                        $unionQuery->andWhere(['in', 'element_id', $query]);
                        $lastQuery = $query;
                    }

                    //$unionQueriesStings[] = $query->createCommand()->rawSql;
                }
            }

            //print_r($unionQuery->createCommand()->rawSql);die;

            //$activeQuery->andWhere(['in', $tableName . '.id', $unionQuery]);
            //$activeQuery->union()
            /*if ($unionQueriesStings)
            {
                $unionQueryStings = implode(" MINUS ", $unionQueriesStings);
            }*/
            $activeQuery->andWhere(['in', $tableName . '.id', $unionQuery]);
            //print_r($unionQuery->createCommand()->rawSql);die;
            //$activeQuery->andWhere($tableName . '.id in (' . new Expression($unionQuery) . ')');
            //print_r($activeQuery->createCommand()->rawSql);die;

        }

    }
}
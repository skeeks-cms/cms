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
class SearchRelatedPropertiesModel extends DynamicModel
{
    /**
     * @var CmsContent
     */
    public $cmsContent = null;
    /**
     * @var CmsContentProperty[]
     */
    public $properties = [];


    public function initCmsContent(CmsContent $cmsContent)
    {
        $this->cmsContent = $cmsContent;

        /**
         * @var $prop CmsContentProperty
         */
        if ($props = $this->cmsContent->cmsContentProperties)
        {
            foreach ($props as $prop)
            {
                if ($prop->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_NUMBER)
                {
                    $this->defineAttribute($this->getAttributeNameRangeFrom($prop->code), '');
                    $this->defineAttribute($this->getAttributeNameRangeTo($prop->code), '');

                    $this->addRule([$this->getAttributeNameRangeFrom($prop->code), $this->getAttributeNameRangeTo($prop->code)], "safe");

                }

                $this->defineAttribute($prop->code, "");
                $this->addRule([$prop->code], "safe");

                $this->properties[$prop->code] = $prop;

            }
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

        foreach ($this->attributes() as $code)
        {
            $result[$code] = $this->getProperty($code)->name;
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
        if (strpos($propertyCode, $this->prefixRange))
        {
            return true;
        }

        return false;
    }


    /**
     * @param ActiveDataProvider $activeDataProvider
     */
    public function search(ActiveDataProvider $activeDataProvider)
    {
        /**
         * @var $activeQuery ActiveQuery
         */
        $activeQuery = $activeDataProvider->query;
        $elementIdsGlobal = [];
        $applyFilters = false;

        foreach ($this->toArray() as $propertyCode => $value)
        {

            if ($property = $this->getProperty($propertyCode))
            {
                if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_NUMBER)
                {
                    $elementIds = [];

                    $query = CmsContentElementProperty::find()->select(['element_id'])->where([
                        "property_id"   => $property->id
                    ])->indexBy('element_id');

                    if ($fromValue = $this->{$this->getAttributeNameRangeFrom($propertyCode)})
                    {
                        $applyFilters = true;

                        $query->andWhere(['>=', 'value_num', (float) $fromValue]);
                    }

                    if ($toValue = $this->{$this->getAttributeNameRangeTo($propertyCode)})
                    {
                        $applyFilters = true;

                        $query->andWhere(['<=', 'value_num', (float) $toValue]);
                    }

                    if (!$fromValue && !$toValue)
                    {
                        continue;
                    }

                    $elementIds = $query->all();

                } else
                {
                    if (!$value)
                    {
                        continue;
                    }

                    $applyFilters = true;

                    $elementIds = CmsContentElementProperty::find()->select(['element_id'])->where([
                        "value"         => $value,
                        "property_id"   => $property->id
                    ])->indexBy('element_id')->all();
                }



                $elementIds = array_keys($elementIds);

                if (!$elementIds)
                {
                    $elementIdsGlobal = [];
                }

                if ($elementIdsGlobal)
                {
                    $elementIdsGlobal = array_intersect_assoc($elementIds, $elementIdsGlobal);
                } else
                {
                    $elementIdsGlobal = $elementIds;
                }
            }

        }


        if ($applyFilters)
        {
            $activeQuery->andWhere(['cms_content_element.id' => $elementIdsGlobal]);
        }

    }
}
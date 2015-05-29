<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\propertyTypes;
use skeeks\cms\relatedProperties\PropertyType;
use skeeks\widget\chosen\Chosen;
use yii\helpers\ArrayHelper;

/**
 * Class PropertyTypeRadioList
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeRadioList extends PropertyType
{
    public function init()
    {
        $this->multiple                 = false;
        $this->code                     = self::CODE_LIST;
        $this->name                     = "Радо кнопки (выбор одного значения)";

        parent::init();
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $field->radioList(ArrayHelper::map($this->property->productPropertyEnums, 'id', 'value'), $this->getActiveFormConfig());

        return $field;
    }
}
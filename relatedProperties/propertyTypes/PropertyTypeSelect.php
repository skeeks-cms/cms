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
 * Class PropertyTypeSelect
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeSelect extends PropertyType
{
    public function init()
    {
        $this->multiple                 = false;
        $this->code                     = self::CODE_LIST;
        $this->name                     = "Выпадающий список (выбор одного значения)";

        parent::init();
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = $this->activeForm->fieldSelect(
            $this->model,
            $this->property->code,
            ArrayHelper::map($this->property->enums, 'id', 'value'),
            $this->getActiveFormConfig()
        );

        if (!$field)
        {
            return '';
        }

        $this->postFieldRender($field);
        return $field;
    }
}
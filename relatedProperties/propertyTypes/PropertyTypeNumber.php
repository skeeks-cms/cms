<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\propertyTypes;
use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
use skeeks\cms\relatedProperties\PropertyType;

/**
 * Class PropertyTypeNumber
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeNumber extends PropertyType
{
    public $code                 = self::CODE_NUMBER;
    public $name                 = "";

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $field->textInput();

        return $field;
    }

    public function init()
    {
        parent::init();

        if(!$this->name)
        {
            $this->name = \Yii::t('skeeks/cms','Number');
        }
    }

    /**
     * @varsion > 3.0.2
     * @param RelatedPropertiesModel $relatedPropertiesModel
     *
     * @return $this
     */
    public function addRulesToRelatedPropertiesModel(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $relatedPropertiesModel->addRule($this->property->code, 'number');

        return $this;
    }
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\propertyTypes;
use skeeks\cms\components\Cms;
use skeeks\cms\relatedProperties\PropertyType;

/**
 * Class PropertyTypeTree
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeTree extends PropertyType
{
    public $code = self::CODE_TREE;
    public $name = "Привязка к разделу";

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $field->widget(
            \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
            [
                "mode" => $this->multiple == Cms::BOOL_Y ? \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_MULTI : \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_SINGLE,
                "attributeSingle" => $this->property->code,
                "attributeMulti" => $this->property->code
            ]
        );

        return $field;
    }


    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views/_formPropertyTypeTree.php';
    }

}
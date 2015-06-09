<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\propertyTypes;
use skeeks\cms\relatedProperties\PropertyType;

/**
 * Class PropertyTypeTextarea
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeText extends PropertyType
{
    public $code             = self::CODE_STRING;
    public $name             = "Текст";

    static public $fieldElements    =
    [
        'textarea'  => 'Текстовое поле (textarea)',
        'textInput' => 'Текстовая строка (input)',
    ];

    public $fieldElement            = 'textInput';
    public $textareaRows            = 5;

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'fieldElement' => 'Элемент формы',
            'textareaRows' => 'Количество строк текстового поля'
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['fieldElement', 'string'],
            ['textareaRows', 'integer']
        ]);
    }

    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
     *
     * @return string
     */
    public function getConfigFormFile()
    {
        $class = new \ReflectionClass($this->className());
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . '_formPropertyTypeText.php';
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        if (in_array($this->fieldElement, array_keys(self::$fieldElements)))
        {
            $fieldElement = $this->fieldElement;
            $field->$fieldElement($this->getActiveFormConfig());
        } else
        {
            $field->textInput($this->getActiveFormConfig());
        }

        return $field;
    }
}
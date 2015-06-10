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
use yii\helpers\ArrayHelper;

/**
 * Class PropertyTypeList
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeList extends PropertyType
{
    public $code                 = self::CODE_LIST;
    public $name                 = "Список";

    const FIELD_ELEMENT_SELECT              = "select";
    const FIELD_ELEMENT_SELECT_MULTI        = "selectMulti";
    const FIELD_ELEMENT_RADIO_LIST          = "radioList";
    const FIELD_ELEMENT_CHECKBOX_LIST       = "checkbox";

    static public $fieldElements    =
    [
        self::FIELD_ELEMENT_SELECT          => 'Выпадающий список (select)',
        self::FIELD_ELEMENT_SELECT_MULTI    => 'Выпадающий список (select multiple)',
        self::FIELD_ELEMENT_RADIO_LIST      => 'Радио кнопки (выбор одного значения)',
        self::FIELD_ELEMENT_CHECKBOX_LIST   => 'Checkbox List',
    ];

    public $fieldElement            = self::FIELD_ELEMENT_SELECT;

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'fieldElement'  => 'Элемент формы',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['fieldElement', 'string'],
            ['fieldElement', 'in', 'range' => array_keys(static::$fieldElements)],
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
        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views/_formPropertyTypeList.php';
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        if ($this->fieldElement == self::FIELD_ELEMENT_SELECT)
        {
            $field = $this->activeForm->fieldSelect(
                $this->model->relatedPropertiesModel,
                $this->property->code,
                ArrayHelper::map($this->property->enums, 'id', 'value'),
                []
            );
        } else if ($this->fieldElement == self::FIELD_ELEMENT_SELECT_MULTI)
        {
            $field = $this->activeForm->fieldSelectMulti(
                $this->model->relatedPropertiesModel,
                $this->property->code,
                ArrayHelper::map($this->property->enums, 'id', 'value'),
                []
            );
        } else if ($this->fieldElement == self::FIELD_ELEMENT_RADIO_LIST)
        {
            $field = parent::renderForActiveForm();
            $field->radioList(ArrayHelper::map($this->property->enums, 'id', 'value'));

        } else if ($this->fieldElement == self::FIELD_ELEMENT_CHECKBOX_LIST)
        {
            $field = parent::renderForActiveForm();
            $field->checkboxList(ArrayHelper::map($this->property->enums, 'id', 'value'));
        }


        if (!$field)
        {
            return '';
        }

        $this->postFieldRender($field);
        return $field;
    }

    /**
     * @return $this
     */
    public function initInstance()
    {
        parent::initInstance();

        if (in_array($this->fieldElement, [self::FIELD_ELEMENT_SELECT_MULTI, self::FIELD_ELEMENT_CHECKBOX_LIST]))
        {
            $this->multiple = Cms::BOOL_Y;
        }

        return $this;
    }
}
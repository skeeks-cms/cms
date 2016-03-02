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
use yii\widgets\ActiveForm;

/**
 * Class PropertyTypeList
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeList extends PropertyType
{
    public $code                 = self::CODE_LIST;
    public $name                 = "";

    const FIELD_ELEMENT_SELECT              = "select";
    const FIELD_ELEMENT_SELECT_MULTI        = "selectMulti";
    const FIELD_ELEMENT_RADIO_LIST          = "radioList";
    const FIELD_ELEMENT_CHECKBOX_LIST       = "checkbox";

    static public function fieldElements()
    {
        return [
            self::FIELD_ELEMENT_SELECT          => \Yii::t('app','Combobox').' (select)',
            self::FIELD_ELEMENT_SELECT_MULTI    => \Yii::t('app','Combobox').' (select multiple)',
            self::FIELD_ELEMENT_RADIO_LIST      => \Yii::t('app','Radio Buttons (selecting one value)'),
            self::FIELD_ELEMENT_CHECKBOX_LIST   => \Yii::t('app','Checkbox List'),
        ];
    }

    public $fieldElement            = self::FIELD_ELEMENT_SELECT;

    public function init()
    {
        parent::init();

        if(!$this->name)
        {
            $this->name = \Yii::t('app','List');
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'fieldElement'  => \Yii::t('app','Element form'),
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['fieldElement', 'string'],
            ['fieldElement', 'in', 'range' => array_keys(static::fieldElements())],
        ]);
    }

    /**
     * @return string
     */
    public function renderConfigForm(ActiveForm $activeForm)
    {
        echo $activeForm->fieldSelect($this, 'fieldElement', \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeList::fieldElements());
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
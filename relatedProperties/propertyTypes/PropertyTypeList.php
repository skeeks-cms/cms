<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\relatedProperties\propertyTypes;
use skeeks\cms\components\Cms;
use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
use skeeks\cms\relatedProperties\PropertyType;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * Class PropertyTypeList
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeList extends PropertyType
{
    public $enumRoute               = 'cms/admin-cms-content-property-enum';

    public $code                 = self::CODE_LIST;
    public $name                 = "";

    const FIELD_ELEMENT_SELECT              = "select";
    const FIELD_ELEMENT_SELECT_MULTI        = "selectMulti";
    const FIELD_ELEMENT_LISTBOX             = "listbox";
    const FIELD_ELEMENT_LISTBOX_MULTI       = "listboxmulti";

    const FIELD_ELEMENT_RADIO_LIST          = "radioList";
    const FIELD_ELEMENT_CHECKBOX_LIST       = "checkbox";

    static public function fieldElements()
    {
        return [
            self::FIELD_ELEMENT_SELECT          => \Yii::t('skeeks/cms', 'Combobox').' (select)',
            self::FIELD_ELEMENT_SELECT_MULTI    => \Yii::t('skeeks/cms', 'Combobox').' (select multiple)',
            self::FIELD_ELEMENT_RADIO_LIST      => \Yii::t('skeeks/cms', 'Radio Buttons (selecting one value)'),
            self::FIELD_ELEMENT_CHECKBOX_LIST   => \Yii::t('skeeks/cms', 'Checkbox List'),
            self::FIELD_ELEMENT_LISTBOX         => \Yii::t('skeeks/cms', 'ListBox'),
            self::FIELD_ELEMENT_LISTBOX_MULTI   => \Yii::t('skeeks/cms', 'ListBox Multi'),
        ];
    }

    public $fieldElement            = self::FIELD_ELEMENT_SELECT;

    public function init()
    {
        parent::init();

        if(!$this->name)
        {
            $this->name = \Yii::t('skeeks/cms','List');
        }
    }

    /**
     * @return bool
     */
    public function getIsMultiple()
    {
        if (in_array($this->fieldElement, [self::FIELD_ELEMENT_SELECT_MULTI, self::FIELD_ELEMENT_CHECKBOX_LIST, self::FIELD_ELEMENT_LISTBOX_MULTI]))
        {
            return true;
        }

        return false;
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'fieldElement'  => \Yii::t('skeeks/cms','Element form'),
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

        echo \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
            'label'             => \Yii::t('skeeks/cms',"Values for list"),
            'hint'              => \Yii::t('skeeks/cms',"You can snap to the element number of properties, and set the value to them"),
            'parentModel'       => $this->property,
            'relation'          => [
                'property_id' => 'id'
            ],

            'controllerRoute'   => $this->enumRoute,
            'gridViewOptions'   => [
                'sortable' => true,
                'columns' => [
                    [
                        'attribute'     => 'id',
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'code',
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'value',
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'priority',
                        'enableSorting' => false
                    ],

                    [
                        'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                        'attribute'     => 'def',
                        'enableSorting' => false
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        if ($this->fieldElement == self::FIELD_ELEMENT_SELECT)
        {
            $field = $this->activeForm->fieldSelect(
                $this->property->relatedPropertiesModel,
                $this->property->code,
                ArrayHelper::map($this->property->enums, 'id', 'value'),
                []
            );
        } else if ($this->fieldElement == self::FIELD_ELEMENT_SELECT_MULTI)
        {
            $field = $this->activeForm->fieldSelectMulti(
                $this->property->relatedPropertiesModel,
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

        } else if ($this->fieldElement == self::FIELD_ELEMENT_LISTBOX_MULTI)
        {
            $field = parent::renderForActiveForm();
            $field->listBox(ArrayHelper::map($this->property->enums, 'id', 'value'), [
                'size' => 5,
                'multiple' => 'multiple'
            ]);
        } else if ($this->fieldElement == self::FIELD_ELEMENT_LISTBOX)
        {
            $field = parent::renderForActiveForm();
            $field->listBox(ArrayHelper::map($this->property->enums, 'id', 'value'), [
                'size' => 1,
            ]);
        }


        if (!$field)
        {
            return '';
        }

        return $field;
    }

    /**
     * @varsion > 3.0.2
     *
     * @return $this
     */
    public function addRules()
    {
        if ($this->isMultiple)
        {
            $this->property->relatedPropertiesModel->addRule($this->property->code, 'safe');
        } else
        {
            $this->property->relatedPropertiesModel->addRule($this->property->code, 'integer');
        }

        return $this;
    }


    /**
     * @return string
     */
    public function getStringValue()
    {
        $value = $this->property->relatedPropertiesModel->getAttribute($this->property->code);

        if ($this->isMultiple)
        {
            if ($this->property->enums)
            {
                $result = [];

                foreach ($this->property->enums as $enum)
                {
                    if (in_array($enum->id, $value))
                    {
                        $result[$enum->code] = $enum->value;
                    }

                }

                return implode(", ", $result);
            }
        } else
        {
            if ($this->property->enums)
            {
                $enums = (array) $this->property->enums;

                foreach ($enums as $enum)
                {
                    if ($enum->id == $value)
                    {
                        return $enum->value;
                    }
                }
            }

            return "";
        }
    }
}
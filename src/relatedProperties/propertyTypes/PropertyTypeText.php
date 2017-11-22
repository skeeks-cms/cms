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
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * Class PropertyTypeTextarea
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeText extends PropertyType
{
    public $code = self::CODE_STRING;
    public $name = "";

    public $default_value = null;

    /*static public $fieldElements    =
    [
        'textarea'  => 'Текстовое поле (textarea)',
        'textInput' => 'Текстовая строка (input)',
    ];*/

    public $fieldElement = 'textInput';
    public $rows = 5;

    public static function fieldElements()
    {
        return [
            'textarea' => \Yii::t('skeeks/cms', 'Text field') . ' (textarea)',
            'textInput' => \Yii::t('skeeks/cms', 'Text string') . ' (input)',
            'hiddenInput' => \Yii::t('skeeks/cms', 'Скрытое поле') . ' (hiddenInput)',
            'default_value' => \Yii::t('skeeks/cms', 'Default Value'),
        ];
    }

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', 'Text');
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
            [
                'fieldElement' => \Yii::t('skeeks/cms', 'Element form'),
                'rows' => \Yii::t('skeeks/cms', 'The number of lines of the text field'),
                'default_value' => \Yii::t('skeeks/cms', 'Default Value'),
            ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
            [
                ['fieldElement', 'string'],
                ['rows', 'integer', 'min' => 1, 'max' => 50],
                ['default_value', 'string'],
            ]);
    }

    /**
     * @return string
     */
    public function renderConfigForm(ActiveForm $activeForm)
    {
        echo $activeForm->fieldSelect($this, 'fieldElement',
            \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeText::fieldElements());
        echo $activeForm->fieldInputInt($this, 'rows');
        echo $activeForm->field($this, 'default_value');
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        if (in_array($this->fieldElement, array_keys(self::fieldElements()))) {
            $fieldElement = $this->fieldElement;
            $field->$fieldElement([
                'rows' => $this->rows
            ]);

            if ($this->fieldElement == 'hiddenInput') {
                $field->label(false);
            }
        } else {
            $field->textInput([]);
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
        $this->property->relatedPropertiesModel->addRule($this->property->code, 'string');

        if ($this->property->isRequired) {
            $this->property->relatedPropertiesModel->addRule($this->property->code, 'required');
        }

        return $this;
    }

    /**
     * @varsion > 3.0.2
     *
     * @return null
     */
    public function getDefaultValue()
    {
        if ($this->default_value !== null) {
            return $this->default_value;
        }
        return;
    }

}
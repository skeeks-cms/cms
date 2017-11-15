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
 * Class PropertyTypeNumber
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeNumber extends PropertyType
{
    public $code = self::CODE_NUMBER;
    public $name = "";

    public $default_value = null;

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', 'Number');
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
            [
                'default_value' => \Yii::t('skeeks/cms', 'Default Value'),
            ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
            [
                ['default_value', 'number'],
            ]);
    }

    /**
     * @return string
     */
    public function renderConfigForm(ActiveForm $activeForm)
    {
        echo $activeForm->field($this, 'default_value');
    }


    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $field->textInput();

        return $field;
    }


    /**
     * @varsion > 3.0.2
     *
     * @return $this
     */
    public function addRules()
    {
        $this->property->relatedPropertiesModel->addRule($this->property->code, 'number');

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
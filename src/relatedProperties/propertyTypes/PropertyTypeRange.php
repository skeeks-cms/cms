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
 *
 * TODO: not ready
 *
 * Class PropertyTypeTextarea
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeRange extends PropertyType
{
    public $code = self::CODE_RANGE;
    public $name = "";

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', 'Range');
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
            [
            ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
            [
            ]);
    }

    /**
     * @return string
     */
    public function renderConfigForm(ActiveForm $activeForm)
    {
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        if (in_array($this->fieldElement, array_keys(self::fieldElements()))) {
            $fieldElement = $this->fieldElement;

            if ($fieldElement == 'radioList' || $fieldElement == 'listbox') {
                $field->{$fieldElement}(\Yii::$app->formatter->booleanFormat);
            } else {
                $field->{$fieldElement}();
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
        $this->property->relatedPropertiesModel->addRule($this->property->code, 'boolean');

        if ($this->property->isRequired) {
            $this->property->relatedPropertiesModel->addRule($this->property->code, 'required');
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getAsText()
    {
        $value = $this->property->relatedPropertiesModel->getAttribute($this->property->code);
        return (string)\Yii::$app->formatter->asBoolean($value);
    }

}
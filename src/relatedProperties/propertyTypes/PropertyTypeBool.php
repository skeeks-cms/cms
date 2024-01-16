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
class PropertyTypeBool extends PropertyType
{
    public $code = self::CODE_BOOL;
    public $name = "";

    public $default_value = null;

    public $fieldElement = 'radioInput';
    public $rows = 5;

    public static function fieldElements()
    {
        return [
            'radioList' => \Yii::t('skeeks/cms', 'Radio'),
            'checkbox' => \Yii::t('skeeks/cms', 'Checkbox'),
            'listBox' => \Yii::t('skeeks/cms', 'listBox'),
        ];
    }

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', 'Yes/No');
        }
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
            [
                'fieldElement' => \Yii::t('skeeks/cms', 'Element form'),
                'default_value' => \Yii::t('skeeks/cms', 'Default Value'),
            ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
            [
                ['fieldElement', 'string'],
                ['default_value', 'boolean'],
            ]);
    }

    /**
     * @return string
     */
    public function renderConfigFormFields(ActiveForm $activeForm)
    {
        $result = $activeForm->fieldSelect($this, 'fieldElement', self::fieldElements());
        $result .= $activeForm->field($this, 'default_value')->radioList(\Yii::$app->formatter->booleanFormat);
        return $result;
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $field = parent::renderForActiveForm($relatedPropertiesModel);

        if (in_array($this->fieldElement, array_keys(self::fieldElements()))) {
            $fieldElement = $this->fieldElement;
            if ($relatedPropertiesModel->relatedElementModel->isNewRecord && $this->getDefaultValue($relatedPropertiesModel)) {
                $relatedPropertiesModel->setAttribute($this->property->code, $this->getDefaultValue($relatedPropertiesModel));
            }

            if ($fieldElement == 'radioList' || $fieldElement == 'listBox') {
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
    public function addRules(RelatedPropertiesModel $relatedPropertiesModel)
    {
        if ($this->property->isRequired) {
            $relatedPropertiesModel->addRule($this->property->code, 'required', [
                'requiredValue' => '1',
                'message' => \Yii::t('yii', '{attribute} cannot be blank.'),
            ]);
        } else {
            $relatedPropertiesModel->addRule($this->property->code, 'boolean', [
                'trueValue' => '1',
                'falseValue' => '0'
            ]);
        }

        return $this;
    }

    /**
     * @varsion > 3.0.2
     *
     * @return null
     */
    public function getDefaultValue(RelatedPropertiesModel $relatedPropertiesModel)
    {
        if ($this->default_value !== null) {
            return $this->default_value;
        }

        return;
    }

    /**
     * @return string
     */
    public function getAsText(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $value = $relatedPropertiesModel->getAttribute($this->property->code);
        return (string)\Yii::$app->formatter->asBoolean($value);
    }

}
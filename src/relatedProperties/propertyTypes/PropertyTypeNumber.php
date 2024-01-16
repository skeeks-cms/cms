<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */

namespace skeeks\cms\relatedProperties\propertyTypes;

use skeeks\cms\backend\widgets\forms\NumberInputWidget;
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
    public function renderConfigFormFields(ActiveForm $activeForm)
    {
        return $activeForm->field($this, 'default_value');
    }


    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $field = parent::renderForActiveForm($relatedPropertiesModel);

        $append = '';
        if ($this->property->cms_measure_code) {
            $append = $this->property->cmsMeasure->asShortText;
        }

        //$field->textInput();
        $field->widget(NumberInputWidget::class, [
            'options' => [
                'step' => '0.00000001'
            ],
            'append' => $append
        ]);

        return $field;
    }


    /**
     * @varsion > 3.0.2
     *
     * @return $this
     */
    public function addRules(RelatedPropertiesModel $relatedPropertiesModel)
    {
        $relatedPropertiesModel->addRule($this->property->code, 'number');

        if ($this->property->isRequired) {
            $relatedPropertiesModel->addRule($this->property->code, 'required');
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
}
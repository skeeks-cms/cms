<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */

namespace skeeks\cms\relatedProperties\userPropertyTypes;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
use skeeks\cms\relatedProperties\PropertyType;
use yii\helpers\ArrayHelper;

/**
 * Class UserPropertyTypeComboText
 * @package skeeks\cms\relatedProperties\userPropertyTypes
 */
class UserPropertyTypeComboText extends PropertyType
{
    public $code = self::CODE_STRING;
    public $name = "Текст/CKEditor/HTML";


    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
            [
                //'type'  => 'Тип',
            ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
            [
                //['type', 'string'],
                //['type', 'in', 'range' => array_keys(self::$types)],
            ]);
    }

    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $field->widget(\skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
            [
                'ckeditorOptions' =>
                    [
                        'relatedModel' => $this->property->relatedPropertiesModel->relatedElementModel
                    ]
            ]);

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

        return $this;
    }
}
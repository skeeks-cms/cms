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
 * Class PropertyTypeTree
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeTree extends PropertyType
{
    public $code = self::CODE_TREE;
    public $name = "Привязка к разделу";

    public $is_multiple = false;


    /**
     * Файл с формой настроек, по умолчанию лежит в той же папке где и компонент.
     *
     * @return string
     */
    public function renderConfigForm(ActiveForm $activeForm)
    {
        echo $activeForm->field($this, 'is_multiple')->checkbox(\Yii::$app->formatter->booleanFormat);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'is_multiple'  => \Yii::t('skeeks/cms','Multiple choice'),
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['is_multiple', 'boolean'],
        ]);
    }


    /**
     * @return bool
     */
    public function getIsMultiple()
    {
        return $this->is_multiple;
    }


    /**
     * @return \yii\widgets\ActiveField
     */
    public function renderForActiveForm()
    {
        $field = parent::renderForActiveForm();

        $field->widget(
            \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
            [
                "mode" => $this->isMultiple ? \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_MULTI : \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_SINGLE,
                "attributeSingle" => $this->property->code,
                "attributeMulti" => $this->property->code
            ]
        );

        return $field;
    }
}
<?php
/**
 * ActiveForm
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\base\widgets;

use skeeks\cms\components\Cms;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveField;

/**
 * @deprecated
 *
 * Class ActiveForm
 * @package skeeks\cms\modules\admin\widgets
 */
class ActiveForm extends \yii\widgets\ActiveForm
{
    /**
     * @param $model
     * @param $attribute
     * @param array $items
     * @param bool $enclosedByLabel
     * @param array $fieldOptions
     * @return ActiveField the created ActiveField object
     */
    public function fieldCheckboxBoolean($model, $attribute, $items = [], $enclosedByLabel = true, $fieldOptions = [])
    {
        return $this->field($model, $attribute, $fieldOptions)->checkbox([
            'uncheck' => Cms::BOOL_N,
            'value' => Cms::BOOL_Y
        ], $enclosedByLabel);
    }

    /**
     * @param $model
     * @param $attribute
     * @param array $items
     * @param bool $enclosedByLabel
     * @param array $fieldOptions
     * @return ActiveField the created ActiveField object
     */
    public function fieldRadioListBoolean($model, $attribute, $items = [], $options = [], $fieldOptions = [])
    {
        if (!$items) {
            $items = \Yii::$app->cms->booleanFormat();
        }

        return $this->field($model, $attribute, $fieldOptions)->radioList($items, $options);
    }

    /**
     * @param $model
     * @param $attribute
     * @param array $config
     * @param array $fieldOptions
     * @return ActiveField the created ActiveField object
     */
    public function fieldInputInt($model, $attribute, $config = [], $fieldOptions = [])
    {
        //Html::addCssClass($config, "sx-input-int");
        $config['class'] = ArrayHelper::getValue($config, 'class') . "form-control sx-input-int";

        $defaultConfig = [
            'type' => 'number'
        ];

        $config = ArrayHelper::merge($defaultConfig, (array)$config);
        return $this->field($model, $attribute, $fieldOptions)->textInput($config);
    }

    /**
     * @param $model
     * @param $attribute
     * @param $items
     * @param array $config
     * @param array $fieldOptions
     * @return ActiveField the created ActiveField object
     */
    public function fieldSelect($model, $attribute, $items, $config = [], $fieldOptions = [])
    {
        $config = ArrayHelper::merge(
            ['size' => 1], //Опции по умолчанию
            $config
        );
        return $this->field($model, $attribute, $fieldOptions)->listBox($items, $config);
    }

    /**
     * @param $model
     * @param $attribute
     * @param $items
     * @param array $config
     * @param array $fieldOptions
     * @return ActiveField
     */
    public function fieldSelectMulti($model, $attribute, $items, $config = [], $fieldOptions = [])
    {
        $config = ArrayHelper::merge(
            $config, //Опции по умолчанию
            [
                'multiple' => 'multiple',
                'size' => 5
            ]
        );
        return $this->fieldSelect($model, $attribute, $items, $config, $fieldOptions);
    }
}
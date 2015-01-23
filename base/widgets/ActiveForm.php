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

/**
 * Class ActiveForm
 * @package skeeks\cms\modules\admin\widgets
 */
class ActiveForm extends \yii\widgets\ActiveForm
{
    /**
     * @param $model
     * @param $attribute
     * @param array $options
     * @return \yii\widgets\ActiveField
     */
    public function fieldNoLabel($model, $attribute, $options = [])
    {
        return parent::field($model, $attribute, array_merge($options,
            ['parts' => ['{label}' => '']]
        ));
    }
}
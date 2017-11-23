<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.07.2015
 */

namespace skeeks\cms\widgets;

use kartik\color\ColorInputAsset;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * Class ColorInput
 * @package skeeks\cms\widgets
 */
class ColorInput extends \kartik\color\ColorInput
{
    const SAVE_VALUE_TO_STRING = 'toString';
    const SAVE_VALUE_TO_HEX = 'toHex';
    const SAVE_VALUE_TO_HEX_STRING = 'toHexString';
    const SAVE_VALUE_TO_RGB = 'toRgb';
    const SAVE_VALUE_TO_RGB_STRING = 'toRgbString';
    const SAVE_VALUE_TO_HSV = 'toHsv';
    const SAVE_VALUE_TO_HSV_STRING = 'toHsvString';
    const SAVE_VALUE_TO_HSL = 'toHsl';
    const SAVE_VALUE_TO_HSL_STRING = 'toHslString';
    const SAVE_VALUE_TO_NAME = 'toName';

    /**
     * @var array
     */
    static public $possibleSaveAs =
        [
            self::SAVE_VALUE_TO_STRING => 'Стандартно — #ff0000',
            //self::SAVE_VALUE_TO_HEX => 'toHex — ff0000',
            self::SAVE_VALUE_TO_HEX_STRING => 'toHexString — #ff0000',
            //self::SAVE_VALUE_TO_RGB => 'toRgb — {"r":255,"g":0,"b":0}',
            self::SAVE_VALUE_TO_RGB_STRING => 'toRgbString — rgb(255, 0, 0)',
            //self::SAVE_VALUE_TO_HSV => 'toHsv — {"h":0,"s":1,"v":1}',
            self::SAVE_VALUE_TO_HSV_STRING => 'toHsvString — hsv(0, 100%, 100%)',
            //self::SAVE_VALUE_TO_HSL => 'toHsl — {"h":0,"s":1,"l":0.5}',
            self::SAVE_VALUE_TO_HSL_STRING => 'toHslString — hsl(0, 100%, 50%)',
            self::SAVE_VALUE_TO_NAME => 'toName — red',
        ];
    /**
     *
     *
     * t.toHex()       // "ff0000"
     * t.toHexString() // "#ff0000"
     * t.toRgb()       // {"r":255,"g":0,"b":0}
     * t.toRgbString() // "rgb(255, 0, 0)"
     * t.toHsv()       // {"h":0,"s":1,"v":1}
     * t.toHsvString() // "hsv(0, 100%, 100%)"
     * t.toHsl()       // {"h":0,"s":1,"l":0.5}
     * t.toHslString() // "hsl(0, 100%, 50%)"
     * t.toName()      // "red"
     *
     * @var string
     */
    public $saveValueAs = self::SAVE_VALUE_TO_STRING;

    /**
     *
     * Registers the needed assets
     */
    public function registerAssets()
    {
        $view = $this->getView();
        $value = $this->hasModel() ? Html::getAttributeValue($this->model, $this->attribute) : $this->value;
        $this->html5Options['value'] = $value;
        ColorInputAsset::register($view);
        if ($this->useNative) {
            parent::registerAssets();
            return;
        }

        \kartik\base\Html5InputAsset::register($view);
        $caption = 'jQuery("#' . $this->options['id'] . '")';
        $input = 'jQuery("#' . $this->html5Options['id'] . '")';
        $this->pluginOptions['change'] = new JsExpression("function(color){
            _.delay(function()
            {
                {$caption}.val(color." . $this->saveValueAs . "());
            }, 500);
        }");
        $this->registerPlugin('spectrum', $input);
        $js = <<< JS
{$input}.spectrum('set','{$value}');
{$caption}.on('change', function(){
    {$input}.spectrum('set',{$caption}.val());
});
JS;
        $view->registerJs($js);
    }
}
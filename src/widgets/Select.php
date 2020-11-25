<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */
namespace skeeks\cms\widgets;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\View;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class Select extends Select2
{
    protected $_isAutoDetectAutoDeselect = true;
    
    protected $_defaultOptions = [];

    /**
     * @deprecated 
     * @var bool 
     */
    public $allowDeselect = true;

    /**
     * @var bool 
     */
    public $multiple = false;

    /**
     * @var string 
     */
    public $placeholder = '';
    
    public function init()
    {
        if ($this->placeholder) {
            $this->options['placeholder'] = $this->placeholder;
        }
        
        if ($this->multiple) {
            $this->options['multiple'] = 'multiple';
        }
        
        if ($this->allowDeselect) {
            if ($this->isRequired()) {
            } else {
                if (!isset($this->options['placeholder'])) {
                    $this->options['placeholder'] = '';
                }
                $this->pluginOptions['allowClear'] = true;
                if (!$this->multiple) {
                    $this->data = ArrayHelper::merge(['' => ''], (array) $this->data);
                }
                
            }
        }
        
        parent::init();

    }
    /**
     * @param $items
     * @return $this
     */
    public function setItems($items = [])
    {
        $this->data = $items;
        return $this;
    }


    /**
     * Registers the client assets for [[Select2]] widget.
     */
    public function registerAssets()
    {
        $id = $this->options['id'];
        $this->registerAssetBundle();
        $isMultiple = isset($this->options['multiple']) && $this->options['multiple'];
        $options = Json::encode([
            'themeCss' => ".select2-container--{$this->theme}",
            'sizeCss' => empty($this->addon) && $this->size !== self::MEDIUM ? ' input-' . $this->size : '',
            'doReset' => static::parseBool($this->changeOnReset),
            'doToggle' => static::parseBool($isMultiple && $this->showToggleAll),
            'doOrder' => static::parseBool($isMultiple && $this->maintainOrder),
        ]);
        $this->_s2OptionsVar = 's2options_' . hash('crc32', $options);
        $this->options['data-s2-options'] = $this->_s2OptionsVar;
        $view = $this->getView();
        $view->registerJs("var {$this->_s2OptionsVar} = {$options};", View::POS_READY);
        if ($this->maintainOrder) {
            $val = Json::encode(is_array($this->value) ? $this->value : [$this->value]);
            $view->registerJs("initS2Order('{$id}',{$val});");
        }
        $this->registerPlugin($this->pluginName, "jQuery('#{$id}')", "initS2Loading('{$id}','{$this->_s2OptionsVar}')");
    }

    /**
     * Registers plugin options by storing within a uniquely generated javascript variable.
     *
     * @param string $name the plugin name
     */
    protected function registerPluginOptions($name)
    {
        $this->hashPluginOptions($name);
        $encOptions = empty($this->_encOptions) ? '{}' : $this->_encOptions;
        $this->registerWidgetJs("window.{$this->_hashVar} = {$encOptions};\n", View::POS_READY);
    }

    /**
     * @return bool
     */
    protected function isRequired()
    {
        if (!empty($this->options['required'])) {
            return true;
        }
        if (!$this->hasModel()) {
            return false;
        }
        $validators = $this->model->getActiveValidators($this->attribute);
        foreach ($validators as $validator) {
            if ($validator instanceof RequiredValidator) {
                if (is_callable($validator->when)) {
                    if (call_user_func($validator->when, $this->model, $this->attribute)) {
                        return true;
                    }
                } else {
                    return true;
                }

            }
        }
        return false;
    }
}
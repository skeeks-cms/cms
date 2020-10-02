<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */
namespace skeeks\cms\widgets;
use yii\helpers\ArrayHelper;

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
        
        if ($this->allowDeselect && !$this->multiple) {
            if ($this->isRequired()) {
            } else {
                $this->data = ArrayHelper::merge(['' => ''], (array) $this->data);
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

}
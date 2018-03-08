<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\text;

use skeeks\cms\base\Widget;
use skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget;
use skeeks\yii2\form\fields\WidgetField;
use yii\helpers\ArrayHelper;

/**
 * Class TextCmsWidget
 * @package skeeks\cms\cmsWidgets\text
 */
class TextCmsWidget extends Widget
{
    public $text = '';
    protected $_obContent = '';
    public static function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Текст',
        ]);
    }

    public static function begin($config = [])
    {
        parent::begin($config);

        ob_start();
        ob_implicit_flush(false);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'text' => 'Текст',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['text', 'string'],
        ]);
    }

    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'text' => [
                'class'       => WidgetField::class,
                'widgetClass' => ComboTextInputWidget::class,
            ],
        ];
    }

    public function run()
    {
        if ($this->_isBegin) {
            $this->_obContent = ob_get_clean();
            if (!$this->text) {
                $this->text = $this->_obContent;
            }
        }

        return parent::run();
    }

    /**
     * @return array
     */
    public function getCallableData()
    {
        $attributes = parent::getCallableData();
        if (!$attributes['attributes']['text']) {
            $attributes['attributes']['text'] = $this->_obContent;
        }
        return $attributes;
    }

    protected function _run()
    {
        return $this->text;
    }

}
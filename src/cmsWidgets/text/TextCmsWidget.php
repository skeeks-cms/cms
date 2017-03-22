<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\text;

use skeeks\cms\base\Widget;
use skeeks\cms\helpers\UrlHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/**
 * Class TextCmsWidget
 * @package skeeks\cms\cmsWidgets\text
 */
class TextCmsWidget extends Widget
{
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Текст'
        ]);
    }

    public $text = '';

    protected $_obContent = '';

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'text' => 'Текст'
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['text', 'string']
        ]);
    }

    public function renderConfigForm(ActiveForm $form)
    {
        echo $form->field($this, 'text')->widget(
            \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className()
        );
    }

    static public function begin($config = [])
    {
        parent::begin($config);

        ob_start();
        ob_implicit_flush(false);
    }

    public function run()
    {
        if ($this->_isBegin)
        {
            $this->_obContent = ob_get_clean();
            if (!$this->text)
            {
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
        if (!$attributes['attributes']['text'])
        {
            $attributes['attributes']['text'] = $this->_obContent;
        }
        return $attributes;
    }

    protected function _run()
    {
        return $this->text;
    }

}
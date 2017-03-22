<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */
namespace skeeks\cms\base;
use skeeks\cms\traits\HasComponentDbSettingsTrait;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use yii\base\Model;
use yii\widgets\ActiveForm;

/**
 * Class Component
 * @package skeeks\cms\base
 */
abstract class Component extends Model implements ConfigFormInterface
{
    //Можно задавать описание компонента.
    use HasComponentDescriptorTrait;
    //Можно сохранять настройки в базу
    use HasComponentDbSettingsTrait;

    public $defaultAttributes = [];

    public function init()
    {
        $this->defaultAttributes = $this->attributes;

        \Yii::beginProfile("Init: " . $this->className());
            $this->initSettings();
        \Yii::endProfile("Init: " . $this->className());
    }

    public function renderConfigForm(ActiveForm $form)
    {}
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */
namespace skeeks\cms\base;
use skeeks\cms\traits\HasComponentConfigFormTrait;
use skeeks\cms\traits\HasComponentDbSettingsTrait;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use yii\base\Model;
/**
 * Class Component
 * @package skeeks\cms\base
 */
abstract class Component extends Model
{
    //Можно задавать описание компонента.
    use HasComponentDescriptorTrait;
    //Может строить форму для своих данных.
    use HasComponentConfigFormTrait;
    //Можно сохранять настройки в базу
    use HasComponentDbSettingsTrait;

    public function init()
    {
        \Yii::beginProfile("Init: " . $this->className());
            $this->initSettings();
        \Yii::endProfile("Init: " . $this->className());
    }

}
<?php
/**
 * Widget
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 03.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\base;

/**
 * Class Widget
 * @package skeeks\cms\base
 */
class Widget extends \yii\base\Widget
{
    public function init()
    {
        parent::init();
        $this->_ensure();
    }

    /**
     * Проверка целостности настроек виджета
     */
    protected function _ensure()
    {}
}
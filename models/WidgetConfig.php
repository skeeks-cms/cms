<?php
/**
 * WidgetConfig
 * 
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\base\Model;

class WidgetConfig extends Model
{
    /**
     * @var string|null
     */
    public $widget = null;
    public $config = [];

    protected $_data = [];

    public function init()
    {
        parent::init();

        $defaultConfig  = get_class_vars($this->widget);
        $this->config   = array_merge($defaultConfig, $this->config);
    }


    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {

        if (isset($this->config[$name]))
        {
            return $this->config[$name];
        }

        return null;
    }

    /**
     * @return WidgetDescriptor
     */
    public function getWidgetDescriptor()
    {
        return \Yii::$app->registeredWidgets->getComponent($this->widget);
    }
}
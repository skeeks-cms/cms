<?php
/**
 * Model
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 10.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components\registeredWidgets;

use skeeks\cms\base\Component;
use skeeks\cms\base\Widget;

class Model
    extends Component
{
    public $label       = "";
    public $description = "";
    public $templates   = [];
    public $enabled     = true;
    public $class       = "";

    protected function _ensure()
    {}

    /**
     * @param $config
     * @return Widget|null
     */
    public function createWidget($config)
    {
        $widgetClass = $this->class;
        $widget = $widgetClass::begin($config);
        if ($widget)
        {
            return $widget;
        }

        return null;
    }

    /**
     * @param array $data
     * @return string
     */
    public function renderForm($data = [])
    {
        $class = $this->class;
        $class = new \ReflectionClass($class);

        return \Yii::$app->getView()->renderFile(dirname($class->getFileName()) . DIRECTORY_SEPARATOR . '_form.php', $data);
    }
}
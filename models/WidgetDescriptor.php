<?php
/**
 * WidgetDescriptor
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\base\Component;
use skeeks\cms\base\Widget;
use skeeks\cms\components\ModelActionViews;
use skeeks\cms\models\ComponentModel;

class WidgetDescriptor
    extends ComponentModel
{
    public $description = "";
    public $templates   = [];

    protected function _ensure()
    {}

    /**
     * @param $config
     * @return Widget|null
     */
    public function createWidget($config)
    {
        $widgetClass = $this->id;
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
        $class = $this->id;
        $class = new \ReflectionClass($class);

        return \Yii::$app->getView()->renderFile(dirname($class->getFileName()) . DIRECTORY_SEPARATOR . '_form.php', $data);
    }


    /**
     * @var ModelActionViews
     */
    protected $_templatesObject = null;
    /**
     * @return ModelActionViews
     */
    public function getTemplatesObject()
    {
        if ($this->_templatesObject === null)
        {
            $this->_templatesObject = new ModelActionViews([
                'components' => $this->templates
            ]);
        }

        return $this->_templatesObject;
    }


}
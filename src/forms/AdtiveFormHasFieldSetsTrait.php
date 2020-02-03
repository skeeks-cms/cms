<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\forms;

use skeeks\cms\widgets\forms\FieldSetWidget;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
trait AdtiveFormHasFieldSetsTrait
{
    /**
     * @var string
     */
    public $firldSetClass = FieldSetWidget::class;

    /**
     * @param       $name
     * @param array $widgetConfig
     * @return FieldSetWidget
     */
    public function fieldSet($name, $widgetConfig = [])
    {
        $class = $this->firldSetClass;
        $widgetConfig['name'] = $name;
        return $class::begin($widgetConfig);

    }

    /**
     * @return string
     */
    /*public function fieldSetEnd()
    {
        $class = $this->firldSetClass;
        return $class::end();
    }*/
}
<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\forms;

/**
 * @property string $icon;
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
interface IActiveFormHasFieldSets
{
    /**
     * @param       $name
     * @param array $widgetConfig
     * @return FieldSetWidget
     */
    public function fieldSet($name, $widgetConfig = []);
}
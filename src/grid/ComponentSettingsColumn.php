<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.05.2015
 */

namespace skeeks\cms\grid;

use skeeks\cms\base\Component;
use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\User;

/**
 * Class LongTextColumnData
 * @package skeeks\cms\grid
 */
class ComponentSettingsColumn extends BooleanColumn
{
    /**
     * @var Component
     */
    public $component = null;

    public $label = 'Наличие настроек';

    /**
     * @inheritdoc
     */
    public function getDataCellValue($model, $key, $index)
    {
        $settings = null;

        if ($this->component === null) {
            return $this->_result(Cms::BOOL_N);
        }

        if ($model instanceof CmsSite) {
            $settings = \skeeks\cms\models\CmsComponentSettings::findByComponentSite($this->component, $model)->one();
        }

        if ($model instanceof User) {
            $settings = \skeeks\cms\models\CmsComponentSettings::findByComponentUser($this->component, $model)->one();
        }

        if ($settings) {
            return $this->_result(Cms::BOOL_Y);
        }

        return $this->_result(Cms::BOOL_N);
    }

    /**
     * @inheritdoc
     */
    protected function _result($value)
    {
        if ($this->trueValue !== true) {
            if ($value == $this->falseValue) {
                return $this->falseIcon;
            } else {
                return $this->trueIcon;
            }
        } else {
            if ($value !== null) {
                return $value ? $this->trueIcon : $this->falseIcon;
            }
            return $this->showNullAsFalse ? $this->falseIcon : $value;
        }

    }
}
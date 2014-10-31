<?php
/**
 * Простой ДатаВью который сам формирует атрибуты
 * TODO: требуется дорабатывать и развивать
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\widgets;

/**
 * Class DetailView
 * @package skeeks\cms\modules\admin\widgets
 */
class DetailView extends \yii\widgets\DetailView
{
    public function init()
    {
        if (!$this->attributes)
        {
            $autoAttributes = [];

            foreach ($this->model->toArray() as $key => $value)
            {
                if (!is_array($value))
                {
                    $autoAttributes[$key] = $value;
                }
            }

            $this->attributes = array_keys($autoAttributes);
        }

        parent::init();
    }
}
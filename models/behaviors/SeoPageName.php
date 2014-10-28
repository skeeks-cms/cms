<?php
/**
 * Генерирует seo_page_name перед insert - ом
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models\behaviors;

use skeeks\sx\filters\string\SeoPageName as FilterSeoPageName;

use yii\db\BaseActiveRecord;
use yii\behaviors\AttributeBehavior;
use yii\base\Event;

/**
 * Class SeoPageName
 * @package skeeks\cms\models\behaviors
 */
class SeoPageName extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive timestamp value
     * Set this property to false if you do not want to record the creation time.
     */
    public $generatedAttribute = 'seo_page_name';

    /**
     * @var
     */
    public $value;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->attributes))
        {
            $this->attributes =
            [
                BaseActiveRecord::EVENT_BEFORE_INSERT => [$this->generatedAttribute],
            ];
        }
    }

    /**
     * @param Event $event
     * @return mixed the value of the user.
     */
    protected function getValue($event)
    {
        if ($this->value === null)
        {
            $filter = new FilterSeoPageName();
            return $filter->filter($this->owner->name);
        } else
        {
            return call_user_func($this->value, $event);
        }
    }


}

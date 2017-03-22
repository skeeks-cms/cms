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

use skeeks\modules\cms\catalog\models\Product;
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
    public $generatedAttribute  = 'code';
    public $fromAttribute       = 'name';
    public $uniqeue             = true;
    public $maxLength           = 64;

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
                BaseActiveRecord::EVENT_BEFORE_UPDATE => [$this->generatedAttribute],
            ];
        }
    }

    /**
     * @param Event $event
     * @return mixed the value of the user.
     */
    public function getValue($event)
    {
        if (!$this->value)
        {
            $filter = new FilterSeoPageName();
            $filter->maxLength = $this->maxLength;

            if ($this->owner->{$this->generatedAttribute})
            {
                $seoPageName = $filter->filter($this->owner->{$this->generatedAttribute});
            } else
            {
                $seoPageName = $filter->filter($this->owner->{$this->fromAttribute});
            }


            //Нужно чтобы поле было уникальным
            if ($this->uniqeue)
            {

                if (!$this->owner->isNewRecord)
                {
                    //Значит неуникально
                    if ($founded = $this->owner->find()->where([
                        $this->generatedAttribute => $seoPageName
                    ])->andWhere(["!=", "id", $this->owner->id])->one())
                    {
                        if ($last = $this->owner->find()->orderBy('id DESC')->one())
                        {
                            $seoPageName = $seoPageName . '-' . $last->id;
                            return $filter->filter($seoPageName);
                        }
                    }
                } else
                {
                    //Значит неуникально
                    if ($founded = $this->owner->find()->where([
                        $this->generatedAttribute => $seoPageName
                    ])->one())
                    {
                        if ($last = $this->owner->find()->orderBy('id DESC')->one())
                        {
                            $seoPageName = $seoPageName . '-' . $last->id;
                            return $filter->filter($seoPageName);
                        }
                    }
                }
            }

            return $seoPageName;
        } else
        {
            return call_user_func($this->value, $event);
        }
    }


}

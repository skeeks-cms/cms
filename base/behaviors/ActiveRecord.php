<?php
/**
 * ActiveRecord
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 03.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\base\behaviors;


use skeeks\cms\Exception;
use yii\base\Behavior;

/**
 * Class Controller
 * @package skeeks\cms\base\behaviors
 */
class ActiveRecord extends Behavior
{
    /**
     * @var \yii\db\ActiveRecord the owner of this behavior
     */
    public $owner;

    /**
     * @param \skeeks\cms\base\db\ActiveRecord $owner
     * @throws Exception
     */
    public function attach($owner)
    {
        if (!$owner instanceof \yii\db\ActiveRecord)
        {
            throw new Exception("Данное поведение рассчитано только для работы с " . \yii\db\ActiveRecord::className());
        }

        parent::attach($owner);
    }

}
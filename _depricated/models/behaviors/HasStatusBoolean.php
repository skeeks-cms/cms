<?php
/**
 *
 * TODO: is depricated (1.2.0)
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.04.2015
 */
namespace skeeks\cms\models\behaviors;
use yii\helpers\ArrayHelper;

/**
 * Class HasStatusBoolean
 * @package skeeks\cms\models\behaviors
 */
class HasStatusBoolean extends \skeeks\cms\base\behaviors\ActiveRecord
{
    public $valueTrue       = '1';
    public $valueFalse      = '0';
    public $trueTitle       = 'да';
    public $falseTitle      = 'нет';

    public $field           = 'status';

    /**
     * @return array
     */
    public function getPossibleStatuses()
    {
        return
        [
            $this->valueFalse   => $this->falseTitle,
            $this->valueTrue    => $this->trueTitle,
        ];
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int) $this->owner->{$this->field};
    }

    /**
     * @return string
     */
    public function getStatusText()
    {
        return (string) ArrayHelper::getValue($this->getPossibleStatuses(), $this->getStatus());
    }
}
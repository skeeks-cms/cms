<?php
/**
 * HasStatus
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\App;
use skeeks\cms\models\behaviors\events\AfterLinkedModel;
use skeeks\cms\models\behaviors\events\AfterUnLinkedModel;
use skeeks\cms\models\Subscribe;
use skeeks\cms\models\User;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 * Class HasSubscribes
 * @package skeeks\cms\models\behaviors
 */
class HasStatus extends \skeeks\cms\base\behaviors\ActiveRecord
{
    const STATUS_DELETED    = 0;
    const STATUS_ACTIVE     = 10;
    const STATUS_INACTIVE   = 20;
    const STATUS_ONMODER    = 30;
    const STATUS_DRAFT      = 40;

    public $field           = 'status';

    public $possibleStatuses    =
    [
        self::STATUS_ACTIVE     => 'Запись активна',
        self::STATUS_DELETED    => 'Отправлена на удаление',
        self::STATUS_ONMODER    => 'Запись на модерации',
        self::STATUS_INACTIVE   => 'Запись скрыта',
        self::STATUS_DRAFT      => 'Черновик'
    ];

    /**
     * @return array
     */
    public function getPossibleStatuses()
    {
        return $this->possibleStatuses;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int) $this->owner->{$this->field};
    }
}
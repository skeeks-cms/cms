<?php
/**
 * HasAdultStatus
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
 * Class HasAdultStatus
 * @package skeeks\cms\models\behaviors
 */
class HasAdultStatus extends \skeeks\cms\base\behaviors\ActiveRecord
{
    const STATUS_NOT_CHEKED                     = 0;
    const STATUS_NO_AGE_LIMIT                   = 10;
    const STATUS_AGE_LIMIT_SIMPLE               = 20;
    const STATUS_AGE_LIMIT_HARD                 = 30;

    public $field           = 'status_adult';

    public $possibleAdultStatuses    =
    [
        self::STATUS_NOT_CHEKED                     => 'Запись не проверена',
        self::STATUS_NO_AGE_LIMIT                   => 'Без возрастных ограничений',
        self::STATUS_AGE_LIMIT_SIMPLE               => 'Легкие возрастные ограничения',
        self::STATUS_AGE_LIMIT_HARD                 => 'Серьезные возврастные ограничения',
    ];

    /**
     * @return array
     */
    public function getPossibleAdultStatuses()
    {
        return $this->possibleAdultStatuses;
    }

    /**
     * @return int
     */
    public function getAdultStatus()
    {
        return (int) $this->owner->{$this->field};
    }
}
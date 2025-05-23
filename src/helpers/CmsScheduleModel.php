<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\helpers;

use yii\base\Model;

/**
 * Промежуток времени
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsScheduleModel extends Model implements CmsScheduleInterface
{
    /**
     * @var Начало промежутка
     */
    public $start_at;

    /**
     * @var Конце промежутка
     */
    public $end_at;

    /**
     * Длительность промежутка в секундах
     *
     * @return int
     */
    public function getDuration()
    {
        if ($this->end_at) {
            $end = $this->end_at;
        } else {
            $end = time();
        }

        return $end - $this->start_at;
    }
}
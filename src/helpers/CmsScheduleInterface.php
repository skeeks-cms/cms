<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\helpers;

/**
 * Промежуток расписания
 *
 * @property int $duration Продолжительность промежутка в секундах
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
interface CmsScheduleInterface
{

    /**
     * Длина промежутка в секундах
     * @return int
     */
    public function getDuration();
}

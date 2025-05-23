<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\helpers;

use skeeks\crm\interfaces\CrmScheduleInterface;
use skeeks\crm\traits\CrmScheduleTrait;
use yii\helpers\ArrayHelper;

/**
 * Класс помощьник для работы с расписанием
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsScheduleHelper
{

    /**
     * Длина промежутка по человечески
     *
     * @param $duration
     * @return string
     */
    static public function durationAsText($duration)
    {
        $seconds = $duration;
        if ($seconds > 3599) {
            $time = \Yii::$app->formatter->asDecimal($seconds / 3600, 1)." ч.";
        } elseif ($seconds > 60) {
            $time = \Yii::$app->formatter->asDecimal($seconds / 60, 0)." мин.";
        } elseif ($seconds > 1) {
            $time = "менее 1 мин.";
        } else {
            $time = "";
        }

        return $time;
    }

    /**
     * Получение суммы всех промежутков
     *
     * @param CrmScheduleInterface[] $crmSchedules
     * @return int
     */
    static public function durationBySchedules($crmSchedules)
    {
        $totalDuration = 0;

        foreach ($crmSchedules as $crmSchedule) {
            $totalDuration = $totalDuration + $crmSchedule->duration;
        }

        return $totalDuration;
    }

    /**
     * @param CrmScheduleInterface[] $crmSchedules
     * @return string
     */
    static public function durationAsTextBySchedules($crmSchedules)
    {
        return self::durationAsText(self::durationBySchedules($crmSchedules));
    }

    /**
     * @param CmsScheduleModel[] $crmSchedules
     * @param string             $startTime
     * @return CmsScheduleModel[]
     */
    static public function getFilteredSchedulesByStartTime($crmSchedules, $startTime = null)
    {
        $result = [];

        if (!$startTime) {
            $startTime = time();
        }

        if ($crmSchedules) {
            foreach ($crmSchedules as $key => $tmpSchedule) {

                $crmSchedule = clone $tmpSchedule;

                $result[$key] = $crmSchedule;

                if ($crmSchedule->start_at >= $startTime) {
                    continue;
                } else {
                    if ($crmSchedule->end_at >= $startTime) {
                        $crmSchedule->end_at = $startTime;
                    } else {
                        ArrayHelper::remove($result, $key);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param CrmScheduleTrait[] $crmSchedules
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    static public function getAsTextBySchedules($crmSchedules = [])
    {
        $result = [];

        if ($crmSchedules) {
            foreach ($crmSchedules as $crmSchedule) {
                $result[] = "<small>".\Yii::$app->formatter->asTime((int) $crmSchedule->start_at, "short")." — ".($crmSchedule->end_at ? \Yii::$app->formatter->asTime((int) $crmSchedule->end_at, "short") : "сейчас")."</small>";
            }
        }

        return implode("<br />", $result);
    }

    /**
     * @param array  $wortime Рабочий график
     * @param string $date День по которому нужны промежутки времени
     *
     * @return CmsScheduleModel[]
     */
    static public function getSchedulesByWorktimeForDate(array $wortime, string $date)
    {
        $schedules = [];

        $week_day = date("w", strtotime($date));
        //0 - воскресенье
        if ($week_day == 0) {
            $week_day = 7;
        }

        //В графике пользователя указан грфик в его часовом поясе
        foreach ($wortime as $row) {
            if ($days = ArrayHelper::getValue($row, "day")) {
                $startHour = ArrayHelper::getValue($row, "startHour");
                $startMinutes = ArrayHelper::getValue($row, "startMinutes");
                $endHour = ArrayHelper::getValue($row, "endHour");
                $endMinutes = ArrayHelper::getValue($row, "endMinutes");

                if (in_array($week_day, $days)) {

                    $timeZone = new \DateTimeZone(\Yii::$app->formatter->timeZone); //Тут брать timezone из настроек пользователя

                    $schedules[] = new CmsScheduleModel([
                        'start_at' => (new \DateTime($date." {$startHour}:{$startMinutes}", $timeZone))->getTimestamp(),
                        'end_at'   => (new \DateTime($date." {$endHour}:{$endMinutes}", $timeZone))->getTimestamp(),
                    ]);
                }
            }
        }

        return $schedules;
    }

    /**
     * Получить дни по периоду
     *
     * @param $start_at
     * @param $end_at
     * @return array
     */
    static public function getSchedulesDays($start_at, $end_at)
    {
        $result = [];

        $result[] = date("Y-m-d", $start_at);
        $oneDay = 3600*24;

        for ($i = 1; $i <= 1000; $i++) {

            $start_at = $start_at + $oneDay;

            if ($start_at > $end_at) {
                break;
            }

            $result[$i] = date("Y-m-d", $start_at);

        }

        return $result;
    }
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.05.2015
 */

namespace skeeks\cms\models\behaviors\traits;

use skeeks\cms\models\CmsLog;
use skeeks\cms\models\queries\CmsLogQuery;
use yii\helpers\ArrayHelper;

/**
 * @property CmsLog[] $logs
 *
 * @property string   $skeeksModelCode
 * @property string   $skeeksModelName
 * @property string   $skeeksModelClass
 */
trait HasLogTrait
{
    /**
     * @return string
     */
    public function getSkeeksModelCode()
    {
        return static::class;
    }

    /**
     * @param CmsLog $cmsLog
     * @return string
     */
    public function renderLog(CmsLog $cmsLog)
    {
        return (string)$cmsLog->render();
    }

    /**
     * @return CmsLogQuery
     */
    public function getLogs()
    {
        $q = CmsLog::find()
            ->andWhere(['model_code' => $this->skeeksModelCode])
            ->andWhere(['model_id' => $this->id])
            ->orderBy(['created_at' => SORT_DESC]);

        $q->multiple = true;

        return $q;
    }
    
    /*static public function findLogs()
    {
        $q = CmsLog::find()
            ->andWhere(['model_code' => self::getSkeeksModelCode()])
            ->orderBy(['created_at' => SORT_DESC]);
    }*/
}
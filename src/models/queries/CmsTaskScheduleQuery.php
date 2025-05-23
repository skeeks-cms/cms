<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsUser;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
use yii\db\ActiveRecord;
use yii\db\Expression;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsTaskScheduleQuery extends CmsActiveQuery
{
    /**
     * @param $task
     * @return $this
     */
    public function task($model)
    {
        $model_id = null;
        
        if ($model instanceof ActiveRecord) {
            $model_id = $model->id;
        } else {
            $model_id = (int) $model;
        }
        
        $this->andWhere(['cms_task_id' => $model_id]);
        
        return $this;
    }
    
    /**
     * @return $this
     */
    public function notEnd()
    {
        $this->andWhere(['end_at' => null]);
        return $this;
    }

    /**
     * @return CmsUserScheduleQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function today()
    {
        $date = \Yii::$app->formatter->asDate(time(), "php:Y-m-d");

        return $this->andWhere([
            'or',
            new Expression("FROM_UNIXTIME(start_at, '%Y-%m-%d') = '{$date}'"),
            new Expression("FROM_UNIXTIME(end_at, '%Y-%m-%d') = '{$date}'"),
        ]);
    }

}
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
use yii\db\Expression;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsUserScheduleQuery extends CmsActiveQuery
{
    /**
     * @param string|array $types
     * @return $this
     */
    public function user($user)
    {
        $user_id = null;
        
        if ($user instanceof CmsUser) {
            $user_id = $user->id;
        } else {
            $user_id = (int) $user;
        }
        
        $this->andWhere(['cms_user_id' => $user_id]);
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
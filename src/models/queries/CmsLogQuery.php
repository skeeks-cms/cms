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
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsLogQuery extends CmsActiveQuery
{
    /**
     * @param string|array $types
     * @return $this
     */
    public function logType($types)
    {
        $this->andWhere(['log_type' => $types]);
        return $this;
    }
    
    /**
     * @return $this
     */
    public function comments()
    {
        return $this->logType(CmsLog::LOG_TYPE_COMMENT);
    }

    /**
     * @return $this
     */
    public function pinned()
    {
        return $this->andWhere(['is_pinned' => 1]);
    }

    /**
     * Entries that are not pinned: a feed shows pinned entries once, on top.
     *
     * @return $this
     */
    public function notPinned()
    {
        return $this->andWhere(['or', ['is_pinned' => 0], ['is_pinned' => null]]);
    }

    /**
     * @return $this
     */
    public function results()
    {
        return $this->pinned();
    }

    /**
     * @return $this
     */
    public function phoneCalls()
    {
        return $this->logType(CmsLog::LOG_TYPE_PHONE_CALL);
    }

    /**
     * Entries of the activity feed of CRM cards: calls, comments and the types
     * registered by packages in Skeeks::$activityLogTypes.
     *
     * @return $this
     */
    public function activity()
    {
        return $this->logType(\Yii::$app->skeeks->activityLogTypes);
    }


}

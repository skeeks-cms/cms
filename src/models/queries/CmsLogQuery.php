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


}
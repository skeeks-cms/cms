<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\log;

/**
 * A renderer of a log type (Skeeks::$logRenderers) whose entries are written
 * to the feed of a company or a person on behalf of another record (a
 * proposal) implements it to show them only to who sees that record.
 * CrmAccess::feedCondition() asks it for the employees whose feed of the
 * client is limited (the level "Свои сделки", the general pool): the other
 * entries of the type are hidden there.
 */
interface CmsLogFeedVisibilityInterface
{
    /**
     * @param \skeeks\cms\models\User $user the employee
     * @param string $logTable the CmsLog table name of the query
     * @return array a where condition the visible entries of this log type match
     */
    public function feedVisibleCondition($user, string $logTable): array;
}

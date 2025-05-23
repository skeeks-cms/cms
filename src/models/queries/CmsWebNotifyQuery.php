<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\User;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\rbac\CmsManager;
use yii\helpers\ArrayHelper;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsWebNotifyQuery extends CmsActiveQuery
{
    /**
     * @param string|array $types
     * @return $this
     */
    public function notRead()
    {
        $this->andWhere(['is_read' => 0]);
        return $this;
    }
    /**
     * @param string|array $types
     * @return $this
     */
    public function notPopup()
    {
        $this->andWhere(['is_popup' => 0]);
        return $this;
    }
}
<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use skeeks\cms\models\CmsContent;
use skeeks\cms\query\CmsActiveQuery;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsContentQuery extends CmsActiveQuery
{
    /**
     * @param string $role
     * @return CmsContentQuery
     */
    public function baseRole(string $role)
    {
        return $this->andWhere(['base_role' => trim($role)]);
    }

    /**
     * @return CmsContentQuery
     */
    public function isProducts()
    {
        return $this->baseRole(CmsContent::ROLE_PRODUCTS);
    }
}
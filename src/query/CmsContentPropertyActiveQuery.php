<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\query;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsContentPropertyActiveQuery extends CmsActiveQuery
{
    /**
     * @param mixed $id
     * @return CmsContentPropertyActiveQuery
     */
    public function sxId(mixed $id)
    {
        return $this->andWhere(['sx_id' => $id]);
    }
}
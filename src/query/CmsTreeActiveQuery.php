<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\query;

use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Tree;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsTreeActiveQuery extends CmsActiveQuery
{
    /**
     * @param mixed $id
     * @return CmsTreeActiveQuery
     */
    public function sxId(mixed $id)
    {
        return $this->andWhere(['sx_id' => $id]);
    }
}

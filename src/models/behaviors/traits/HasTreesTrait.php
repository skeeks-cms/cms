<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */

namespace skeeks\cms\models\behaviors\traits;

use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\CmsTree;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @method ActiveQuery getElementTrees()
 * @method ActiveQuery getCmsTrees()
 * @method int[] getTreeIds()
 *
 * @property CmsContentElementTree[] $elementTrees
 * @property int[] $treeIds
 * @property CmsTree[] $cmsTrees
 */
trait HasTreesTrait
{
}
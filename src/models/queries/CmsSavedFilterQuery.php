<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models\queries;

use http\Exception\InvalidArgumentException;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsTree;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\cms\shop\models\ShopBrand;
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsSavedFilterQuery extends CmsActiveQuery
{
    /**
     * @param CmsTree|int|string|array $cmsTree
     * @return CmsSavedFilterQuery
     */
    public function tree(mixed $cmsTree)
    {
        $cms_tree_id = '';
        if ($cmsTree instanceof CmsTree) {
            $cms_tree_id = $cmsTree->id;
        } elseif (is_int($cmsTree)) {
            $cms_tree_id = $cmsTree;
        } elseif(is_string($cmsTree)) {
            $cms_tree_id = (int) $cmsTree;
        } elseif(is_array($cmsTree)) {
            $cms_tree_id = (array) $cmsTree;
        } else {
            throw new InvalidArgumentException("Ошибка");
        }
        return $this->andWhere(['cms_tree_id' => $cms_tree_id]);
    }

    /**
     * @param CmsTree|int|string|array $value
     * @return CmsSavedFilterQuery
     */
    public function brand(mixed $value)
    {
        $brand_id = '';
        if ($value instanceof ShopBrand) {
            $brand_id = $value->id;
        } elseif (is_int($value)) {
            $brand_id = $value;
        } elseif(is_string($value)) {
            $brand_id = (int) $value;
        } elseif(is_array($value)) {
            $brand_id = (array) $value;
        } else {
            throw new InvalidArgumentException("Ошибка");
        }
        return $this->andWhere(['shop_brand_id' => $brand_id]);
    }

}
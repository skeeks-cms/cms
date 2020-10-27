<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.03.2015
 */

namespace skeeks\cms\query;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsSite;
use yii\db\ActiveQuery;

/**
 * Class CmsActiveQuery
 * @package skeeks\cms\query
 */
class CmsActiveQuery extends ActiveQuery
{
    public $is_active = true;

    /**
     * @param bool $state
     * @return $this
     */
    public function active($state = true)
    {
        if ($this->is_active === true) {
            return $this->andWhere([$this->getPrimaryTableName().'.is_active' => $state]);
        }

        return $this->andWhere([$this->getPrimaryTableName().'.active' => ($state == true ? Cms::BOOL_Y : Cms::BOOL_N)]);
    }

    /**
     * @param bool $state
     * @return $this
     */
    public function default($state = true)
    {
        if ($state === true) {
            return $this->andWhere([$this->getPrimaryTableName().'.is_default' => 1]);
        } else {
            return $this->andWhere(['!=', $this->getPrimaryTableName().'.is_default', 1]);
        }
    }
    /**
     * @param int $order
     * @return CmsActiveQuery
     */
    public function sort($order = SORT_ASC)
    {
        return $this->orderBy([$this->getPrimaryTableName().'.priority' => $order]);
    }

    /**
     * @depricated
     *
     * @param bool $state
     * @return CmsActiveQuery
     */
    public function def($state = true)
    {
        return $this->andWhere(['def' => ($state == true ? Cms::BOOL_Y : Cms::BOOL_N)]);
    }

    /**
     * Фильтрация по сайту
     *
     * @return CmsActiveQuery
     */
    public function cmsSite($cmsSite = null)
    {
        if (!$cmsSite instanceof CmsSite) {
            $cmsSite = \Yii::$app->skeeks->site;
        }

        $alias = $this->getPrimaryTableName();
        if ($this->from) {
            foreach ($this->from as $code => $table) {
                if ($table == $alias) {
                    $alias = $code;
                }
            }
        }
        return $this->andWhere([$alias.'.cms_site_id' => $cmsSite->id]);
    }
}

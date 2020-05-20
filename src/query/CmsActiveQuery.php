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
            return $this->andWhere([$this->getPrimaryTableName() . '.is_active' => $state]);
        }

        return $this->andWhere([$this->getPrimaryTableName() . '.active' => ($state == true ? Cms::BOOL_Y : Cms::BOOL_N)]);
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

        return $this->andWhere([$this->getPrimaryTableName().'.cms_site_id' => $cmsSite->id]);
    }
}

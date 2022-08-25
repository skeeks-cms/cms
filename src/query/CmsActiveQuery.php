<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 09.03.2015
 */

namespace skeeks\cms\query;

use http\Exception\InvalidArgumentException;
use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\User;
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
     * @param int|strin|User $user
     * @return static
     */
    public function createdBy($user = null)
    {
        $user_id = null;
        
        if (!$user) {
            $user = \Yii::$app->user->identity;
        }
        
        if (is_int($user)) {
            $user_id = $user;
        } elseif (is_string($user)) {
            $user_id = (int) $user;
        } elseif ($user instanceof User) {
            $user_id = (int) $user->id;
        } else {
            throw new InvalidArgumentException("Parametr user invalid");
        }
        return $this->andWhere([$this->getPrimaryTableName().'.created_by' => $user_id]);
    }
    
    /**
     * @param int $order
     * @return $this
     */
    public function sort($order = SORT_ASC)
    {
        return $this->orderBy([$this->getPrimaryTableName().'.priority' => $order]);
    }

    /**
     * Фильтрация по сайту
     * @param int|CmsSite $cmsSite
     *
     * @return CmsActiveQuery
     */
    public function cmsSite($cmsSite = null)
    {
        $cms_site_id = null;

        if (is_int($cmsSite)) {
            $cms_site_id = $cmsSite;
        } elseif ($cmsSite instanceof CmsSite) {
            $cms_site_id = $cmsSite->id;
        } else {
            $cms_site_id = \Yii::$app->skeeks->site->id;
        }

        $alias = $this->getPrimaryTableName();

        if ($this->from) {
            foreach ($this->from as $code => $table) {
                if ($table == $alias) {
                    $alias = $code;
                }
            }
        }

        return $this->andWhere([$alias.'.cms_site_id' => $cms_site_id]);
    }

    /**
     * @param string $word
     * @return $this
     */
    public function search($word = '')
    {
        $modelClass = $this->modelClass;
        if ($modelClass::getTableSchema()->columns) {
            $where = [];
            $where[] = "or";
            foreach ($modelClass::getTableSchema()->columns as $key => $column)
            {
                $where[] = ['like', $this->getPrimaryTableName() . "." . $key, $word];
            }

            $this->andWhere($where);
        }

        return $this;
    }


    /**
     * @depricated
     *
     * @param bool $state
     * @return $this
     */
    public function def($state = true)
    {
        return $this->andWhere(['def' => ($state == true ? Cms::BOOL_Y : Cms::BOOL_N)]);
    }
}

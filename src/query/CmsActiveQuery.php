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
use skeeks\cms\helpers\PhoneHelper;
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
     * Разбор пользовательского поискового запроса на отдельные слова.
     *
     * Телефон пишут как угодно и обязательно с пробелами («+7 495 005-79-26»),
     * поэтому такой запрос остаётся одним словом. Всё остальное режется на
     * слова: «Иванов Иван» должен находить клиента, у которого имя и фамилия
     * лежат в разных колонках.
     *
     * @param string|null $word
     * @param int $limit максимум слов, чтобы длинная фраза не собрала тяжёлый запрос
     * @return string[]
     */
    protected function searchWords($word, $limit = 5)
    {
        $word = trim((string)$word);

        if ($word === '') {
            return [];
        }

        if (PhoneHelper::isSearchablePhone($word)) {
            return [$word];
        }

        $words = preg_split('/\s+/u', $word, -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return [];
        }

        return array_slice($words, 0, $limit);
    }

    /**
     * Условие поиска по идентификатору, если запрос выглядит как идентификатор.
     *
     * @param string $word
     * @return array|null
     */
    protected function searchIdCondition($word)
    {
        if (!ctype_digit($word) || strlen($word) > 7) {
            return null;
        }

        return [$this->getPrimaryTableName().'.id' => (int)$word];
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

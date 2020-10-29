<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\widgets;

use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/*
    'modelClass' => CrmContractor::class,
    'searchQuery' => function($word = '') {
        $query = CrmContractor::find()->typeProject();
        if ($word) {
            $query->search($word);
        }
        return $query;
    },
 */
/**
 *
 * @property ActiveQuery $searchQuery;
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AjaxSelectModel extends AjaxSelect
{
    /**
     * @var null
     */
    public $modelClass = null;

    /**
     * @var string
     */
    public $modelPkAttribute = "id";

    /**
     * @var string
     */
    public $modelShowAttribute = "asText";

    /**
     * @var int
     */
    public $limit = 25;

    /**
     * @var null
     */
    public $searchQuery = null;

    /**
     * @var array
     */
    public $searchFields = ['id', 'name'];

    /**
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $modelClass = $this->modelClass;

        if (!$modelClass) {
            throw new InvalidConfigException("modelClass is required");
        }

        if (!$this->valueCallback) {
            $this->valueCallback = function ($value) use ($modelClass) {
                return \yii\helpers\ArrayHelper::map($modelClass::find()->where([$this->modelPkAttribute => $value])->all(), $this->modelPkAttribute, $this->modelShowAttribute);
            };
        }

        if (!$this->dataCallback) {
            $this->dataCallback = function ($word = '') use ($modelClass) {

                $query = $this->_createSearchQuery($word);
                if (!$query->limit) {
                    $data = $query->limit($this->limit);
                }

                $data = $query->all();


                $result = [];

                if ($data) {
                    foreach ($data as $model) {
                        $result[] = [
                            'id'   => $model->{$this->modelPkAttribute},
                            'text' => $model->{$this->modelShowAttribute},
                        ];
                    }
                }

                return $result;
            };
        }

        parent::init();
    }

    /**
     * @return ActiveQuery|null
     */
    protected function _createSearchQuery($word = '')
    {
        $modelClass = $this->modelClass;

        if (is_callable($this->searchQuery)) {
            $query = call_user_func($this->searchQuery, $word);
        } else {
            $query = $modelClass::find();
            if ($word) {
                if (method_exists($query, 'search')) {
                    $query->search($word);
                } else {
                    if ($this->searchFields) {
                        $where = [];
                        $where[] = 'or';

                        foreach ($this->searchFields as $field)
                        {
                            $where[] = ['like', $field, $word];
                        }

                        $query->andWhere($where);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * @param $className
     * @return $this
     */
    public function setModelClassName($className)
    {
        $this->modelClass = $className;
        return $this;
    }
}
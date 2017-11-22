<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.10.2015
 */

namespace skeeks\cms\helpers;

use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Tree;
use yii\caching\TagDependency;

/**
 * Class TreeOptions
 * @package skeeks\cms\helpers
 */
class TreeOptions extends \skeeks\cms\models\Tree
{
    /**
     * @var string символ будет добавляться перед називанием раздела.
     */
    public $repeat = '. ';

    /**
     * @var array
     */
    private $_filter = [];

    /**
     *
     * @param CmsTree $parentTree
     * @return array|mixed
     */
    public static function getAllMultiOptions($parentTree = null)
    {
        $key = [
            ROOT_DIR,
            static::className()
        ];

        if ($parentTree) {
            if ($parentTree instanceof CmsTree) {
                $parentTreeId = $parentTree->id;
            } else {
                $parentTreeId = $parentTree;
            }

            $key[] = $parentTreeId;
        }

        $cacheKey = md5(implode($key));

        $dependency = new TagDependency([
            'tags' =>
                [
                    (new CmsTree())->getTableCacheTag(),
                ],
        ]);

        $options = \Yii::$app->cache->get($cacheKey);

        if (!$options) {
            if ($parentTree) {
                $options = \yii\helpers\ArrayHelper::map(
                    self::findOne($parentTreeId)->getMultiOptions(),
                    "id",
                    "name"
                );
            } else {
                $options = \yii\helpers\ArrayHelper::map(
                    (new static())->getMultiOptions(),
                    "id",
                    "name"
                );
            }


            \Yii::$app->cache->set($cacheKey, $options, 0, $dependency);
        }

        return $options;
    }

    /**
     * Строит массив для селекта
     * @param array $tree
     * @return array
     */
    public function getMultiOptions($includeSelf = true)
    {
        $this->_tmpResult = [];
        if (!$this->isNewRecord && $includeSelf) {
            $this->_tmpResult[$this->id] = $this;
        }
        return $this->_buildTreeArrayRecursive($this, $this->_filter);
    }


    protected $_tmpResult = [];

    /**
     * Строит рекурсивно массив дерева
     * @param \skeeks\cms\models\Tree $model
     * @param array $filter
     * @return array
     */
    private function _buildTreeArrayRecursive(Tree $model, $filter)
    {
        $is_filter_set = !empty($filter);

        if ($model->isNewRecord) {
            $childs = static::findRoots()->all();
        } else {
            $childs = $model->children;
        }


        foreach ($childs as $child) {
            $level = $child->level;
            $id = $child->id;
            if (!$is_filter_set || in_array($id, $filter)) {
                $name = $child->name;
                if ($level == 0) {
                    $name = "[" . $child->site->name . "] " . $child->name;
                }

                $child->name = str_repeat($this->repeat, $level) . $name;
                $this->_tmpResult[$id] = $child;
            }
            $this->_buildTreeArrayRecursive($child, $filter);
        }

        return $this->_tmpResult;
    }

    /**
     * Фильтрует дерево по заданному условию
     * @param $condition
     */
    public function filter($condition)
    {
        $items = $this->find()->where($condition)->all();
        $this->_filter = $this->_getTreeNodesIds($items);
    }

    /**
     * Возвращает массив id элементов дерева
     * @param $nodes
     * @return array
     */
    private function _getTreeNodesIds($nodes)
    {
        $result = [];

        foreach ($nodes as $node) {
            $result[] = $node->id;
        }

        return $result;
    }
}
<?php
/**
 * Tree
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\helpers;

/**
 * Class Tree
 * @package skeeks\cms\models\helpers
 */
class Tree extends \skeeks\cms\models\Tree
{
    private $filter = array();

    /**
     * Строит массив для селекта
     * @param array $tree
     * @return array
     */
    public function getMultiOptions()
    {
        return $this->buildTreeArrayRecursive($this, $this->filter);
    }

    /**
     * Строит рекурсивно массив дерева
     * @param \skeeks\cms\models\Tree $model
     * @param array $filter
     * @return array
     */
    private function buildTreeArrayRecursive($model, $filter)
    {
        $result = [];
        $is_filter_set = !empty($filter);
        $childs = $model->findChildrens()->all();
        foreach ($childs as $child) {
            $level = $child->getLevel();
            $id = $child->id;
            if (!$is_filter_set || in_array($id, $filter)) {
                $result[$id] = str_repeat("-", $level) . $child->name;
            }
            $next_childs = $this->buildTreeArrayRecursive($child, $filter);
            $result = array_merge($result, $next_childs);
        }
        return $result;
    }

    /**
     * Фильтрует дерево по заданному условию
     * @param $condition
     */
    public function filter($condition)
    {
        $items = $this->find()->where($condition)->all();
        $this->filter = $this->getTreeNodesIds($items);
    }

    /**
     * Возвращает массив id элементов дерева
     * @param $nodes
     * @return array
     */
    private function getTreeNodesIds($nodes)
    {
        $result = [];
        foreach ($nodes as $node) {
            $result[] = $node->id;
        }
        return $result;
    }
}
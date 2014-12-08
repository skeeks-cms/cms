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
    /**
     * @var string символ будет добавляться перед називанием раздела.
     */
    public $repeat = '— ';

    /**
     * @var array
     */
    private $_filter = [];

    /**
     * Строит всего дерева
     * @return array
     */
    static public function getAllMultiOptions()
    {
        return \yii\helpers\ArrayHelper::map(
             (new static())->getMultiOptions(),
             "id",
             "name"
        );
    }


    /**
     * Строит массив для селекта
     * @param array $tree
     * @return array
     */
    public function getMultiOptions()
    {
        return $this->_buildTreeArrayRecursive($this, $this->_filter);
    }

    /**
     * Строит рекурсивно массив дерева
     * @param \skeeks\cms\models\Tree $model
     * @param array $filter
     * @return array
     */
    private function _buildTreeArrayRecursive($model, $filter)
    {
        $result = [];
        $is_filter_set = !empty($filter);
        $childs = $model->findChildrens()->all();

        foreach ($childs as $child)
        {
            $level  = $child->getLevel();
            $id     = $child->id;
            if (!$is_filter_set || in_array($id, $filter))
            {
                $child->name = str_repeat($this->repeat, $level) . $child->name;
                $result[$id] = $child;
            }
            $next_childs    = $this->_buildTreeArrayRecursive($child, $filter);

            $result         = array_merge($result, $next_childs);

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

        foreach ($nodes as $node)
        {
            $result[] = $node->id;
        }

        return $result;
    }
}
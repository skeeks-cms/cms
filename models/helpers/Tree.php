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
class Tree
{
    static public function getMultiOptions()
    {
        $tree = new \skeeks\cms\models\Tree();
        return self::buildTreeArrayRecursive($tree);
    }

    /**
     * Строит рекурсивно массив дерева
     * @param \skeeks\cms\models\Tree $tree
     * @return array
     */
    public static function buildTreeArrayRecursive(\skeeks\cms\models\Tree $tree)
    {
        $result = [];
        $childs = $tree->findChildrens()->all();
        foreach ($childs as $child) {
            $level = $child->getLevel();
            $result[$child->id] = str_repeat("-", $level) . $child->name;
            $next_childs = self::buildTreeArrayRecursive($child);
            $result = array_merge($result, $next_childs);
        }
        return $result;
    }
}
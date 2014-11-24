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
    /**
     * @return array
     */
    static public function getMultiOptions()
    {
        $result = [];
        $tree = new \skeeks\cms\models\Tree();
        foreach ($tree->findRoots()->all() as $tree)
        {
            $result[$tree->id] = $tree->name;
        }

        return $result;
    }
}
<?php
/**
 * CanBeLinkedToTree
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 12.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;
use skeeks\cms\base\behaviors\ActiveRecord;
use skeeks\cms\models\Tree;

/**
 * Class HasLinkedModels
 * @package skeeks\cms\models\behaviors
 */
class CanBeLinkedToTree extends ActiveRecord
{
    public $treeIdsFieldName = 'tree_ids';

    /**
     *
     * Найти модель к которой привязана текущая модель.
     *
     * @return null|\yii\db\ActiveRecord
     */
    public function findLinkedToTree()
    {
        if ($ids = $this->owner->{$this->$treeIdsFieldName})
        {
            return null;
        }

        return Tree::find()->where(['id' => $ids]);
    }

}
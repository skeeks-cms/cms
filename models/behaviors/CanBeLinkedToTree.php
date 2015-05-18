<?php
/**
 * TODO: is depricated
 *
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

    public function attach($owner)
    {
        $owner->attachBehavior("implode_tree_ids", [
            "class"  => Implode::className(),
            "fields" =>  [
                "tree_ids"
            ]
        ]);

        parent::attach($owner);
    }

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
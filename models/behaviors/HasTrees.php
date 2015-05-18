<?php
/**
 * Может привязываться к разделам через связующую таблицу
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */

namespace skeeks\cms\models\behaviors;
use skeeks\cms\base\behaviors\ActiveRecord;
use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
use yii\db\ActiveQuery;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\ErrorHandler;

/**
 * Class HasTrees
 * @package skeeks\cms\models\behaviors
 */
class HasTrees extends ActiveRecord
{
    public $_tree_ids = null;

    /**
     * @var string названием модели таблицы через которую связаны элементы и разделы.
     */
    public $elementTreesClassName;

    /**
     * @return array
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_UPDATE    => "afterSaveTree",
            BaseActiveRecord::EVENT_AFTER_INSERT    => "afterSaveTree",
        ];
    }

    /**
     * @param AfterSaveEvent $event
     */
    public function afterSaveTree($event)
    {
        $savedValues = [];
        //Смотрим какие разделы привязаны
        //TODO: доработать, не удалять каждый раз
        if ($elementTrees = $this->getElementTrees()->all())
        {
            foreach ($elementTrees as $elementTree)
            {
                $elementTree->delete();
            }
        }

        if ($ids = (array) $this->owner->treeIds)
        {
            $className = $this->elementTreesClassName;

            foreach ($ids as $treeId)
            {
                $elementTree = new $className([
                    'element_id'    => $this->owner->id,
                    'tree_id'       => $treeId,
                ]);

                $elementTree->save();
            }
        }
    }


    /**
     * @return int[]
     */
    public function getTreeIds()
    {
        if ($this->owner->_tree_ids === null)
        {
            $this->owner->_tree_ids = [];

            if ($this->owner->elementTrees)
            {
                $this->_tree_ids = (array) ArrayHelper::map($this->owner->elementTrees, "tree_id", "tree_id");
            }

            return $this->_tree_ids;
        }

        return (array) $this->_tree_ids;
    }

    /**
     * @param $ids
     * @return $this
     */
    public function setTreeIds($ids)
    {
        $this->owner->_tree_ids = $ids;
        return $this;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getElementTrees()
    {
        $className = $this->elementTreesClassName;
        return $this->owner->hasMany($className::className(), ['element_id' => 'id']);
    }
}
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

use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\ErrorHandler;

/**
 * @property ActiveRecord $owner
 *
 * Class HasTrees
 * @package skeeks\cms\models\behaviors
 */
class HasTrees extends Behavior
{
    public $_tree_ids = null;

    /**
     * @var string названием модели таблицы через которую связаны элементы и разделы.
     */
    public $elementTreesClassName = '\skeeks\cms\models\CmsContentElementTree';

    /**
     * @var string класс разделов
     */
    public $treesClassName = '\skeeks\cms\models\CmsTree';

    /**
     * @var string
     */
    public $attributeElementName = 'element_id';

    /**
     * @var string
     */
    public $attributeTreeName = 'tree_id';

    /**
     * @return array
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_UPDATE => "afterSaveTree",
            BaseActiveRecord::EVENT_AFTER_INSERT => "afterSaveTree",
        ];
    }

    /**
     * @param AfterSaveEvent $event
     */
    public function afterSaveTree($event)
    {
        if ($this->owner->_tree_ids === null) {
            return $this;
        }

        //Старые атрибуты
        $oldIds = (array)ArrayHelper::map($this->owner->elementTrees, $this->attributeTreeName,
            $this->attributeTreeName);
        $newIds = (array)$this->owner->treeIds; //Новые


        //Если старых не было, просто записать новые
        $writeIds = [];
        $deleteIds = [];

        if (!$oldIds) {
            $writeIds = $newIds;
        } else {
            foreach ($oldIds as $oldId) {
                //Старый элемент есть в новом массиве, его не трогаем он остается
                if (in_array($oldId, $newIds)) {

                } else {
                    $deleteIds[] = $oldId; //Иначе его надо удалить.
                }
            }

            foreach ($newIds as $newId) {
                //Если новый элемент уже был, то ничего не делаем
                if (in_array($newId, $oldIds)) {

                } else {
                    $writeIds[] = $newId; //Иначе запишем
                }
            }
        }


        //Есть элементы на удаление
        if ($deleteIds) {
            $elementTrees = $this->owner->getElementTrees()->andWhere([
                $this->attributeTreeName => $deleteIds
            ])->limit(count($deleteIds))->all();

            foreach ($elementTrees as $elementTree) {
                $elementTree->delete();
            }
        }

        //Есть элементы на запись
        if ($writeIds) {
            $className = $this->elementTreesClassName;

            foreach ($writeIds as $treeId) {
                if ($treeId) {
                    $elementTree = new $className([
                        $this->attributeElementName => $this->owner->id,
                        $this->attributeTreeName => $treeId,
                    ]);

                    $elementTree->save(false);
                }
            }
        }

        $this->owner->_tree_ids = null;
    }


    /**
     * @return int[]
     */
    public function getTreeIds()
    {
        if ($this->owner->_tree_ids === null) {
            $this->owner->_tree_ids = [];

            if ($this->owner->elementTrees) {
                $this->_tree_ids = (array)ArrayHelper::map($this->owner->elementTrees, $this->attributeTreeName,
                    $this->attributeTreeName);
            }

            return $this->_tree_ids;
        }

        return (array)$this->_tree_ids;
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
        return $this->owner->hasMany($className::className(), [$this->attributeElementName => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTrees()
    {
        $className = $this->elementTreesClassName;
        $treesClassName = $this->treesClassName;

        return $this->owner->hasMany($treesClassName::className(), ['id' => 'tree_id'])
            ->via('elementTrees');
    }
}
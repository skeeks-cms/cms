<?php
/**
 * TreeBehavior
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\base\behaviors\ActiveRecord as ActiveRecordBehavior;
use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\Exception;
use skeeks\cms\models\Tree;
use yii\db\ActiveQuery;

/**
 * Class HasVotes
 * @package skeeks\cms\models\behaviors
 */
class TreeBehavior extends ActiveRecordBehavior
{
    public $pidAttrName         = "pid";
    public $pidsAttrName        = "pids";
    public $levelAttrName       = "level";
    public $dirAttrName         = "dir";
    public $pageAttrName        = "seo_page_name";
    public $hasChildrenAttrName = "has_children";


    public function attach($owner)
    {
        $owner->attachBehavior("implode_tree",  [
            "class"  => Implode::className(),
            "delimetr" => "/",
            "fields" =>  ["pids"]
        ]);

        parent::attach($owner);
    }

    public function events()
	{
		return [
			/*ActiveRecord::EVENT_INIT => 'afterConstruct',
			ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',*/
		];
	}


    /**
	 * Named scope. Gets descendants for node.
	 * @param int $depth the depth.
	 * @return CActiveRecord the owner.
	 */
	public function descendants($depth = null)
	{
	}
	/**
	 * Named scope. Gets children for node (direct descendants only).
	 * @return CActiveRecord the owner.
	 */
	public function childrens()
	{
		return $this->descendants(1);
	}
	/**
	 * Named scope. Gets ancestors for node.
	 * @param int $depth the depth.
	 * @return CActiveRecord the owner.
	 */
	public function ancestors($depth=null)
	{

	}

    
    public function addNode(Tree $target)
    {
        //TODO:: больше проверок
        if ($this->owner->isNewRecord)
        {
            throw new Exception('Прежде чем добавлять в эту ноду что либо, нужно сохранить ее');
        }

        if ($this->owner->primaryKey() == $target->primaryKey())
        {
            throw new Exception('Нельзя добавить раздел в раздел');
        }

        $target->setAttribute($this->levelAttrName, ($this->getLevel() + 1));
        $target->setAttribute($this->pidAttrName, $this->owner->primaryKey());
    }

    /**
     *
     * Корневые разделы дерева.
     *
     * @return ActiveQuery
     */
	public function roots()
	{
		return $this->owner->find()->where([$this->levelAttrName => 0])->orderBy(["priority" => SORT_DESC]);
	}

    /**
     * Родительский элемент
     * @return array|bool|null|\yii\db\ActiveRecord
     */
	public function parent()
	{
        if ($this->owner->isNewRecord)
        {
            return false;
        }

        if (!$this->hasParent() || $this->isRoot())
        {
            return false;
        }

		return $this->owner->find()->where([$this->pidAttrName => $this->getPid()])->one();
	}

    /**
     * У текущего раздела есть ли родительский элемент
     * @return bool
     */
    public function hasParent()
    {
        return (bool) $this->getPid();
    }

    /**
     * Корневая нода?
     * @return bool
     */
    public function isRoot()
    {
        return (bool) ($this->getLevel() == 0);
    }


    /**
     * @return array
     */
    public function getPids()
    {
        return (array) $this->owner->{$this->pidsAttrName};
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return (int) $this->owner->{$this->levelAttrName};
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return (int) $this->owner->{$this->pidAttrName};
    }
}
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
use yii\validators\Validator;

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
	 * Named scope. Gets ancestors for node.
	 * @param int $depth the depth.
	 * @return CActiveRecord the owner.
	 */
	public function ancestors($depth=null)
	{

	}

    /**
     * Найти непосредственных детей ноды
     * @return ActiveQuery
     */
	public function findChildrens()
	{
		return $this->owner->find()->where([$this->pidAttrName => $this->owner->primaryKey])->orderBy(["priority" => SORT_DESC]);
	}



    
    public function processAddNode(Tree $target)
    {
        //TODO:: больше проверок
        if ($this->owner->isNewRecord)
        {
            throw new Exception('Прежде чем добавлять в эту ноду что либо, нужно сохранить ее');
        }

        if ($this->owner->primaryKey == $target->primaryKey)
        {
            throw new Exception('Нельзя добавить раздел в раздел');
        }

        $target->setAttribute($this->levelAttrName, ($this->getLevel() + 1));
        $target->setAttribute($this->pidAttrName, $this->owner->primaryKey);

         if ($target->getPage())
        {
            /*$result = \Cx_Validate::validate(new \Cx_Validator_Chain_And([
                new Cx_Validator_TreePage(),
                new Cx_Validator_TreePageName($treeChild)
            ]), $treeChild->getPage());*/

            /*if (!$result->isValid())
            {
                $treeChild->set("page", NULL);
            }*/
        }

        $target->save();


        if ($target->getPage())
        {
            $target->processNormalize();
        } else
        {
            $target->processGeneratePageName();
        }
    }


    public function processNormalize()
    {
        return $this
            ->processNormalizePids()
            ->processNormalizeDirs()
            ->processNormalizeCountChildrens()
        ;
    }


    /**
     *
     * Качественное обновление поля dir
     *
     * @return $this
     */
    public function processNormalizeDirs()
    {
        $newDir = [];
        if ($models = $this->findParents()->all())
        {
            foreach ($models as $model)
            {
                //Если разделе не текущий то добавляем его в путь
                if ($model->primaryKey != $this->owner->primaryKey && !$model->isRoot())
                {
                    $newDir[] = $model->getPage();
                }
            }
        }

        $newDir[] = $this->getPage();
        $this->owner->setAttribute($this->dirAttrName, implode("/", $newDir));
        $this->owner->save();

        //Берем детей на один уровень ниже
        $childModels = $this->findChildrens()->all();

        if ($childModels)
        {
            foreach ($childModels as $childModel)
            {
                $childModel->processNormalizeDirs();
            }
        }

        return $this;
    }

    /**
     *
     * Процесс обновления pids у всех детей в дереве ниже.
     *
     * @return $this
     */
    public function processNormalizePids()
    {
        if ($this->owner->isNewRecord)
        {
            return $this;
        }

        $parent = $this->findParent();

        if ($parent)
        {
            $newPids    = $parent->getPids();
            $newPids[]  = $parent->primaryKey;

            $this->owner->setAttribute($this->pidsAttrName, array_unique($newPids));
            $this->owner->setAttribute($this->levelAttrName, $parent->getLevel() + 1);

            $this->owner->save();
        } else
        {
            $this->owner->setAttribute($this->pidsAttrName, []);
            $this->owner->setAttribute($this->levelAttrName, 0);
        }


        //Берем детей на один уровень ниже
        $childModels = $this->findChildrens()->all();

        if ($childModels)
        {
            foreach ($childModels as $childModel)
            {
                $childModel->processNormalizePids();
            }
        }

        return $this;
    }

    /**
     * Подсчитать количество всех дочерних разделов и сохранить
     * @return $this
     */
    public function processNormalizeCountChildrens()
    {
        if ($this->owner->isNewRecord)
        {
            return $this;
        }

        //Берем детей на один уровень ниже
        $childModels = $this->findChildrens()->all();

        if ($childModels)
        {
            $this->owner->setAttribute($this->hasChildrenAttrName, 1);
            foreach ($childModels as $childModel)
            {
                $childModel->processNormalizeCountChildrens();
            }
        }

        return $this;
    }

    /**
     *
     * Корневые разделы дерева.
     *
     * @return ActiveQuery
     */
	public function findRoots()
	{
		return $this->owner->find()->where([$this->levelAttrName => 0])->orderBy(["priority" => SORT_DESC]);
	}

    /**
     * Родительский элемент
     * @return array|bool|null|\yii\db\ActiveRecord
     */
	public function findParent()
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
     * @return array|bool|null|ActiveRecord
     */
    public function findParents()
    {
        if ($this->owner->isNewRecord)
        {
            return false;
        }

        if (!$this->hasParent() || $this->isRoot())
        {
            return false;
        }

        $find = $this->owner->find()->orderBy([$this->levelAttrName => SORT_ASC]);
        if ($pids = $this->getPids())
        {
            foreach ($pids as $pidId)
            {
                $find->orWhere([$this->owner->primaryKey()[0] => $pidId]);
            }
        }

        return $find;
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
     * @return string
     */
    public function getPage()
    {
        return (string) $this->owner->{$this->pageAttrName};
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
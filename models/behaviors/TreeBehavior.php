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
use skeeks\cms\validators\db\IsNewRecord;
use skeeks\cms\validators\db\NotNewRecord;
use skeeks\cms\validators\db\NotSame;
use skeeks\cms\validators\HasBehavior;
use skeeks\cms\validators\model\TreeSeoPageName;
use skeeks\cms\validators\NewRecord;
use skeeks\sx\filters\string\SeoPageName as FilterSeoPageName;
use skeeks\sx\validate\Validate;
use skeeks\sx\validators\ChainAnd;
use yii\base\Event;
use yii\base\ModelEvent;
use yii\db\ActiveQuery;
use yii\db\AfterSaveEvent;
use yii\db\BaseActiveRecord;
use yii\helpers\Json;
use yii\validators\Validator;

/**
 * Class TreeBehavior
 * @package skeeks\cms\models\behaviors
 */
class TreeBehavior extends ActiveRecordBehavior
{
    public $pidAttrName         = "pid"; //Непосредственный родитель
    public $pidsAttrName        = "pids";
    public $levelAttrName       = "level";
    public $dirAttrName         = "dir";
    public $pageAttrName        = "code";
    public $nameAttrName        = "name";
    public $hasChildrenAttrName = "has_children";

    public $delimetr = "/";


    /**
     * @param ActiveRecord $owner
     * @throws Exception
     */
    public function attach($owner)
    {
        $owner->attachBehavior("implode_tree",  [
            "class"  => Implode::className(),
            "delimetr" => $this->delimetr,
            "fields" =>  ["pids"]
        ]);

        parent::attach($owner);
    }


    /**
     * @return array
     */
    public function events()
    {
        return array_merge(parent::events(), [
            BaseActiveRecord::EVENT_BEFORE_INSERT          => "beforeSaveNode",
            BaseActiveRecord::EVENT_BEFORE_UPDATE          => "beforeSaveNode",
            BaseActiveRecord::EVENT_AFTER_UPDATE           => "afterUpdateNode",
            BaseActiveRecord::EVENT_BEFORE_DELETE          => "beforeDeleteNode",
            BaseActiveRecord::EVENT_AFTER_DELETE           => "afterDeleteNode",
        ]);
    }

    public function afterUpdateNode(AfterSaveEvent $event)
    {
        if ($event->changedAttributes)
        {
            //Если изменилось название seo_page_name
            if (isset($event->changedAttributes[$this->pageAttrName]))
            {
                $event->sender->processNormalize();
            }
        }
    }

    public function beforeSaveNode(ModelEvent $event)
    {
        //Если не заполнено название, нужно сгенерить
        if (!$this->getName())
        {
            $this->generateName();
        }

        if (!$this->getSeoPageName())
        {
            $this->generateSeoPageName();
        }
    }



    public function beforeDeleteNode(Event $event)
    {
        //Если есть дети для начала нужно удалить их всех
        if ($childrents = $this->findChildrens()->all())
        {
            foreach ($childrents as $childNode)
            {
                $childNode->delete();
            }
        }
    }

    public function afterDeleteNode(Event $event)
    {
        //После удаления нужно родителя пересчитать
        $parent = $this->findParent();

        if ($parent)
        {
            $parent->processNormalize();
        }
    }


    /**
     *
     * Автоматическая генерация PageName по названию
     *
     * @return ActiveRecord
     */
    public function generateSeoPageName()
    {
        if ($this->isRoot())
        {
            $this->owner->setAttribute($this->pageAttrName, null);
        } else
        {
            $filter     = new FilterSeoPageName();
            $newName    = $filter->filter($this->getName());

            if (Validate::validate(new TreeSeoPageName($this->owner), $newName)->isInvalid())
            {
                $newName    = $filter->filter($newName . "-" . substr(md5(uniqid() . time()), 0, 4));

                if (!Validate::validate(new TreeSeoPageName($this->owner), $newName)->isValid())
                {
                    $this->generateName();
                }
            }

            $this->owner->setAttribute($this->pageAttrName, $newName);
        }

        return $this->owner;
    }
    /**
     *
     * Автоматическая генерация названия раздела
     *
     * @return ActiveRecord
     */
    public function generateName()
    {
        $lastTree = $this->owner->find()->orderBy(["id" => SORT_DESC])->one();
        $this->owner->setAttribute($this->nameAttrName, "pk-" . $lastTree->primaryKey);

        return $this->owner;
    }

    /**
     *
     * Проверка ноды
     *
     * @param ActiveRecord $node
     * @throws \skeeks\sx\validate\Exception
     */
    protected function _ensureNode(ActiveRecord $node)
    {
        Validate::ensure(new HasBehavior(self::className()), $node);
    }


    /**
     * Найти непосредственных детей ноды
     * @return ActiveQuery
     */
	public function findChildrens()
	{
		return $this->owner->find()->where([$this->pidAttrName => $this->owner->primaryKey])->orderBy(["priority" => SORT_ASC]);
	}

    /**
     * Найти непосредственных детей ноды
     * @return ActiveQuery
     */
	public function findChildrensAll()
	{
        $pidString = implode('/', $this->owner->getPids()) . "/" . $this->owner->primaryKey;

		return $this->owner->find()
            ->andWhere(['like', $this->pidsAttrName, $pidString . '%', false])
            ->orderBy(["priority" => SORT_ASC]);
	}



    /**
     * Родительский элемент
     * @return array|bool|null|\yii\db\ActiveRecord
     */
	public function findParent()
	{
        if (!$this->hasParent())
        {
            return false;
        }

		return $this->owner->find()->where([$this->owner->primaryKey()[0] => $this->getPid()])->one();
	}



    /**
     *
     * Установка атрибутов если родителем этой ноды будет новый, читаем родителя, и обновляем необходимые данные у себя
     *
     * @param ActiveRecord $parent
     * @return ActiveRecord
     * @throws \skeeks\sx\validate\Exception
     */
    public function setAttributesForFutureParent(ActiveRecord $parent)
    {
        $this->_ensureNode($parent);
        //Родитель должен быть уже сохранен
        Validate::ensure(new ChainAnd([
            new NotNewRecord(),
            new NotSame($this->owner)
        ]), $parent);

        $newPids     = $parent->getPids();
        $newPids[]   = $parent->primaryKey;

        $this->owner->setAttribute($this->levelAttrName,     ($parent->getLevel() + 1));
        $this->owner->setAttribute($this->pidAttrName,       $parent->primaryKey);
        $this->owner->setAttribute($this->pidsAttrName,      $newPids);


        if (!$this->getName())
        {
            $this->generateName();
        }

        if (!$this->getSeoPageName())
        {
            //Просто генерируем pageName
            $this->generateSeoPageName();
        }

        if ($parent->{$this->dirAttrName})
        {
            $this->owner->setAttribute($this->dirAttrName,       $parent->{$this->dirAttrName} . $this->delimetr . $this->getSeoPageName());
        } else
        {
            $this->owner->setAttribute($this->dirAttrName,       $this->getSeoPageName());
        }

        return $this->owner;
    }

    /**
     *
     * Создание дочерней ноды
     *
     * @param ActiveRecord $target
     * @return ActiveRecord
     * @throws Exception
     * @throws \skeeks\sx\validate\Exception
     */
    public function processCreateNode(ActiveRecord $target)
    {
        $this->_ensureNode($target);
        //Текущая сущьность должна быть уже сохранена
        Validate::ensure(new NotNewRecord(), $this->owner);
        //Новая сущьность должна быть еще не сохранена
        Validate::ensure(new IsNewRecord(), $target);

        //Установка атрибутов будущему ребенку
        $target->setAttributesForFutureParent($this->owner);
        if (!$target->save(false))
        {
            throw new Exception(\Yii::t('app',"Failed to create the child element:  ") . Json::encode($target->attributes));
        }

        $this->owner->setAttribute($this->hasChildrenAttrName, 1);
        $this->owner->save(false);

        return $target;
    }


    /**
     *
     * Процесс вставки ноды одна в другую. Можно вставлять как уже сохраненную модель с дочерними элементами, так и еще не сохраненную.
     *
     * @param ActiveRecord $target
     * @return ActiveRecord
     * @throws Exception
     * @throws \skeeks\sx\validate\Exception
     */
    public function processAddNode(ActiveRecord $target)
    {
        $this->_ensureNode($target);
        //Текущая сущьность должна быть уже сохранена, и не равна $target
        Validate::ensure(new ChainAnd([
            new NotNewRecord(),
            new NotSame($target)
        ]), $this->owner);

        //Если раздел который мы пытаемся добавить новый, то у него нет детей и он
        if ($target->isNewRecord)
        {
            $this->processCreateNode($target);
            return $this->owner;
        }
        else
        {
            $target->setAttributesForFutureParent($this->owner);
            if (!$target->save(false))
            {
                throw new Exception(\Yii::t('app',"Unable to move: ") . Json::encode($target->attributes));
            }

            $this->processNormalize();
        }

        return $this->owner;
    }


    /**
     * Обновление всего дерева ниже, и самого элемента.
     * Если найти всех рутов дерева и запустить этот метод, то дерево починиться в случае поломки
     * правильно переустановятся все dir, pids и т.д.
     * @return ActiveRecord
     */
    public function processNormalize()
    {
        //Если это новая несохраненная сущьность, ничего делать не надо
        if (Validate::validate(new IsNewRecord(), $this->owner)->isValid())
        {
            return $this;
        }

        if (!$this->hasParent())
        {
            $this->owner->setAttribute($this->dirAttrName, null);
            $this->owner->save(false);
        }
        else
        {
            $parent = $this->findParent();
            $this->setAttributesForFutureParent($parent);
            $this->owner->save(false);
        }


        //Берем детей на один уровень ниже
        $childModels = $this->findChildrens()->all();
        if ($childModels)
        {
            $this->owner->setAttribute($this->hasChildrenAttrName, 1);
            $this->owner->save(false);

            foreach ($childModels as $childModel)
            {
                $childModel->processNormalize();
            }
        } else
        {
            $this->owner->setAttribute($this->hasChildrenAttrName, 0);
            $this->owner->save(false);
        }

        return $this->owner;
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
     * У текущего раздела есть ли родительский элемент
     * @return bool
     */
    public function hasChildrens()
    {
        return (bool) $this->owner->{$this->hasChildrenAttrName};
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
    public function getName()
    {
        return (string) $this->owner->{$this->nameAttrName};
    }

    /**
     * @return string
     */
    public function getSeoPageName()
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
     * @return string
     */
    public function getDir()
    {
        return (string) $this->owner->{$this->dirAttrName};
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return (int) $this->owner->{$this->pidAttrName};
    }

    /**
     * Пересчитывает приоритеты детей в соответствии со занчениями заданного поля
     * @param string $field Название поля
     * @param bool $is_asc Упорядочивать ли по возрастанию
     */
    public function recalculateChildrenPriorities($field = "name", $is_asc = true)
    {
        //если второй аргумент - true, то сортируем по-возрастанию,
        //по-умолчанию - false: сортировка по-убыванию,
        //потому что как правило везде элементы сортируются по убыванию приоритета:
        //чем больше приоритет, тем выше элемент стоит в списке.
        if ($is_asc)
        {
            $order = SORT_ASC;
        }
        else
        {
            $order = SORT_DESC;
        }

        $children = $this->findChildrens()->orderBy([$field => $order])->all();

        //значения приориетов начинаются со 100 и кратны 100,
        //на тот случай, если между какими-то элементами
        //нужно будет вручную добавить другие
        $i = 100;

        foreach($children as $child)
        {
            $child->priority = $i;
            $child->save(false);
            $i += 100;
        }
    }

    /**
     * Производит обмен значений приоритетов между текущим элементом дерева и элементом, переданном в аргументе
     * @param $swap_node Элемент дерева с которым произвести обмен приоритетами
     */
    public function swapPriorities($swap_node)
    {
        $this_priority = $this->owner->priority;
        $swap_priority = $swap_node->priority;

        $this->owner->priority = $swap_priority;
        $this->owner->save(false);

        $swap_node->priority = $this_priority;
        $swap_node->save(false);
    }
}
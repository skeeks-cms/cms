<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.03.2015
 */
namespace skeeks\cms\widgets\treeChildrens;

use skeeks\cms\base\Widget;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\WidgetHasTemplate;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class TreeChildrens
 * @package skeeks\cms\widgets\tree
 */
class TreeChildrens extends \skeeks\cms\widgets\base\hasTemplate\WidgetHasTemplate
{
    /**
     * @var null|string
     */
    public $pid                 = null;
    public $types               = [];
    public $statuses            = [];
    public $statusesAdults      = [];
    public $treeMenuIds         = [];

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            ['pid', 'required'],
            ['pid', 'integer'],
            [['types', 'statuses', 'statusesAdults', 'treeMenuIds'], 'safe']
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'pid'               => 'Родительский раздел',
            'types'             => 'Типы страниц',
            'statuses'          => 'Статусы',
            'statusesAdults'    => 'Приватные статусы',
            'treeMenuIds'       => 'Метки',
        ]);
    }

    /**
     * Подготовка данных для шаблона
     * @return $this
     */
    public function bind()
    {
        $find = Tree::find();
        $find->orderBy(["priority" => SORT_DESC]);

        if ($this->pid)
        {
            $find->andWhere(['pid' => $this->pid]);
        }

        if ($this->treeMenuIds)
        {
            foreach ($this->treeMenuIds as $id)
            {
                $find->andWhere("FIND_IN_SET('" . $id . "', tree_menu_ids)");
            }
        }

        if ($this->statuses)
        {
            $find->andWhere(['status' => $this->statuses]);
        }

        if ($this->statusesAdults)
        {
            $find->andWhere(['status_adult' => $this->statuses]);
        }

        if ($this->types)
        {
            $find->andWhere(['type' => $this->types]);
        }

        $this->_data->set('models', $find->all());

        return $this;
    }
}

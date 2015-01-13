<?php
/**
 * TreeFixed
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.12.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\treeFixed;

use skeeks\cms\base\Widget;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\WidgetHasTemplate;
use Yii;

/**
 * Class TreeFixed
 * @package skeeks\cms\widgets\treeFixed
 */
class TreeFixed extends WidgetHasTemplate
{
    /**
     * @var null|string
     */
    public $types               = [];
    public $treeMenuId          = null;
    public $statuses            = [];
    public $statusesAdults      = [];
    public $limit               = 0;
    public $orderBy             = null;

    /**
     * Подготовка данных для шаблона
     * @return $this
     */
    public function bind()
    {
        $find = Tree::find();

        if ($this->limit)
        {
            $find->limit($this->limit);
        }

        if ($this->treeMenuId)
        {
            $find->andWhere(['tree_menu_ids' => $this->treeMenuId]);
        }

        if ($this->orderBy)
        {
            $find->orderBy(["priority" => SORT_DESC]);
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


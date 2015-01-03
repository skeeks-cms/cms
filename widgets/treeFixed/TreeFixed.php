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
    public $pid                 = null;
    public $types               = [];
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

        if ($this->pid)
        {
            $find->andWhere(['pid' => $this->pid]);
        }

        if ($this->limit)
        {
            $find->limit($this->limit);
        }

        if ($this->orderBy)
        {
            $find->orderBy($this->limit);
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


<?php
/**
 * Publications
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 08.12.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\publications;

use skeeks\cms\base\Widget;
use skeeks\cms\models\Publication;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\WidgetHasTemplate;
use Yii;

/**
 * Class Publications
 * @package skeeks\cms\widgets\publications
 */
class Publications extends WidgetHasTemplate
{
    /**
     * @var null|string
     */
    public $title                   = '';
    public $tree_ids                = [];
    public $types                   = [];
    public $statuses                = [];
    public $statusesAdults          = [];
    public $limit                   = 0;
    public $orderBy                 = null;

    /**
     * Подготовка данных для шаблона
     * @return $this
     */
    public function bind()
    {
        $find = Publication::find();

        if ($this->tree_ids)
        {
            $idsString = implode(',', $this->tree_ids);
            $find->andWhere("FIND_IN_SET (tree_ids, '{$idsString}')");
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

        $find->andWhere(['<=', 'published_at', time()]);
        $find->orderBy('published_at DESC');

        $this->_data->set('models', $find->all());

        return $this;
    }

    /**
     * @return array|null|Tree
     */
    public function fetchFirstTree()
    {
        if ($id = $this->getFirstTreeId())
        {
            return Tree::find()->where(['id' => $id])->one();
        } else
        {
            return null;
        }
    }

    /**
     * @return int
     */
    public function getFirstTreeId()
    {
        if ($this->tree_ids)
        {
            return (int) array_shift($this->tree_ids);
        } else
        {
            return 0;
        }
    }
}

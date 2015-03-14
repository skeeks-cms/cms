<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.03.2015
 */
namespace skeeks\cms\widgets\treeFixed;

use skeeks\cms\base\Widget;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\WidgetHasTemplate;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class TreeFixed
 * @package skeeks\cms\widgets\treeFixed
 */
class TreeFixed extends \skeeks\cms\widgets\base\hasTemplate\WidgetHasTemplate
{
    /**
     * @var null|string
     */
    public $types               = [];
    public $treeMenuId          = null;
    public $statuses            = [];
    public $statusesAdults      = [];
    public $limit               = 100;
    public $orderBy             = null;

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['limit'], 'integer'],
            [['types', 'treeMenuId', 'statuses', 'statusesAdults', 'limit', 'orderBy'], 'safe'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'template' => 'Шаблон'
        ]);
    }



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
            //$find->andWhere(['like', 'tree_menu_ids', $this->treeMenuId]);
            $find->andWhere("FIND_IN_SET('" . $this->treeMenuId . "', tree_menu_ids)");
        }

        $find->orderBy(["priority" => SORT_DESC]);

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


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
use yii\helpers\ArrayHelper;

/**
 * Class Publications
 * @package skeeks\cms\widgets\publications
 */
class Publications extends \skeeks\cms\widgets\base\hasTemplate\WidgetHasTemplate
{
    static public function getDescriptorConfig()
    {
        return ArrayHelper::merge(parent::getDescriptorConfig(), [
            'name'          => 'Виджет публикаций, без постраничной навигации',
            'description'   => 'В настройках виджета указывается родительский элемент дерева разделов, и виджет находит дочерние.',
        ]);
    }

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

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['title'], 'string'],
            [['tree_ids', 'types', 'statuses', 'statusesAdults', 'limit', 'orderBy'], 'safe'],
        ]);
    }


    /**
     * Подготовка данных для шаблона
     * @return $this
     */
    public function bind()
    {
        $find = Publication::find();

        if ($this->tree_ids)
        {
            foreach ($this->tree_ids as $id)
            {
                $find->orWhere("(FIND_IN_SET ('{$id}', tree_ids) or tree_id = '{$id}')");
            }
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

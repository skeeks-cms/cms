<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.03.2015
 */
namespace skeeks\cms\widgets\treeList;

use skeeks\cms\base\Widget;
use skeeks\cms\models\Publication;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\base\hasModels\WidgetHasModels;
use skeeks\cms\widgets\base\hasModelsSmart\WidgetHasModelsSmart;
use skeeks\cms\widgets\WidgetHasTemplate;
use Yii;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;

/**
 * Class TreeList
 * @package skeeks\cms\widgets\publicationsAll
 */
class TreeList extends WidgetHasModelsSmart
{
    public $modelClassName          = '\skeeks\cms\models\Tree';

    static public function getDescriptorConfig()
    {
        return ArrayHelper::merge(parent::getDescriptorConfig(), [
            'name' => 'Список разделов ( + постраничная навигация)'
        ]);
    }

    /**
     * @var null|string
     */
    public $enableSubTree           = 1;
    public $title                   = '';
    public $types                   = [];
    public $statuses                = [];
    public $statusesAdults          = [];
    public $useCurrentTree          = 0;

    public $treeMenuIds             = [];

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['title'], 'string'],
            [['types', 'statuses', 'statusesAdults', 'useCurrentTree', 'enableSubTree', 'treeMenuIds'], 'safe'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'title'                         => 'Название виджета',
            'types'                         => 'Типы публикаций',
            'statuses'                      => 'Статусы',
            'statusesAdults'                => 'Статусы приватности',
            'useCurrentTree'                => 'Добавлять условия выбора записей, страницы где находится этот виджет',
            'enableSubTree'                 => 'Искать во вложенных разделах',
            'treeMenuIds'                   => 'Метки',
        ]);
    }


    /**
     * Подготовка данных для шаблона
     * @return $this
     */
    public function bind()
    {
        /*$this->buildSearch();
        $this->getSearch()->search(\Yii::$app->request->queryParams);*/
        parent::bind();
        $dataProvider = $this->getSearch()->getDataProvider();
        $find = $dataProvider->query;

        if ($this->useCurrentTree)
        {
            $tree = \Yii::$app->cms->getCurrentTree();
            if ($tree)
            {
                $ids[] = $tree->id;

                if ($this->enableSubTree)
                {
                    if ($tree->hasChildrens())
                    {
                        if ($childrens = $tree->fetchChildrens())
                        {
                            foreach ($childrens as $chidren)
                            {
                                $ids[] = $chidren->id;
                            }
                        }
                    }

                }

                foreach ($ids as $id)
                {
                    $find->orWhere("(pid = '{$id}')");
                }
            }
        }


        if ($this->treeMenuIds)
        {
            foreach ($this->treeMenuIds as $menuId)
            {
                $find->andWhere("FIND_IN_SET('" . $menuId . "', tree_menu_ids)");
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


        if ($this->createdBy)
        {
            $find->andWhere(['created_by' => $this->createdBy]);
        }


        if ($this->updatedBy)
        {
            $find->andWhere(['updated_by' => $this->updatedBy]);
        }

        return $this;
    }


}

<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\query;

use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\Tree;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CmsContentElementActiveQuery extends CmsActiveQuery
{


    /**
     * Фильтрация по дате публикации
     *
     * @return CmsActiveQuery
     */
    public function publishedTime()
    {
        $this->andWhere(["<=", $this->getPrimaryTableName().'.published_at', \Yii::$app->formatter->asTimestamp(time())]);

        return $this->andWhere([
            'or',
            [">=", $this->getPrimaryTableName().'.published_to', \Yii::$app->formatter->asTimestamp(time())],
            [$this->getPrimaryTableName().'.published_to' => null],
        ]);
    }

    /**
     * Фильтрация по разделам
     *
     * @param null|CmsTree $cmsTree если не укзан раздел то будет взят текущий раздел
     * @param bool         $isDescendants искать привязку среди всех вложенных разделов?
     * @param bool         $isJoinSecondTrees Искать элементы по второстепенной привязке? Находит элементы, которые не привязаны к основному разделу
     * @return $this
     */
    public function cmsTree($cmsTree = null, $isDescendants = true, $isJoinSecondTrees = false)
    {
        if ($cmsTree === null) {
            $cmsTree = \Yii::$app->cms->currentTree;
        }

        if (!$cmsTree || !$cmsTree instanceof Tree) {
            return $this;
        }

        $treeIds = [$cmsTree->id];
        if ($isDescendants) {
            //$treeIds = $cmsTree->getDescendants(null, true)->select(['id']);
            //print_r($treeIds->createCommand()->rawSql);die;
            $treeDescendantIds = $cmsTree->getDescendants()->select(['id'])->indexBy('id')->asArray()->all();
            if ($treeDescendantIds) {
                $treeDescendantIds = array_keys($treeDescendantIds);
                $treeIds = ArrayHelper::merge($treeIds, $treeDescendantIds);
            }
        }

        if ($isJoinSecondTrees === true) {
            $this->joinWith('cmsContentElementTrees');
            $this->andWhere([
                'or',
                [$this->getPrimaryTableName().'.tree_id' => $treeIds],
                [CmsContentElementTree::tableName().'.tree_id' => $treeIds],
            ]);
            $this->groupBy([$this->getPrimaryTableName().'.id']);
        } else {
            $this->andWhere([$this->getPrimaryTableName().'.tree_id' => $treeIds]);
        }

        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function hasImage($value = true)
    {
        if ($value) {
             $this->andWhere(['is not', $this->getPrimaryTableName() . '.image_id', null]);
        } else {
            $this->andWhere([$this->getPrimaryTableName() . '.image_id' => null]);
        }

        return $this;
    }
}

<?php
/**
 * Publication
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\CanBeLinkedToModel;
use skeeks\cms\models\behaviors\CanBeLinkedToTree;
use skeeks\cms\models\behaviors\HasAdultStatus;
use skeeks\cms\models\behaviors\HasPageOptions;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use Yii;

/**
 * Class Publication
 * @package skeeks\cms\models
 */
class Publication extends PageAdvanced
{
    public $viewPageTemplate = "cms/publication/view";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_publication}}';
    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            CanBeLinkedToModel::className(),
            CanBeLinkedToTree::className(),
            TimestampPublishedBehavior::className() => TimestampPublishedBehavior::className(),
            HasPageOptions::className() => HasPageOptions::className(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'type'          => Yii::t('app', 'Publication type'),
            'tree_ids'      => Yii::t('app', 'Разделы'),
            'tree_id'       => Yii::t('app', 'Главный раздел'),
            'page_options'  => Yii::t('app', 'Дополнительные свойства'),
            'published_at'  => Yii::t('app', 'Дата публикации'),
        ]);
    }

    public $multiPageOptions;

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios['create'] = $scenarios[self::SCENARIO_DEFAULT];
        $scenarios['update'] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name'], 'required'],
            [['type'], 'string'],
            [['published_at', 'tree_id'], 'integer'],
            [['tree_ids', 'page_options', 'multiPageOptions'], 'safe'],
        ]);
    }

    /**
     * @return null|ModelType
     */
    public function getType()
    {
        if ($this->type)
        {
            return \Yii::$app->registeredModels->getDescriptor($this)->getTypes()->getComponent($this->type);
        }

        return null;
    }


    /**
     * @return null|Tree
     */
    public function fetchMainTree()
    {
        if ($treeId = $this->getMainTreeId())
        {
            return Tree::find()->where(['id' => $treeId])->limit(1)->one();
        }

        return null;
    }
    /**
     * @return int
     */
    public function getMainTreeId()
    {
        return (int) array_shift($this->getTreeIds());
    }

    /**
     * @return array
     */
    public function getTreeIds()
    {
        $result = [];

        if ($this->tree_id)
        {
            $result[] = (int) $this->tree_id;
        }

        if ($this->tree_ids)
        {
            $result = array_merge($result, $this->tree_ids);
        }

        return array_unique($result);
    }

    /**
     * @return Tree[]
     */
    public function fetchTrees()
    {
        if ($treeIds = $this->getTreeIds())
        {
            return Tree::find()->where(['id' => $treeIds])->all();
        }

        return [];
    }

}

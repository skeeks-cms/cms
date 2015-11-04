<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\contentElements;

use skeeks\cms\base\Widget;
use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class СontentElementsCmsWidget
 * @package skeeks\cms\cmsWidgets\contentElements
 */
class ContentElementsCmsWidget extends WidgetRenderable
{
    //Навигация
    public $enabledPaging               = CMS::BOOL_Y;
    public $enabledPjaxPagination       = CMS::BOOL_Y;

    public $pageSize                    = 10;
    public $pageSizeLimitMin            = 1;
    public $pageSizeLimitMax            = 50;
    public $pageParamName               = 'page';

    //Сортировка
    public $orderBy                     = "published_at";
    public $order                       = SORT_DESC;

    //Дополнительные настройки
    public $label                       = null;
    public $enabledSearchParams         = CMS::BOOL_Y;
    public $enabledCurrentTree          = CMS::BOOL_Y;
    public $enabledCurrentTreeChild     = CMS::BOOL_Y;
    public $enabledCurrentTreeChildAll  = CMS::BOOL_Y;

    public $tree_ids                    = [];

    //Условия для запроса
    public $limit                       = 0;
    public $active                      = "";
    public $createdBy                   = [];
    public $content_ids                 = [];

    public $enabledActiveTime           = CMS::BOOL_Y;


    public $enabledRunCache             = Cms::BOOL_N;
    public $runCacheDuration            = 0;

    public $activeQueryCallback;
    public $dataProviderCallback;

    /**
     * @see (new ActiveQuery)->with
     * @var array
     */
    public $with = ['image', 'cmsTree'];


    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Элементы контента'
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'enabledPaging'             => \Yii::t('app','Enable paging'),
            'enabledPjaxPagination'     => \Yii::t('app','Enable ajax navigation'),
            'pageParamName'             => \Yii::t('app','Parameter name pages, pagination'),
            'pageSize'                  => \Yii::t('app','Number of records on one page'),
            'pageSizeLimitMin'          => \Yii::t('app','The minimum allowable value for pagination'),
            'pageSizeLimitMax'          => \Yii::t('app','The maximum allowable value for pagination'),

            'orderBy'                   => \Yii::t('app','Sort by what parameter'),
            'order'                     => \Yii::t('app','Sorting direction'),

            'label'                     => \Yii::t('app','Title'),
            'enabledSearchParams'       => \Yii::t('app','Take into account the parameters from search string (for filtering)'),

            'limit'                     => \Yii::t('app','The maximum number of entries in the sample ({limit})',['limit' => 'limit']),
            'active'                    => \Yii::t('app','Take into consideration active flag'),
            'createdBy'                 => \Yii::t('app','Selecting the user records'),
            'content_ids'               => \Yii::t('app','Elements of content'),
            'enabledCurrentTree'        => \Yii::t('app','For the colection taken into account the current section (which shows the widget)'),
            'enabledCurrentTreeChild'   => \Yii::t('app','For the colection taken into account the current section and its subsections'),
            'enabledCurrentTreeChildAll'=> \Yii::t('app','For the colection taken into account the current section and all its subsections'),
            'tree_ids'                  => \Yii::t('app','Show items linked to sections'),
            'enabledActiveTime'         => \Yii::t('app','Take into consideration activity time'),

            'enabledRunCache'       => 'Включить кэширование',
            'runCacheDuration'      => 'Время жизни кэша',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            [['enabledPaging'], 'string'],
            [['enabledPjaxPagination'], 'string'],
            [['pageParamName'], 'string'],
            [['pageSize'], 'string'],
            [['orderBy'], 'string'],
            [['order'], 'integer'],
            [['label'], 'string'],
            [['label'], 'string'],
            [['enabledSearchParams'], 'string'],
            [['limit'], 'integer'],
            [['pageSizeLimitMin'], 'integer'],
            [['pageSizeLimitMax'], 'integer'],
            [['active'], 'string'],
            [['createdBy'], 'safe'],
            [['content_ids'], 'safe'],
            [['enabledCurrentTree'], 'string'],
            [['enabledCurrentTreeChild'], 'string'],
            [['enabledCurrentTreeChildAll'], 'string'],
            [['tree_ids'], 'safe'],
            [['enabledActiveTime'], 'string'],

            [['enabledRunCache'], 'string'],
            [['runCacheDuration'], 'integer'],
        ]);
    }

    protected function _run()
    {

        $cacheKey = $this->getCacheKey() . 'run';

        $dependency = new TagDependency([
            'tags'      =>
            [
                $this->className() . (string) $this->namespace,
                (new CmsContentElement())->getTableCacheTag(),
            ],
        ]);

        $result = \Yii::$app->cache->get($cacheKey);
        if ($result === false || $this->enabledRunCache == Cms::BOOL_N)
        {
            $this->initDataProvider();

            if ($this->createdBy)
            {
                $this->dataProvider->query->andWhere([CmsContentElement::tableName() . '.created_by' => $this->createdBy]);
            }

            if ($this->active)
            {
                $this->dataProvider->query->andWhere([CmsContentElement::tableName() . '.active' => $this->active]);
            }

            if ($this->content_ids)
            {
                $this->dataProvider->query->andWhere([CmsContentElement::tableName() . '.content_id' => $this->content_ids]);
            }

            if ($this->limit)
            {
                $this->dataProvider->query->limit($this->limit);
            }


            $treeIds = (array) $this->tree_ids;

            if ($this->enabledCurrentTree == Cms::BOOL_Y)
            {
                $tree = \Yii::$app->cms->getCurrentTree();
                if ($tree)
                {
                    $treeIds[] = $tree->id;
                    if ($tree->children && $this->enabledCurrentTreeChild == Cms::BOOL_Y)
                    {
                        if ($this->enabledCurrentTreeChildAll)
                        {
                            if ($childrens = $tree->children)
                            {
                                foreach ($childrens as $chidren)
                                {
                                    $treeIds[] = $chidren->id;
                                }
                            }
                        } else
                        {
                            if ($childrens = $tree->children)
                            {
                                foreach ($childrens as $chidren)
                                {
                                    $treeIds[] = $chidren->id;
                                }
                            }
                        }
                    }

                }
            }

            if ($treeIds)
            {
                foreach ($treeIds as $key => $treeId)
                {
                    if (!$treeId)
                    {
                        unset($treeIds[$key]);
                    }
                }

                if ($treeIds)
                {
                    /**
                     * @var $query ActiveQuery
                     */
                    $query = $this->dataProvider->query;

                    $query->joinWith('cmsContentElementTrees');
                    $query->andWhere(
                        [
                            'or',
                            [CmsContentElement::tableName() . '.tree_id' => $treeIds],
                            [CmsContentElementTree::tableName() . '.tree_id' => $treeIds]
                        ]
                    );
                }

            }


            if ($this->enabledActiveTime == Cms::BOOL_Y)
            {
                $this->dataProvider->query->andWhere(
                    ["<=", CmsContentElement::tableName() . '.published_at', \Yii::$app->formatter->asTimestamp(time())]
                );

                $this->dataProvider->query->andWhere(
                    [
                        'or',
                        [">=", CmsContentElement::tableName() . '.published_to', \Yii::$app->formatter->asTimestamp(time())],
                        [CmsContentElement::tableName() . '.published_to' => null],
                    ]
                );
            }

            /**
             *
             */
            if ($this->with)
            {
                $this->dataProvider->query->with($this->with);
            }

            $this->dataProvider->query->groupBy([CmsContentElement::tableName() . '.id']);

            if ($this->activeQueryCallback && is_callable($this->activeQueryCallback))
            {
                $callback = $this->activeQueryCallback;
                $callback($this->dataProvider->query);
            }

            if ($this->dataProviderCallback && is_callable($this->dataProviderCallback))
            {
                $callback = $this->dataProviderCallback;
                $callback($this->dataProvider);
            }

            $result = parent::_run();

            \Yii::$app->cache->set($cacheKey, $result, (int) $this->runCacheDuration, $dependency);
        }

        return $result;
    }

    /**
     * @var ActiveDataProvider
     */
    public $dataProvider    = null;

    /**
     * @var Search
     */
    public $search          = null;

    public function initDataProvider()
    {
        $this->search         = new Search(CmsContentElement::className());
        $this->dataProvider   = $this->search->getDataProvider();

        if ($this->enabledPaging == Cms::BOOL_Y)
        {
            $this->dataProvider->getPagination()->defaultPageSize   = $this->pageSize;
            $this->dataProvider->getPagination()->pageParam         = $this->pageParamName;
            $this->dataProvider->getPagination()->pageSizeLimit         = [(int) $this->pageSizeLimitMin, (int) $this->pageSizeLimitMax];
        } else
        {
            $this->dataProvider->pagination = false;
        }

        if ($this->orderBy)
        {
            $this->dataProvider->getSort()->defaultOrder =
            [
                $this->orderBy => (int) $this->order
            ];
        }

        return $this;
    }

}
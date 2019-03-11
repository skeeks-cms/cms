<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\contentElements;

use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsContent;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\formInputs\selectTree\SelectTree;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/**
 * Class СontentElementsCmsWidget
 * @package skeeks\cms\cmsWidgets\contentElements
 */
class ContentElementsCmsWidget extends WidgetRenderable
{
    public $contentElementClass = '\skeeks\cms\models\CmsContentElement';

    //Навигация
    public $enabledPaging = CMS::BOOL_Y;
    public $enabledPjaxPagination = CMS::BOOL_Y;

    public $pageSize = 10;
    public $pageSizeLimitMin = 1;
    public $pageSizeLimitMax = 50;
    public $pageParamName = 'page';

    //Сортировка
    public $orderBy = "published_at";
    public $order = SORT_DESC;

    //Дополнительные настройки
    public $label = null;
    public $enabledSearchParams = CMS::BOOL_Y;
    public $enabledCurrentTree = CMS::BOOL_Y;
    public $enabledCurrentTreeChild = CMS::BOOL_Y;
    public $enabledCurrentTreeChildAll = CMS::BOOL_Y;

    public $tree_ids = [];

    //Условия для запроса
    public $limit = 0;
    public $active = "";
    public $createdBy = [];
    public $content_ids = [];

    public $enabledActiveTime = CMS::BOOL_Y;


    public $enabledRunCache = Cms::BOOL_N;
    public $runCacheDuration = 0;

    public $activeQueryCallback;
    public $dataProviderCallback;

    /**
     * Additionaly, any data
     *
     * @var array
     */
    public $data = [];

    /**
     * @see (new ActiveQuery)->with
     * @var array
     */
    public $with = ['image', 'cmsTree'];

    /**
     * When a sample of elements does a search and table of multiple links. Slowly on large databases!
     * При выборке элементов делает поиск и по таблице множественных связей. Медленно на больших базах данных!
     * @var bool
     */
    public $isJoinTreeMap = true;

    public $options = [];
    /**
     * @var ActiveDataProvider
     */
    public $dataProvider = null;
    /**
     * @var Search
     */
    public $search = null;
    public static function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('skeeks/cms', 'Content elements'),
        ]);
    }
    public function init()
    {
        parent::init();

        $this->initActiveQuery();
    }
    /**
     * @return $this
     */
    public function initActiveQuery()
    {
        $className = $this->contentElementClass;
        $this->initDataProvider();

        if ($this->createdBy) {
            $this->dataProvider->query->andWhere([$className::tableName().'.created_by' => $this->createdBy]);
        }

        if ($this->active) {
            $this->dataProvider->query->andWhere([$className::tableName().'.active' => $this->active]);
        }

        if ($this->content_ids) {
            $this->dataProvider->query->andWhere([$className::tableName().'.content_id' => $this->content_ids]);
        }

        if ($this->limit) {
            $this->dataProvider->query->limit($this->limit);
        }


        $treeIds = (array)$this->tree_ids;

        if ($this->enabledCurrentTree == Cms::BOOL_Y) {
            $tree = \Yii::$app->cms->currentTree;
            if ($tree) {
                if ($this->enabledCurrentTreeChild == Cms::BOOL_Y) {
                    if ($this->enabledCurrentTreeChildAll == Cms::BOOL_Y) {
                        $treeIds = $tree->getDescendants()->select(['id'])->indexBy('id')->asArray()->all();
                        $treeIds = array_keys($treeIds);
                    } else {
                        if ($childrens = $tree->children) {
                            foreach ($childrens as $chidren) {
                                $treeIds[] = $chidren->id;
                            }
                        }
                    }
                }

                $treeIds[] = $tree->id;

            }
        }

        if ($treeIds) {
            foreach ($treeIds as $key => $treeId) {
                if (!$treeId) {
                    unset($treeIds[$key]);
                }
            }

            if ($treeIds) {
                /**
                 * @var $query ActiveQuery
                 */
                $query = $this->dataProvider->query;

                if ($this->isJoinTreeMap === true) {
                    $query->joinWith('cmsContentElementTrees');
                    $query->andWhere(
                        [
                            'or',
                            [$className::tableName().'.tree_id' => $treeIds],
                            [CmsContentElementTree::tableName().'.tree_id' => $treeIds],
                        ]
                    );
                } else {
                    $query->andWhere([$className::tableName().'.tree_id' => $treeIds]);
                }

            }

        }


        if ($this->enabledActiveTime == Cms::BOOL_Y) {
            $this->dataProvider->query->andWhere(
                ["<=", $className::tableName().'.published_at', \Yii::$app->formatter->asTimestamp(time())]
            );

            $this->dataProvider->query->andWhere(
                [
                    'or',
                    [">=", $className::tableName().'.published_to', \Yii::$app->formatter->asTimestamp(time())],
                    [CmsContentElement::tableName().'.published_to' => null],
                ]
            );
        }

        /**
         *
         */
        if ($this->with) {
            $this->dataProvider->query->with($this->with);
        }

        $this->dataProvider->query->groupBy([$className::tableName().'.id']);

        if ($this->activeQueryCallback && is_callable($this->activeQueryCallback)) {
            $callback = $this->activeQueryCallback;
            $callback($this->dataProvider->query);
        }

        if ($this->dataProviderCallback && is_callable($this->dataProviderCallback)) {
            $callback = $this->dataProviderCallback;
            $callback($this->dataProvider);
        }

        return $this;
    }
    public function initDataProvider()
    {
        $className = $this->contentElementClass;

        $this->search = new Search($className::className());
        $this->dataProvider = $this->search->getDataProvider();

        if ($this->enabledPaging == Cms::BOOL_Y) {
            $this->dataProvider->getPagination()->defaultPageSize = $this->pageSize;
            $this->dataProvider->getPagination()->pageParam = $this->pageParamName;
            $this->dataProvider->getPagination()->pageSizeLimit = [
                (int)$this->pageSizeLimitMin,
                (int)$this->pageSizeLimitMax,
            ];
        } else {
            $this->dataProvider->pagination = false;
        }

        if ($this->orderBy) {
            $this->dataProvider->getSort()->defaultOrder =
                [
                    $this->orderBy => (int)$this->order,
                ];
        }

        return $this;
    }
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
            [
                'enabledPaging'         => \Yii::t('skeeks/cms', 'Enable paging'),
                'enabledPjaxPagination' => \Yii::t('skeeks/cms', 'Enable ajax navigation'),
                'pageParamName'         => \Yii::t('skeeks/cms', 'Parameter name pages, pagination'),
                'pageSize'              => \Yii::t('skeeks/cms', 'Number of records on one page'),
                'pageSizeLimitMin'      => \Yii::t('skeeks/cms', 'The minimum allowable value for pagination'),
                'pageSizeLimitMax'      => \Yii::t('skeeks/cms', 'The maximum allowable value for pagination'),

                'orderBy' => \Yii::t('skeeks/cms', 'Sort by what parameter'),
                'order'   => \Yii::t('skeeks/cms', 'Sorting direction'),

                'label'               => \Yii::t('skeeks/cms', 'Title'),
                'enabledSearchParams' => \Yii::t('skeeks/cms',
                    'Take into account the parameters from search string (for filtering)'),

                'limit'                      => \Yii::t('skeeks/cms', 'The maximum number of entries in the sample ({limit})',
                    ['limit' => 'limit']),
                'active'                     => \Yii::t('skeeks/cms', 'Active'),
                'createdBy'                  => \Yii::t('skeeks/cms', 'Selecting the user records'),
                'content_ids'                => \Yii::t('skeeks/cms', 'Elements of content'),
                'enabledCurrentTree'         => \Yii::t('skeeks/cms',
                    'For the colection taken into account the current section (which shows the widget)'),
                'enabledCurrentTreeChild'    => \Yii::t('skeeks/cms',
                    'For the colection taken into account the current section and its subsections'),
                'enabledCurrentTreeChildAll' => \Yii::t('skeeks/cms',
                    'For the colection taken into account the current section and all its subsections'),
                'tree_ids'                   => \Yii::t('skeeks/cms', 'Show items linked to sections'),
                'enabledActiveTime'          => \Yii::t('skeeks/cms', 'Take into consideration activity time'),

                'enabledRunCache'  => 'Включить кэширование',
                'runCacheDuration' => 'Время жизни кэша',
            ]);
    }
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'enabledActiveTime' => \Yii::t('skeeks/cms', "Will be considered time of beginning and end of the publication"),
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

    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'template' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Template'),
                'fields' => [
                    'viewFile',
                ],
            ],

            'pagination' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Pagination'),
                'fields' => [
                    'enabledPaging'         => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                        'allowNull'  => false,
                    ],
                    'enabledPjaxPagination' => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                        'allowNull'  => false,
                    ],
                    'pageSize'              => [
                        'elementOptions' => [
                            'type' => 'number',
                        ],
                    ],
                    'pageSizeLimitMin'      => [
                        'elementOptions' => [
                            'type' => 'number',
                        ],
                    ],
                    'pageSizeLimitMax'      => [
                        'elementOptions' => [
                            'type' => 'number',
                        ],
                    ],
                    'pageParamName',
                ],
            ],

            'filtration' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Filtration'),
                'fields' => [
                    'active'            => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                    ],
                    'enabledActiveTime' => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                        'allowNull' => false,
                    ],
                    'createdBy'         => [
                        'class' => SelectField::class,
                        'items' => \yii\helpers\ArrayHelper::map(
                            \skeeks\cms\models\User::find()->active()->all(),
                            'id',
                            'displayName'
                        ),
                    ],
                    'content_ids'       => [
                        'class'    => SelectField::class,
                        'items'    => CmsContent::getDataForSelect(),
                        'multiple' => true,
                    ],

                    'enabledCurrentTree'         => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                        'allowNull' => false,
                    ],
                    'enabledCurrentTreeChild'    => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                        'allowNull' => false,
                    ],
                    'enabledCurrentTreeChildAll' => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                        'allowNull' => false,
                    ],

                    'tree_ids' => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => SelectTree::class,
                        'widgetConfig' => [
                            'mode'           => SelectTree::MOD_MULTI,
                            'attributeMulti' => 'tree_ids',
                        ],
                    ],


                    'enabledSearchParams' => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                        'allowNull' => false,
                    ],
                ],
            ],

            'sort'         => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Sorting and quantity'),
                'fields' => [
                    'limit'   => [
                        'elementOptions' => [
                            'type' => 'number',
                        ],
                    ],
                    'orderBy' => [
                        'class' => SelectField::class,
                        'items' => (new \skeeks\cms\models\Tree())->attributeLabels(),
                    ],
                    'order'   => [
                        'class' => SelectField::class,
                        'items' => [
                            SORT_ASC  => \Yii::t('skeeks/cms', 'ASC (from lowest to highest)'),
                            SORT_DESC => \Yii::t('skeeks/cms', 'DESC (from highest to lowest)'),
                        ],
                    ],
                ],
            ],
            'additionally' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Additionally'),
                'fields' => [
                    'label',
                ],
            ],
            'cache'        => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Cache settings'),
                'fields' => [
                    'enabledRunCache'  => [
                        'class'      => BoolField::class,
                        'trueValue'  => 'Y',
                        'falseValue' => 'N',
                        'allowNull'  => false,
                    ],
                    'runCacheDuration' => [
                        'elementOptions' => [
                            'type' => 'number',
                        ],
                    ],
                ],
            ],
        ];
    }
    /**
     * @param Tree $tree
     * @return array
     */
    public function getAllIdsForChildren(Tree $tree)
    {
        $treeIds = [];
        /**
         * @var $query ActiveQuery
         */
        $childrens = $tree->getChildren()->with('children')->all();

        if ($childrens) {
            foreach ($childrens as $chidren) {
                if ($chidren->children) {
                    $treeIds[$chidren->id] = $chidren->id;
                    $treeIds = array_merge($treeIds, $this->getAllIdsForChildren($chidren));
                } else {
                    $treeIds[$chidren->id] = $chidren->id;
                }
            }
        }

        return $treeIds;
    }

    public function run()
    {
        $cacheKey = $this->getCacheKey().'run';

        $dependency = new TagDependency([
            'tags' =>
                [
                    $this->className().(string)$this->namespace,
                    (new CmsContentElement())->getTableCacheTag(),
                ],
        ]);

        $result = \Yii::$app->cache->get($cacheKey);
        if ($result === false || $this->enabledRunCache == Cms::BOOL_N) {
            $result = parent::run();

            \Yii::$app->cache->set($cacheKey, $result, (int)$this->runCacheDuration, $dependency);
        }

        return $result;
    }

}
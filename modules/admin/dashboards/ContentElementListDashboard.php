<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\modules\admin\dashboards;

use skeeks\cms\base\Widget;
use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\base\AdminDashboardWidget;
use skeeks\cms\modules\admin\base\AdminDashboardWidgetRenderable;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/**
 * Class ContentElementListDashboard
 * @package skeeks\cms\modules\admin\dashboards
 */
class ContentElementListDashboard extends AdminDashboardWidgetRenderable
{
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('app', 'The list of content items')
        ]);
    }

    public $viewFile    = 'content-element-list';

    public $name;

    //Навигация
    public $enabledPaging               = true;
    public $pageSize                    = 10;
    public $pageSizeLimitMin            = 1;
    public $pageSizeLimitMax            = 50;

    //Сортировка
    public $orderBy                     = "published_at";
    public $order                       = SORT_DESC;


    public $tree_ids                    = [];

    //Условия для запроса
    public $limit                       = 0;
    public $active                      = "";
    public $createdBy                   = [];
    public $content_ids                 = [];

    public $enabledActiveTime           = true;

    /**
     * @see (new ActiveQuery)->with
     * @var array
     */
    public $with = ['image', 'cmsTree'];


    public function init()
    {
        parent::init();

        if (!$this->name)
        {
            $this->name = \Yii::t('app', 'The list of content items');
        }
    }
    /**
     * @return array
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['name'], 'string'],
            [['enabledPaging'], 'boolean'],

            [['orderBy'], 'string'],
            [['order'], 'integer'],

            [['limit'], 'integer'],
            [['pageSizeLimitMin'], 'integer'],
            [['pageSizeLimitMax'], 'integer'],

            [['active'], 'string'],
            [['createdBy'], 'safe'],
            [['content_ids'], 'safe'],

            [['tree_ids'], 'safe'],
            [['enabledActiveTime'], 'boolean'],

            [['pageSize'], 'integer'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name'                           => \Yii::t('app', 'Name'),

            'enabledPaging'             => \Yii::t('app','Enable paging'),
            'pageSizeLimitMin'          => \Yii::t('app','The minimum allowable value for pagination'),
            'pageSizeLimitMax'          => \Yii::t('app','The maximum allowable value for pagination'),
            'pageSize'                  => \Yii::t('app','Number of records on one page'),

            'orderBy'                   => \Yii::t('app','Sort by what parameter'),
            'order'                     => \Yii::t('app','Sorting direction'),

            'limit'                     => \Yii::t('app','The maximum number of entries in the sample ({limit})',['limit' => 'limit']),
            'active'                    => \Yii::t('app','Take into consideration active flag'),

            'createdBy'                 => \Yii::t('app','Selecting the user records'),
            'content_ids'               => \Yii::t('app','Elements of content'),

            'tree_ids'                  => \Yii::t('app','Show items linked to sections'),
            'enabledActiveTime'         => \Yii::t('app','Take into consideration activity time'),
        ]);
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

        if ($this->enabledPaging)
        {
            $this->dataProvider->getPagination()->defaultPageSize   = $this->pageSize;
            $this->dataProvider->getPagination()->pageParam         = "page-" . $this->id;
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

        $this->search->search(\Yii::$app->request->get());

        return $this;
    }


    public function run()
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


        if ($this->enabledActiveTime)
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

        return parent::run();
    }

    /**
     * @param \skeeks\cms\modules\admin\widgets\ActiveForm $form
     */
    public function renderConfigForm(ActiveForm $form = null)
    {
        echo $form->fieldSet(\Yii::t('app','Main'));
            echo $form->field($this, 'name');
        echo $form->fieldSetEnd();

        echo $form->fieldSet(\Yii::t('app','Pagination'));
            echo $form->field($this, 'enabledPaging')->checkbox();
            echo $form->field($this, 'pageSize');
            echo $form->field($this, 'pageSizeLimitMin');
            echo $form->field($this, 'pageSizeLimitMax');
        echo $form->fieldSetEnd();

        echo $form->fieldSet(\Yii::t('app','Filtering'));

            echo $form->field($this, 'enabledActiveTime')->checkbox()
                ->hint(\Yii::t('app',"Will be considered time of beginning and end of the publication"));

            echo $form->fieldSelectMulti($this, 'content_ids', \skeeks\cms\models\CmsContent::getDataForSelect());
            /*echo $form->fieldSelectMulti($this, 'createdBy')->widget(
                \skeeks\cms\modules\admin\widgets\formInputs\SelectModelDialogUserInput::className()
            );*/

            echo $form->field($this, 'tree_ids')->widget(
                \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
                [
                    'mode' => \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_MULTI,
                    'attributeMulti' => 'tree_ids'
                ]
            );
        echo $form->fieldSetEnd();

        echo $form->fieldSet(\Yii::t('app','Sorting and quantity'));
            echo $form->field($this, 'limit');
            echo $form->fieldSelect($this, 'orderBy', (new \skeeks\cms\models\CmsContentElement())->attributeLabels());
            echo $form->fieldSelect($this, 'order', [
            SORT_ASC    => "ASC (".\Yii::t('app','from smaller to larger').")",
            SORT_DESC   => "DESC (".\Yii::t('app','from highest to lowest').")",
        ]);
        echo $form->fieldSetEnd();
    }
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\treeMenu;

use skeeks\cms\base\Widget;
use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Tree;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/**
 * Class TreeMenuCmsWidget
 *
 * @package skeeks\cms\cmsWidgets\treeMenu
 */
class TreeMenuCmsWidget extends WidgetRenderable
{
    /**
     * Родительский раздел дерева
     * @var null
     */
    public $treePid                     = null;

    /**
     * Выбор только активных пунктов
     * @var string
     */
    public $active                      = Cms::BOOL_Y;

    /**
     * Добавить условие уровня раздела
     * @var null
     */
    public $level                       = null;

    /**
     * Название
     * @var null
     */
    public $label                       = null;

    /**
     * Условие выборки по сайтам
     * @var array
     */
    public $site_codes                  = [];

    /**
     * Сортировка по умолчанию
     * @var string
     */
    public $orderBy                     = "priority";
    public $order                       = SORT_ASC;

    /**
     * Добавить условие выборки разделов, только текущего сайта
     * @var string
     */
    public $enabledCurrentSite          = Cms::BOOL_Y;

    /**
     * Включить выключить кэш
     * @var string
     */
    public $enabledRunCache             = Cms::BOOL_Y;
    public $runCacheDuration            = 0;

    /**
     * Типы разделов
     * @var array
     */
    public $tree_type_ids               = [];

    /**
     * Дополнительный activeQueryCallback
     * @var
     */
    public $activeQueryCallback;

    /**
     * @see (new ActiveQuery)->with
     * @var array
     */
    public $with = ['children'];


    /**
     * @var ActiveQuery
     */
    public $activeQuery = null;

    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Меню разделов'
        ]);
    }

    public $text = '';

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'treePid'               => \Yii::t('skeeks/cms', 'The parent section'),
            'active'                => \Yii::t('skeeks/cms', 'Activity'),
            'level'                 => \Yii::t('skeeks/cms', 'The nesting level'),
            'label'                 => \Yii::t('skeeks/cms', 'Header'),
            'site_codes'            => \Yii::t('skeeks/cms', 'Linking to sites'),
            'orderBy'               => \Yii::t('skeeks/cms', 'Sorting'),
            'order'                 => \Yii::t('skeeks/cms', 'Sorting direction'),
            'enabledCurrentSite'    => \Yii::t('skeeks/cms', 'Consider the current site'),
            'enabledRunCache'       => \Yii::t('skeeks/cms', 'Enable caching'),
            'runCacheDuration'      => \Yii::t('skeeks/cms', 'Cache lifetime'),
            'tree_type_ids'         => \Yii::t('skeeks/cms', 'Section types'),
        ]);
    }

    public function attributeHints()
    {
        return array_merge(parent::attributeHints(),
        [
            'enabledCurrentSite'   => \Yii::t('skeeks/cms', 'If you select "yes", then the sample section, add the filter condition, sections of the site, which is called the widget'),
            'level'                => \Yii::t('skeeks/cms', 'Adds the sample sections, the condition of nesting choice. 0 - will not use this condition at all.'),
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['text', 'string'],
            [['viewFile', 'label', 'active', 'orderBy', 'enabledCurrentSite', 'enabledRunCache'], 'string'],
            [['treePid', 'level', 'runCacheDuration'], 'integer'],
            [['order'], 'integer'],
            [['site_codes'], 'safe'],
            [['tree_type_ids'], 'safe'],
        ]);
    }

    public function renderConfigForm(ActiveForm $form)
    {
        echo \Yii::$app->view->renderFile(__DIR__ . '/_form.php', [
            'form'  => $form,
            'model' => $this
        ], $this);
    }

    public function init()
    {
        parent::init();

        $this->initActiveQuery();
    }

    /**
     * Инициализация acitveQuery
     * @return $this
     */
    public function initActiveQuery()
    {
        $this->activeQuery = Tree::find();

        if ($this->treePid)
        {
            $this->activeQuery->andWhere(['pid' => $this->treePid]);
        }

        if ($this->level)
        {
            $this->activeQuery->andWhere(['level' => $this->level]);
        }

        if ($this->active)
        {
            $this->activeQuery->andWhere(['active' => $this->active]);
        }

        if ($this->site_codes)
        {
            $this->activeQuery->andWhere(['site_code' => $this->site_codes]);
        }

        if ($this->enabledCurrentSite == Cms::BOOL_Y && \Yii::$app->cms->site)
        {
            $this->activeQuery->andWhere(['site_code' => \Yii::$app->cms->site->code]);
        }

        if ($this->orderBy)
        {
            $this->activeQuery->orderBy([$this->orderBy => (int) $this->order]);
        }

        if ($this->tree_type_ids)
        {
            $this->activeQuery->andWhere(['tree_type_id' => $this->tree_type_ids]);
        }


        if ($this->with)
        {
            $this->activeQuery->with($this->with);
        }

        if ($this->activeQueryCallback && is_callable($this->activeQueryCallback))
        {
            $callback = $this->activeQueryCallback;
            $callback($this->activeQuery);
        }

        return $this;
    }

    protected function _run()
    {
        $key = $this->getCacheKey() . 'run';

        $dependency = new TagDependency([
            'tags'      =>
            [
                $this->className() . (string) $this->namespace,
                (new Tree())->getTableCacheTag(),
            ],
        ]);

        $result = \Yii::$app->cache->get($key);
        if ($result === false || $this->enabledRunCache == Cms::BOOL_N)
        {
            $result = parent::_run();
            \Yii::$app->cache->set($key, $result, (int) $this->runCacheDuration, $dependency);
        }

        return $result;
    }

}
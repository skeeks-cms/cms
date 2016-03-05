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
 * @package skeeks\cms\cmsWidgets\treeMenu
 */
class TreeMenuCmsWidget extends WidgetRenderable
{
    public $treePid                     = null;
    public $active                      = Cms::BOOL_Y;
    public $level                       = null;
    public $label                       = null;
    public $site_codes                  = [];

    public $orderBy                     = "priority";
    public $order                       = SORT_ASC;

    public $enabledCurrentSite          = Cms::BOOL_Y;

    public $enabledRunCache             = Cms::BOOL_Y;
    public $runCacheDuration            = 0;

    public $tree_type_ids               = [];

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
            'treePid'               => 'Родительский раздел',
            'active'                => 'Активность',
            'level'                 => 'Уровень вложенности',
            'label'                 => 'Заголовок',
            'site_codes'            => 'Разделы привязанные к сайтам',
            'orderBy'               => 'По какому параметру сортировать',
            'order'                 => 'Направление сортировки',
            'enabledCurrentSite'    => 'Учитывать текущий сайт',
            'enabledRunCache'       => 'Включить кэширование',
            'runCacheDuration'      => 'Время жизни кэша',
            'tree_type_ids'         => 'Типы страниц',
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

            if ($this->enabledCurrentSite == Cms::BOOL_Y && $currentSite = \Yii::$app->cms->site)
            {
                $this->activeQuery->andWhere(['site_code' => $currentSite->code]);
            }

            if ($this->orderBy)
            {
                $this->activeQuery->orderBy([$this->orderBy => (int) $this->order]);
            }

            if ($this->tree_type_ids)
            {
                $this->activeQuery->andWhere(['tree_type_id' => $this->tree_type_ids]);
            }

            /**
             *
             */
            if ($this->with)
            {
                $this->activeQuery->with($this->with);
            }

            if ($this->activeQueryCallback && is_callable($this->activeQueryCallback))
            {
                $callback = $this->activeQueryCallback;
                $callback($this->activeQuery);
            }

            $result = parent::_run();

            \Yii::$app->cache->set($key, $result, (int) $this->runCacheDuration, $dependency);
        }

        return $result;
    }

}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\sites;

use skeeks\cms\base\Widget;
use skeeks\cms\base\WidgetRenderable;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContentElementTree;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\Search;
use skeeks\cms\models\Tree;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/**
 * Class SitesCmsWidget
 * @package skeeks\cms\cmsWidgets\contentElements
 */
class SitesCmsWidget extends WidgetRenderable
{
    //Сортировка
    public $orderBy                     = "priority";
    public $order                       = SORT_ASC;

    //Дополнительные настройки
    public $label                       = null;

    //Условия для запроса
    public $limit                       = 0;
    public $active                      = Cms::BOOL_Y;

    public $enabledRunCache             = Cms::BOOL_Y;
    public $runCacheDuration            = 0;

    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Сайты'
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'orderBy'                   => 'По какому параметру сортировать',
            'order'                     => 'Направление сортировки',

            'label'                     => 'Заголовок',

            'limit'                     => 'Максимальное количество записей в выборке (limit)',
            'active'                    => 'Учитывать флаг активности',

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
            [['active'], 'string'],
            [['createdBy'], 'safe'],
            [['content_ids'], 'safe'],
            [['enabledCurrentTree'], 'string'],
            [['enabledCurrentTreeChild'], 'string'],
            [['tree_ids'], 'safe'],
            [['enabledRunCache'], 'string'],
            [['runCacheDuration'], 'integer'],
        ]);
    }

    public function renderConfigForm(ActiveForm $form)
    {
        echo \Yii::$app->view->renderFile(__DIR__ . '/_form.php', [
            'form'  => $form,
            'model' => $this
        ], $this);
    }

    /**
     * @var ActiveQuery
     */
    public $activeQuery = null;


    protected function _run()
    {
        $key = $this->getCacheKey() . 'run';

        $dependency = new TagDependency([
            'tags'      =>
            [
                $this->className() . (string) $this->namespace,
                (new CmsSite())->getTableCacheTag(),
            ],
        ]);

        $result = \Yii::$app->cache->get($key);
        if ($result === false || $this->enabledRunCache == Cms::BOOL_N)
        {
            $this->activeQuery = CmsSite::find();

            if ($this->active == Cms::BOOL_Y)
            {
                $this->activeQuery->active();
            } else if ($this->active == Cms::BOOL_N)
            {
                $this->activeQuery->active(false);
            }

            if ($this->limit)
            {
                $this->activeQuery->limit($limit);
            }

            if ($this->orderBy)
            {
                $this->activeQuery->orderBy([$this->orderBy => (int) $this->order]);
            }

            $result = parent::_run();

            \Yii::$app->cache->set($key, $result, (int) $this->runCacheDuration, $dependency);
        }

        return $result;
    }
}
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
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class TreeMenuCmsWidget
 * @package skeeks\cms\cmsWidgets\treeMenu
 */
class TreeMenuCmsWidget extends WidgetRenderable
{
    public $treePid     = null;
    public $active      = Cms::BOOL_Y;
    public $level       = null;
    public $label       = null;
    public $site_codes  = [];

    public $orderBy                     = "priority";
    public $order                       = SORT_DESC;

    public $enabledCurrentTree          = Cms::BOOL_Y;


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
            'enabledCurrentTree'    => 'Учитывать текущий сайт',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['text', 'string'],
            [['viewFile', 'label', 'active', 'orderBy', 'enabledCurrentTree'], 'string'],
            [['treePid', 'level'], 'integer'],
            [['order'], 'integer'],
            [['site_codes'], 'safe'],
        ]);
    }

    protected function _run()
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

        if ($this->enabledCurrentTree == Cms::BOOL_Y && $currentSite = \Yii::$app->cms->site)
        {
            $this->activeQuery->andWhere(['site_code' => $currentSite->code]);
        }

        if ($this->orderBy)
        {
            $this->activeQuery->orderBy([$this->orderBy => (int) $this->order]);
        }

        return parent::_run();
    }

}
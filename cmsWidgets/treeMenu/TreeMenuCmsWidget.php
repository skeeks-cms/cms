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
    public $site        = null;

    public $orderBy     = "priority";
    public $order       = SORT_DESC;


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
            'treePid'   => 'Родительский раздел',
            'active'    => 'Активность',
            'level'     => 'Уровень вложенности',
            'label'     => 'Заголовок',
            'site'      => 'Разделы привязанные к сайту',
            'orderBy'   => 'По какому параметру сортировать',
            'order'     => 'Направление сортировки',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            ['text', 'string'],
            [['viewFile', 'label', 'site', 'orderBy'], 'string'],
            [['treePid', 'active', 'level'], 'integer'],
            [['order'], 'integer'],
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

        if ($this->site)
        {
            $this->activeQuery->andWhere(['site_code' => $this->site]);
        }

        if ($this->orderBy)
        {
            $this->activeQuery->orderBy([$this->orderBy => $this->order]);
        }

        return parent::_run();
    }

}
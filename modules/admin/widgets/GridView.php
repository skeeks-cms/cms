<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.06.2015
 */
namespace skeeks\cms\modules\admin\widgets;

use skeeks\cms\components\Cms;
use skeeks\cms\grid\GridViewPjaxTrait;
use skeeks\cms\modules\admin\assets\AdminGridAsset;
use skeeks\cms\modules\admin\traits\GridViewSortableTrait;
use skeeks\cms\modules\admin\widgets\gridView\GridViewSettings;
use skeeks\cms\traits\HasComponentConfigFormTrait;
use skeeks\cms\traits\HasComponentDbSettingsTrait;
use skeeks\cms\traits\HasComponentDescriptorTrait;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\jui\Sortable;

/**
 * Простейший крид амдинки.
 *
 * Class GridView
 * @package skeeks\cms\modules\admin\widgets
 */
class GridView extends \yii\grid\GridView
{
    use GridViewSortableTrait;
    use GridViewPjaxTrait;

    public $tableOptions    = ['class' => 'table table-striped table-bordered sx-table'];
    public $options         = ['class' => 'grid-view sx-grid-view'];

    public function init()
    {
        parent::init();

        $this->pjaxClassName = Pjax::className();
    }

    /**
     * Runs the widget.
     */
    public function run()
    {
        $this->pjaxBegin();

            parent::run();
            $this->registerAsset();

        $this->pjaxEnd();
    }

    public function registerAsset()
    {
        $this->registerSortableJs();
        AdminGridAsset::register($this->view);
    }
}
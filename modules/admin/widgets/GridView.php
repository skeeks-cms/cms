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
     * @var string
     */
    public $afterTableLeft     = "";
    /**
     * @var string
     */
    public $afterTableRight     = "";

    /**
     * @var string
     */
    public $beforeTableLeft     = "";
    /**
     * @var string
     */
    public $beforeTableRight     = "";

    /**
     * @var string the layout that determines how different sections of the list view should be organized.
     * The following tokens will be replaced with the corresponding section contents:
     *
     * - `{summary}`: the summary section. See [[renderSummary()]].
     * - `{errors}`: the filter model error summary. See [[renderErrors()]].
     * - `{items}`: the list items. See [[renderItems()]].
     * - `{sorter}`: the sorter. See [[renderSorter()]].
     * - `{pager}`: the pager. See [[renderPager()]].
     * - `{beforeTable}`: the pager. See [[renderPager()]].
     * - `{afterTable}`: the pager. See [[renderPager()]].
     */
    public $layout = "{beforeTable}\n
                      <div class='sx-table-wrapper'>
                          {items}\n
                      </div>
                      {afterTable}
                      \n<div class='pull-left'>{pager}</div>
                      \n<!--<div class='pull-left'>{sorter}</div>-->
                        <div class='pull-right'>{summary}</div>";


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




    /**
     * @param string $name
     * @return bool|string
     */
    public function renderSection($name)
    {
        switch ($name) {
            case "{beforeTable}":
                return $this->renderBeforeTable();
            case "{afterTable}":
                return $this->renderAfterTable();
            default:
                return parent::renderSection($name);
        }
    }



    /**
     * @return string
     */
    public function renderAfterTable()
    {
        if ($this->afterTableLeft || $this->afterTableRight)
        {
            return "<div class='sx-after-table'>
                        <div class='pull-left'>{$this->afterTableLeft}</div>
                        <div class='pull-right'>{$this->afterTableRight}</div>
                    </div>";
        } else
        {
            return "";
        }

    }

    /**
     * @return string
     */
    public function renderBeforeTable()
    {
        if ($this->beforeTableLeft || $this->beforeTableRight)
        {

            return <<<HTML
        <div class='sx-before-table'>
            <div class='pull-left'>{$this->beforeTableLeft}</div>
            <div class='pull-right'>{$this->beforeTableRight}</div>
          </div>
HTML;
        } else
        {
            return '';
        }

    }
}
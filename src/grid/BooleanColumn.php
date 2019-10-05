<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.03.2015
 */

namespace skeeks\cms\grid;

use skeeks\cms\components\Cms;
use yii\grid\DataColumn;

/**
 * Class CreatedAtColumn
 * @package skeeks\cms\grid
 */
class BooleanColumn extends DataColumn
{
    /**
     * @var string the horizontal alignment of each column. Should be one of
     * 'left', 'right', or 'center'. Defaults to `center`.
     */
    public $hAlign = 'center';
    /**
     * @var string the width of each column (matches the CSS width property).
     * Defaults to `90px`.
     * @see http://www.w3schools.com/cssref/pr_dim_width.asp
     */
    public $width = '90px';
    /**
     * @var string|array in which format should the value of each data model be displayed
     * Defaults to `raw`.
     * [[\yii\base\Formatter::format()]] or [[\yii\i18n\Formatter::format()]] is used.
     */
    public $format = 'raw';
    /**
     * @var boolean|string|Closure the page summary that is displayed above the footer.
     * Defaults to false.
     */
    public $pageSummary = false;
    /**
     * @var string label for the true value. Defaults to `Active`.
     */
    public $trueLabel;
    /**
     * @var string label for the false value. Defaults to `Inactive`.
     */
    public $falseLabel;

    /**
     * @var null
     */
    public $falseValue = Cms::BOOL_N;
    public $trueValue = Cms::BOOL_Y;

    /**
     * @var string icon/indicator for the true value. If this is not set, it will use the value from `trueLabel`.
     * If GridView `bootstrap` property is set to true - it will default to [[GridView::ICON_ACTIVE]]
     * `<span class="glyphicon glyphicon-ok text-success"></span>`
     */
    public $trueIcon;
    /**
     * @var string icon/indicator for the false value. If this is null, it will use the value from `falseLabel`.
     * If GridView `bootstrap` property is set to true - it will default to [[GridView::ICON_INACTIVE]]
     * `<span class="fa fa-times text-danger"></span>`
     */
    public $falseIcon;
    /**
     * @var bool whether to show null value as a false icon.
     */
    public $showNullAsFalse = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (empty($this->trueLabel)) {
            $this->trueLabel = \Yii::t('yii', 'Yes');
        }
        if (empty($this->falseLabel)) {
            $this->falseLabel = \Yii::t('yii', 'No');
        }
        if (!$this->filter) {
            $this->filter = [$this->trueValue => $this->trueLabel, $this->falseValue => $this->falseLabel];
        }
        if (empty($this->trueIcon)) {
            $this->trueIcon = '<span class="fa fa-check text-success" title="' . $this->trueLabel . '"></span>';
        }
        if (empty($this->falseIcon)) {
            $this->falseIcon = '<span class="fa fa-times text-danger" title="' . $this->falseLabel . '"></span>';
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getDataCellValue($model, $key, $index)
    {
        /*if (!isset($model->{$key})) {
            return '';
        }*/
        $value = parent::getDataCellValue($model, $key, $index);

        if (is_integer($value)) {
            if ($value == 0) {
                return $this->falseIcon;
            } else {
                return $this->trueIcon;
            }
        }

        if (is_string($value)) {
            if ($value == "N") {
                return $this->falseIcon;
            } else {
                return $this->trueIcon;
            }
        }

        if ($this->trueValue !== true) {
            if ($value == $this->falseValue) {
                return $this->falseIcon;
            } else {
                return $this->trueIcon;
            }
        } else {
            if ($value !== null) {
                return $value ? $this->trueIcon : $this->falseIcon;
            }
            return $value . ($this->showNullAsFalse ? $this->falseIcon : $value);
        }

    }
}
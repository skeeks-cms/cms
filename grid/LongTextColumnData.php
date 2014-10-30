<?php
/**
 * LongTextColumnData
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\grid;

use skeeks\sx\String;
use yii\grid\DataColumn;

/**
 * Class LongTextColumnData
 * @package skeeks\cms\grid
 */
class LongTextColumnData extends DataColumn
{
    public $maxLength = 200;
    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $text = $model->{$this->attribute};
        return "<small>" . String::substr($text, 0, $this->maxLength) . ( (String::strlen($text) > $this->maxLength) ? " ..." : "" ) . "</small>";

    }
}
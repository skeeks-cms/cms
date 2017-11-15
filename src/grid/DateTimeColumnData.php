<?php
/**
 * DateTimeColumnData
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\grid;

use yii\grid\DataColumn;

/**
 * Class DateTimeColumnData
 * @package skeeks\cms\grid
 */
class DateTimeColumnData extends DataColumn
{
    public $filter = false;

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $timestamp = $model->{$this->attribute};
        return \Yii::$app->formatter->asDatetime($timestamp) . "<br /><small>" . \Yii::$app->formatter->asRelativeTime($timestamp) . "</small>";
    }
}
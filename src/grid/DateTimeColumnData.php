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
use yii\helpers\Html;

/**
 * Class DateTimeColumnData
 * @package skeeks\cms\grid
 */
class DateTimeColumnData extends DataColumn
{
    public $filter = false;

    public $headerOptions = [
        'style' => 'width: 130px;'
    ];

    public $contentOptions = [
        'style' => 'width: 130px;'
    ];

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $timestamp = $model->{$this->attribute};
        return Html::tag("span", \Yii::$app->formatter->asRelativeTime($timestamp), [
            'title' => \Yii::$app->formatter->asDatetime($timestamp),
            'data-toggle' => "tooltip"
        ]);
        //return \Yii::$app->formatter->asDatetime($timestamp) . "<br /><small>" . \Yii::$app->formatter->asRelativeTime($timestamp) . "</small>";
    }
}
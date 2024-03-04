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

    const VIEW_RELITIVE_TYME = "VIEW_RELITIVE_TYME";
    const VIEW_DATE = "VIEW_DATE";

    public $view_type = self::VIEW_RELITIVE_TYME;

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        if ($this->view_type == self::VIEW_RELITIVE_TYME) {
            $timestamp = $model->{$this->attribute};
            return Html::tag("span", \Yii::$app->formatter->asRelativeTime($timestamp), [
                'title' => \Yii::$app->formatter->asDatetime($timestamp),
                'data-toggle' => "tooltip"
            ]);
        } elseif ($this->view_type == self::VIEW_DATE) {
            $timestamp = $model->{$this->attribute};
            return Html::tag("span", \Yii::$app->formatter->asDate($timestamp), [
                'title' => \Yii::$app->formatter->asDatetime($timestamp),
                'data-toggle' => "tooltip"
            ]);
        }

        //return \Yii::$app->formatter->asDatetime($timestamp) . "<br /><small>" . \Yii::$app->formatter->asRelativeTime($timestamp) . "</small>";
    }
    /**
     * @inheritdoc
     */
    public function renderDataCellContentForExport($model, $key, $index)
    {
        if ($this->view_type == self::VIEW_RELITIVE_TYME) {
            $timestamp = $model->{$this->attribute};
            return \Yii::$app->formatter->asDatetime($timestamp, "php:Y-m-d H:i:s");
        } elseif ($this->view_type == self::VIEW_DATE) {
            $timestamp = $model->{$this->attribute};
            return \Yii::$app->formatter->asDatetime($timestamp, "php:Y-m-d ");
        }

        //return \Yii::$app->formatter->asDatetime($timestamp) . "<br /><small>" . \Yii::$app->formatter->asRelativeTime($timestamp) . "</small>";
    }
}
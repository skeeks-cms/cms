<?php
/**
 * FileSizeColumnData
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\grid;

use yii\grid\DataColumn;

/**
 * Class FileSizeData
 * @package skeeks\cms\grid
 */
class FileSizeColumnData extends DataColumn
{
    public $filter = false;

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $size = $model->{$this->attribute};
        return \Yii::$app->formatter->asShortSize($size);
    }
}
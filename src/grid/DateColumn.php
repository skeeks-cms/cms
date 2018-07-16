<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\grid;

use yii\grid\DataColumn;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class DateColumn extends DataColumn
{
    public $filter = false;

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $timestamp = $model->{$this->attribute};
        return \Yii::$app->formatter->asDate($timestamp);
    }
}
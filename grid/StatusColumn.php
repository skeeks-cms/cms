<?php
/**
 * StatusColumn
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\grid;

use skeeks\cms\models\User;
use yii\grid\DataColumn;

/**
 * Class StatusColumn
 * @package skeeks\cms\grid
 */
class StatusColumn extends DataColumn
{
    public $format = 'html';
    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $class = 'label-default';
        if ($model->status == \skeeks\cms\models\behaviors\HasStatus::STATUS_ACTIVE)
        {
            $class = 'label-success';
        } else if ($model->status == \skeeks\cms\models\behaviors\HasStatus::STATUS_DELETED)
        {
            $class = 'label-danger';
        } else if ($model->status == \skeeks\cms\models\behaviors\HasStatus::STATUS_ONMODER)
        {
            $class = 'label-warning';
        }
        return '<span class="label ' . $class . '">' . $model->getStatusText() . '</span>';
    }
}
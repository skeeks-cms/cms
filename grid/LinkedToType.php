<?php
/**
 * LinkedToType
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 12.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\grid;

use yii\grid\DataColumn;

/**
 * Class DateTimeColumnData
 * @package skeeks\cms\grid
 */
class LinkedToType extends DataColumn
{
    public $filter      = false;
    public $attribute   = "linked_to_model";
    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        return \Yii::$app->registeredModels->getModelDataByCode( $model->{$this->attribute} )['name'];
    }
}
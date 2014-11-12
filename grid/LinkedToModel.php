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
class LinkedToModel extends DataColumn
{
    public $filter                  = false;
    public $linkedModelAttr         = null;
    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $linkedModel = $model->findLinkedToModel();
        if ($linkedModel)
        {
            if (isset($linkedModel->name))
            {
                return $linkedModel->name;
            }

            return $linkedModel->primaryKey;
        } else
        {
            return '';
        }
    }
}
<?php
/**
 * ImageColumnData
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
 * Class ImageColumnData
 * @package skeeks\cms\grid
 */
class ImageColumnData extends DataColumn
{
    public $filter      = false;
    public $maxWidth    = "50";
    public $noImage     = "http://vk.com/images/deactivated_100.gif"; //TODO: refactor this moment
    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $mainImage = $this->getFilesGroups()->getComponent('image');

        if ($mainImage->getFirstSrc())
        {
            return $mainImage->getFirstSrc();
        }

        return \Yii::$app->params['noimage'];

        $model->getMainImageSrc()

        return "<img src='" . $src . "' style='width: " . $this->maxWidth . "px;' />";
    }
}
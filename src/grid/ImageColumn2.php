<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.09.2015
 */

namespace skeeks\cms\grid;

use yii\grid\DataColumn;

/**
 * Class ImageColumn2
 * @package skeeks\cms\grid
 */
class ImageColumn2 extends DataColumn
{
    public $filter = false;
    public $maxWidth = "50";
    public $relationName = "image";
    public $label = "Изображение";

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        if ($this->relationName && $file = $model->{$this->relationName}) {
            $originalSrc = $file->src;
            $src = \Yii::$app->imaging->getImagingUrl($file->src,
                new \skeeks\cms\components\imaging\filters\Thumbnail());
        } else {
            $src = \Yii::$app->cms->noImageUrl;
            $originalSrc = $src;
        }


        return "<a href='" . $originalSrc . "' class='sx-fancybox sx-img-link-hover' title='Увеличить' data-pjax='0'>
                    <img src='" . $src . "' style='width: " . $this->maxWidth . "px;' />
                </a>";
    }
}
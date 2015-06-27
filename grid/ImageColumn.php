<?php
/**
 * ImageColumn
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\grid;

/**
 * Class ImageColumn
 * @package skeeks\cms\grid
 */
class ImageColumn extends ImageColumnData
{

    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $originalSrc = $model->getMainImageSrc();
        $src = $model->getPreviewMainImageSrc();
        if (!$src)
        {
            $src = \Yii::$app->cms->moduleAdmin()->noImage;
            $originalSrc = $src;
        }

        return "<a href='" . $originalSrc . "' class='sx-fancybox sx-img-link-hover' title='Увеличить' data-pjax='0'>
                    <img src='" . $src . "' style='width: " . $this->maxWidth . "px;' />
                </a>";
    }
}
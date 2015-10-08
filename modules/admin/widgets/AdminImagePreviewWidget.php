<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 08.10.2015
 */

namespace skeeks\cms\modules\admin\widgets;

use skeeks\cms\mail\helpers\Html;
use skeeks\cms\models\CmsStorageFile;
use yii\base\Widget;

class AdminImagePreviewWidget extends Widget
{
    /**
     * @var CmsStorageFile
     */
    public $image = null;

    public $maxWidth        = "50px";

    public function run()
    {
        if ($this->image)
        {
            $originalSrc    = $this->image->src;
            $src            = \Yii::$app->imaging->getImagingUrl($this->image->src, new \skeeks\cms\components\imaging\filters\Thumbnail());
        } else
        {
            $src            = \Yii::$app->cms->moduleAdmin()->noImage;
            $originalSrc    = $src;
        }


        return "<a href='" . $originalSrc . "' class='sx-fancybox sx-img-link-hover' title='Увеличить' data-pjax='0'>
                    <img src='" . $src . "' style='width: " . $this->maxWidth . ";' />
                </a>";
    }
}
<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 *
 * @var $model \skeeks\cms\models\User
 */
/** 
* @var $this yii\web\View
*/

$this->registerCss(<<<CSS
.sx-info .label {
    font-size: 1rem;
}
.sx-info .sx-value {
    font-size: 1.25rem;
}
.sx-info {
    margin-bottom: 1.5rem;
}
CSS
);
?>
<h1><?php echo $model->displayName; ?></h1>

<div class="sx-project-content">
    <div class="row">
        <div class="col-md-3">
            <div class="sx-photo">
                <div class="sx-info" style="margin-bottom: 0;">
                    <img src="<?= \Yii::$app->imaging->thumbnailUrlOnRequest($model->image ? $model->image->src : \skeeks\cms\helpers\Image::getCapSrc(),
                        new \skeeks\cms\components\imaging\filters\Thumbnail([
                            'h' => 300,
                            'w' => 300,
                            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_INSET,
                        ])); ?>" alt=""
                         class="img-fluid" data-toggle="tooltip" data-html="true" style="    border-radius: var(--base-radius);" data-original-title="" title="">
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="row">
                <div class="col-md-6">
                    <div class="sx-info">
                        <div class="sx-label">E-mail</div>
                        <div class="sx-value"><?php echo $model->email; ?></div>
                    </div>
                    <div class="sx-info">
                        <div class="sx-label">Зарегистрирован</div>
                        <div class="sx-value"><?php echo \Yii::$app->formatter->asDate($model->created_at); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="sx-info">
                        <div class="sx-label">Телефон</div>
                        <div class="sx-value"><?php echo $model->phone; ?></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
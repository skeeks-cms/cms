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
\skeeks\cms\widgets\assets\CmsProfileAsset::register($this);
?>
<h1><?php echo $model->displayName; ?></h1>

<div class="sx-content">
    <div class="row">
        <div class="col-md-3">
            <div class="sx-profile-photo">
                <div>
                    <img src="<?= \Yii::$app->imaging->thumbnailUrlOnRequest($model->image ? $model->image->src : \skeeks\cms\helpers\Image::getCapSrc(),
                        new \skeeks\cms\components\imaging\filters\Thumbnail([
                            'h' => 300,
                            'w' => 300,
                            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_INSET,
                        ])); ?>" alt=""
                         class="img-fluid sx-profile-photo__image" data-toggle="tooltip" data-html="true" data-original-title="" title="">
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="row">
                <div class="col-md-6">
                    <div class="sx-profile-info">
                        <div class="sx-profile-info__label">E-mail</div>
                        <div class="sx-profile-info__value"><?php echo $model->email; ?></div>
                    </div>
                    <div class="sx-profile-info">
                        <div class="sx-profile-info__label">Зарегистрирован</div>
                        <div class="sx-profile-info__value"><?php echo \Yii::$app->formatter->asDate($model->created_at); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="sx-profile-info">
                        <div class="sx-profile-info__label">Телефон</div>
                        <div class="sx-profile-info__value"><?php echo $model->phone; ?></div>
                    </div>
                    <?php if($model->birthday_at) : ?>
                        <div class="sx-profile-info">
                            <div class="sx-profile-info__label">Дата рождения</div>
                            <div class="sx-profile-info__value"><?php echo \Yii::$app->formatter->asDate($model->birthday_at); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

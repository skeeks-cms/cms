<?php
/**
 * one
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.02.2015
 * @since 1.0.0
 */
?>
<div class="new-wrapper sx-publication">
    <h3><a href="<?= $model->getPageUrl(); ?>"><?= $model->name; ?></a></h3>
    <small class="date text-muted"><?= \Yii::$app->formatter->asDate($model->published_at, 'short')?> (<?= \Yii::$app->formatter->asRelativeTime($model->published_at)?>)</small>
    <div class="new-content">
    <p>
        <? if ($model->hasMainImageSrc()) : ?>
            <a class="pull-left" style="margin-right: 10px; margin-bottom: 10px;" href="<?= $model->getPageUrl(); ?>" title="<?= $model->name; ?>">
                <img alt="<?= $model->name; ?>" title="<?= $model->name; ?>" src="<?=
                \Yii::$app->imaging->getImagingUrl($model->getMainImageSrc(),
                    new \skeeks\cms\components\imaging\filters\Thumbnail([
                        'w'    => 200,
                        'h'    => 150,
                    ])
                ) ?>
                <?/*= $model->getMainImageSrc(); */?>" class="img-responsive">
            </a>
        <? endif; ?>
        <?= $model->description_short; ?></p>
    <p><a href="<?= $model->getPageUrl(); ?>" title="<?= $model->name; ?>">Подробнее</a></p>
    </div>
</div>

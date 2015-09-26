<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.03.2015
 *
 * @var \skeeks\cms\models\CmsContentElement $model
 *
 */
?>

<div class="row margin-bottom-20">
    <div class="col-sm-4 sm-margin-bottom-20">
        <? if ($model->image) : ?>
            <img src="<?= \Yii::$app->imaging->getImagingUrl($model->image->src,
            new \skeeks\cms\components\imaging\filters\Thumbnail([
                'w'    => 409,
                'h'    => 258,
            ])
        ) ?>" title="<?= $model->name; ?>" alt="<?= $model->name; ?>" class="img-responsive" />
        <? else: ?>
            <img src="<?= \skeeks\cms\helpers\Image::getCapSrc(); ?>" title="<?= $model->name; ?>" alt="<?= $model->name; ?>" class="img-responsive" />
        <? endif; ?>

    </div>
    <div class="col-sm-8 news-v3">
        <div class="news-v3-in-sm no-padding">
            <h2>
                <a href="<?= $model->url; ?>" title="<?= $model->name; ?>"><?= $model->name; ?></a>
            </h2>

            <ul class="list-inline posted-info">
                <? if ($model->createdBy) : ?>
                    <li>Добавил: <a href="<?= $model->createdBy->getPageUrl(); ?>" title="<?= $model->createdBy->name; ?>"><?= $model->createdBy->name; ?></a></li>
                <? endif; ?>
                <? if ($model->cmsTree) : ?>
                    <li>Категория: <a href="<?= $model->cmsTree->url; ?>" title="<?= $model->cmsTree->name; ?>"><?= $model->cmsTree->name; ?></a></li>
                <? endif; ?>
                <li>Время публикации: <?= \Yii::$app->formatter->asDate($model->published_at, 'full')?></li>
                <? if ($testValue = $model->relatedPropertiesModel->getAttribute('test')) : ?>
                    <li><?= $model->relatedPropertiesModel->getAttributeLabel('test'); ?>: <?= $testValue; ?></li>
                <? endif; ?>
            </ul>

            <p><?= $model->description_short; ?></p>
            <p><a href="<?= $model->url; ?>">Читать полностью</a></p>

        </div>
    </div>
</div>

<div class="clearfix margin-bottom-20"><hr></div>
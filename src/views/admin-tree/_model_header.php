<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsTree
 */
?>
<div class="row" style="margin-bottom: 5px;">



    <? if ($model->image) : ?>
        <div class="col my-auto" style="max-width: 60px">
            <img style="border: 2px solid #ededed; border-radius: 5px;" src="<?php echo \Yii::$app->imaging->getImagingUrl($model->image->src,
                new \skeeks\cms\components\imaging\filters\Thumbnail()); ?>" />
        </div>
    <? endif; ?>
    <div class="col my-auto">
        <h1 style="margin-bottom: 0px; line-height: 1.1;">
            <?php echo $model->name; ?>

            <? if ($model->is_adult) : ?>
                <span style="font-size: 17px; color: red; font-weight: bold; color: #ff0000bd;">
                    <span data-toggle="tooltip" title="Этот раздел содержит информацию для взрослых. Имеет возрастные ограничения 18+">[18+]</span>
                </span>
            <? endif; ?>

            <? if ($model->is_index == 0) : ?>
                <span style="font-size: 17px; color: red; font-weight: bold; color: #ff0000bd;">
                    <span data-toggle="tooltip" title="Эта страница не индексируется поисковыми системами!">[no index]</span>
                </span>
            <? endif; ?>

            <? if ($model->is_index == 0 || $model->isRedirect || $model->isCanonical) : ?>
                <span style="font-size: 17px; color: red; font-weight: bold; color: #ff0000bd;">
                    <span data-toggle="tooltip" title="Эта страница не попадает в карту сайта!">[no sitemap]</span>
                </span>
            <? endif; ?>

            <? if ($model->isCanonical) : ?>
                <span style="font-size: 17px; color: red; font-weight: bold; color: #ff0000bd;">
                    <span data-toggle="tooltip" title="У этой страницы задана атрибут rel=canonical на сатраницу: <?php echo $model->canonicalUrl; ?>">[canonical]</span>
                </span>
            <? endif; ?>

            <? if ($model->isRedirect) : ?>
                <span style="font-size: 17px;">
                    <i class="fas fa-directions" data-toggle="tooltip" title="<?= $model->redirect_code ?> редиррект посетителя на страницу: <?= $model->url; ?>"></i>
                </span>
            <? endif; ?>


        </h1>
        <div class="sx-small-info" style="font-size: 10px; color: silver;">
            <span title="ID записи - уникальный код записи в базе данных." data-toggle="tooltip"><i class="fas fa-key"></i> <?php echo $model->id; ?></span>
            <? if ($model->created_at) : ?>
                <span style="margin-left: 5px;" data-toggle="tooltip" title="Запись создана в базе: <?php echo \Yii::$app->formatter->asDatetime($model->created_at); ?>"><i class="far fa-clock"></i> <?php echo \Yii::$app->formatter->asDate($model->created_at); ?></span>
            <? endif; ?>
            <? if ($model->created_by) : ?>
                <span style="margin-left: 5px;" data-toggle="tooltip" title="Запись создана пользователем с ID: <?php echo $model->createdBy->id; ?>"><i class="far fa-user"></i> <?php echo $model->createdBy->shortDisplayName; ?></span>
            <? endif; ?>
            <? if ($model->pid) : ?>
                <span style="margin-left: 5px;" data-toggle="tooltip" title=""><i class="far fa-folder"></i>
                    <?php echo $model->fullName; ?>
                </span>
            <? endif; ?>
            <?/* if ($model->tree_id) : */?><!--
                <span style="margin-left: 5px;" data-toggle="tooltip" title="<?php /*echo $model->cmsTree->fullName; */?>"><i class="far fa-folder"></i> <?php /*echo $model->cmsTree->name; */?></span>
            --><?/* endif; */?>

        </div>
    </div>
    <div class="col my-auto" style="max-width: 70px; text-align: right;">
        <a href="<?php echo $model->url; ?>" data-toggle="tooltip" class="btn btn-default" target="_blank" title="<?php echo \Yii::t('skeeks/cms', 'Watch to site (opens new window)'); ?>"><i class="fas fa-external-link-alt"></i></a>
    </div>
</div>

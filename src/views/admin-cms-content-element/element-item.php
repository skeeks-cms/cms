<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 06.03.2015
 *
 * @var \skeeks\cms\shop\models\ShopCmsContentElement $model
 *
 */
/* @var $this yii\web\View */
//Если этот товар привязан к главному
$infoModel = $model;
?>
<article class="card-prod h-100 to-cart-fly-wrapper">
    <div class="card-prod--labels">
    </div>
    <div class="card-prod--photo">
        <? if ($infoModel->image) : ?>
            <?
            $preview = \Yii::$app->imaging->getPreview($infoModel->image,
                new \skeeks\cms\components\imaging\filters\Thumbnail([
                    'w'          => 300,
                    'h'          => 300,
                    'm'          => \Imagine\Image\ImageInterface::THUMBNAIL_INSET,
                    'sx_preview' => \skeeks\cms\components\storage\SkeeksSuppliersCluster::IMAGE_PREVIEW_MEDIUM,
                ]), $model->code
            );
            ?>
            <img class="to-cart-fly-img" src="<?= $preview->src; ?>" title="<?= \yii\helpers\Html::encode($infoModel->name); ?>" alt="<?= \yii\helpers\Html::encode($infoModel->name); ?>"/>
        <? else : ?>
            <img class="img-fluid to-cart-fly-img" src="<?= \skeeks\cms\helpers\Image::getCapSrc(); ?>" alt="<?= $infoModel->name; ?>">
        <? endif; ?>
    </div>
    <div class="card-prod--inner g-px-10">
        <div class="card-prod--reviews">
            <div class="card-prod--category">
                <a href="#" class="btn btn-primary btn-xs sx-btn-dettach"><i class="far fa-trash-alt"></i> Отвязать</a>
            </div>
            <div class="card-prod--category">
                <? if ($model->cmsTree) : ?>
                    <a href="<?= $model->cmsTree->url; ?>" style="color: gray; font-size: 11px;"><?= $model->cmsTree->name; ?></a>
                <? endif; ?>
            </div>
            <div class="card-prod--title">
                <a target="_blank" href="<?= $model->url; ?>" title="<?= $model->name; ?>" data-pjax="0" class="sx-card-prod--title-a sx-main-text-color g-text-underline--none--hover"><?= $infoModel->name; ?></a>
            </div>
        </div>
    </div>
</article>

<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/**
 * @var $cmsUser \skeeks\cms\models\CmsUser
 */
$widget = $this->context;
$cmsUser = $widget->cmsUser;

?>
<div class="d-flex flex-row sx-preview-card sx-preview-card--person">

    <div class="sx-preview-card__media">
        <?php
        $w = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
            'controllerId' => '/cms/admin-user',
            'modelId'      => $cmsUser->id,
            'content'      => $cmsUser->shortDisplayNameWithAlias,
            'isRunFirstActionOnClick'      => true,
            'options'      => [
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
                'data-pjax'   => '0',
                'class'       => 'sx-preview-card__media-link',
            ],
        ]); ?>
        <? if ($cmsUser->image) : ?>
            <img src="<?= \Yii::$app->imaging->thumbnailUrlOnRequest($cmsUser->image ? $cmsUser->image->src : \skeeks\cms\helpers\Image::getCapSrc(),
                new \skeeks\cms\components\imaging\filters\Thumbnail([
                    'h' => $widget->prviewImageSize,
                    'w' => $widget->prviewImageSize,
                    'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
                ])); ?>" alt=""
                 class="sx-photo sx-img-size-<?= $widget->isSmall ? "small" : $widget->prviewImageSize; ?>"
                 data-toggle="tooltip"
                 data-html="true"
            >
        <? else : ?>
            <div class="sx-no-photo sx-img-size-<?= $widget->isSmall ? "small" : $widget->prviewImageSize; ?>">
                <?= \skeeks\cms\helpers\StringHelper::strtoupper(
                    \skeeks\cms\helpers\StringHelper::substr($cmsUser->shortDisplayNameWithAlias, 0, 2)
                ); ?>
            </div>
        <? endif; ?>
        <?php $w::end(); ?>
    </div>

    <div class="sx-preview-card__content sx-collection-cell sx-collection-cell--stack">

        <?php
        $options = \yii\helpers\ArrayHelper::merge([
            'class' => '',
            'href'  => '#',

            'data-toggle' => 'tooltip',
            'data-html'   => 'true',
            'data-pjax'   => '0',
        ], (array) $widget->tagNameOptions);
        \yii\helpers\Html::addCssClass($options, ['sx-preview-card__title', 'sx-collection-cell__primary']);

        echo \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::widget([
            'controllerId' => '/cms/admin-user',
            'modelId'      => $cmsUser->id,
            'isRunFirstActionOnClick'      => true,
            'content'      => $cmsUser->shortDisplayNameWithAlias,
            'tag'          => $widget->tagName,
            'options'      => $options,
        ]); ?>


        <? if ($widget->isShowOnlyName === false) : ?>

            <? if ($cmsUser->phone) : ?>
                <div class="sx-phone sx-preview-card__meta sx-collection-cell__secondary">
                    <a href="tel:<?= $cmsUser->phone; ?>">
                        <i class="fas fa-phone"></i> <?= $cmsUser->phone; ?>
                    </a>
                </div>
            <? endif; ?>
            <? if ($cmsUser->email) : ?>
                <div class="sx-mail sx-preview-card__meta sx-collection-cell__secondary">
                    <a href="mailto:<?= $cmsUser->email; ?>">
                        <i class="far fa-envelope"></i> <?= $cmsUser->email; ?>
                    </a>
                </div>
            <? endif; ?>
        <? endif; ?>
        <? if ($widget->append) : ?>
            <?php echo $widget->append; ?>
        <? endif; ?>
    </div>


</div>

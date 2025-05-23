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

$class = 'g-brd-gray-light-v4';

?>
<div class="d-flex flex-row sx-preview-card">

    <div>
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
                'style'       => 'border: 0;',
            ],
        ]); ?>
        <? if ($cmsUser->image) : ?>
            <img src="<?= \Yii::$app->imaging->thumbnailUrlOnRequest($cmsUser->image ? $cmsUser->image->src : \skeeks\cms\helpers\Image::getCapSrc(),
                new \skeeks\cms\components\imaging\filters\Thumbnail([
                    'h' => $widget->prviewImageSize,
                    'w' => $widget->prviewImageSize,
                    'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
                ])); ?>" alt=""
                 class="sx-photo <?= $class; ?> sx-img-size-<?= $widget->isSmall ? "small" : $widget->prviewImageSize; ?>"
                 data-toggle="tooltip"
                 data-html="true"
            >
        <? else : ?>
            <div class="sx-no-photo g-brd-gray-light-v4 sx-img-size-<?= $widget->isSmall ? "small" : $widget->prviewImageSize; ?>">
                <?= \skeeks\cms\helpers\StringHelper::strtoupper(
                    \skeeks\cms\helpers\StringHelper::substr($cmsUser->shortDisplayNameWithAlias, 0, 2)
                ); ?>
            </div>
        <? endif; ?>
        <?php $w::end(); ?>
    </div>

    <div>

        <?php
        $options = \yii\helpers\ArrayHelper::merge([
            'style' => 'text-align: left; white-space: nowrap;',
            'class' => '',
            'href'  => '#',

            'data-toggle' => 'tooltip',
            'data-html'   => 'true',
            'data-pjax'   => '0',
        ], (array) $widget->tagNameOptions);

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
                <div class="sx-phone">
                    <a href="tel:<?= $cmsUser->phone; ?>" style="color: gray; font-size: 12px; text-decoration: none; border-bottom: 0px;">
                        <i class="fas fa-phone"></i> <?= $cmsUser->phone; ?>
                    </a>
                </div>
            <? endif; ?>
            <? if ($cmsUser->email) : ?>
                <div class="sx-mail">
                    <a href="mailto:<?= $cmsUser->email; ?>"  style="color: gray; font-size: 12px; text-decoration: none; border-bottom: 0px;">
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



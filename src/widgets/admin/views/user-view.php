<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

use Imagine\Image\ImageInterface;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\components\imaging\filters\Thumbnail;
use skeeks\cms\helpers\StringHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $cmsUser \skeeks\cms\models\CmsUser */

$widget = $this->context;
$cmsUser = $widget->cmsUser;
$name = (string)$cmsUser->shortDisplayNameWithAlias;
$imageSizeClass = $widget->isSmall ? 'small' : $widget->prviewImageSize;

if ($cmsUser->image) {
    $mediaContent = Html::img(
        \Yii::$app->imaging->thumbnailUrlOnRequest($cmsUser->image->src, new Thumbnail([
            'h' => $widget->prviewImageSize,
            'w' => $widget->prviewImageSize,
            'm' => ImageInterface::THUMBNAIL_OUTBOUND,
        ])),
        [
            'alt'   => '',
            'class' => 'sx-photo sx-img-size-'.$imageSizeClass,
        ]
    );
} else {
    $initials = StringHelper::strtoupper(StringHelper::substr($name, 0, 2));
    $mediaContent = Html::tag('span', Html::encode($initials), [
        'class' => 'sx-no-photo sx-img-size-'.$imageSizeClass,
    ]);
}

$titleOptions = ArrayHelper::merge([
    'class'       => '',
    'data-toggle' => 'tooltip',
    'data-html'   => 'true',
    'data-pjax'   => '0',
], (array)$widget->tagNameOptions);
ArrayHelper::remove($titleOptions, 'href');
Html::addCssClass($titleOptions, ['sx-preview-card__title', 'sx-collection-cell__primary']);
?>
<div class="sx-preview-card sx-preview-card--person">
    <div class="sx-preview-card__media">
        <?= BackendEntityLink::widget([
            'controllerId' => '/cms/admin-user',
            'modelId'      => $cmsUser->id,
            'content'      => $mediaContent,
            'options'      => [
                'class'      => 'sx-preview-card__media-link',
                'aria-label' => $name,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ],
        ]); ?>
    </div>

    <div class="sx-preview-card__content sx-collection-cell sx-collection-cell--stack">
        <?= BackendEntityLink::widget([
            'controllerId' => '/cms/admin-user',
            'modelId'      => $cmsUser->id,
            'label'        => $name,
            'tag'          => $widget->tagName,
            'options'      => $titleOptions,
        ]); ?>

        <?php if ($widget->isShowOnlyName === false) : ?>
            <?php if ($cmsUser->phone) : ?>
                <div class="sx-phone sx-preview-card__meta sx-collection-cell__secondary">
                    <?= Html::a(
                        '<i class="fas fa-phone"></i> '.Html::encode($cmsUser->phone),
                        'tel:'.$cmsUser->phone
                    ); ?>
                </div>
            <?php endif; ?>
            <?php if ($cmsUser->email) : ?>
                <div class="sx-mail sx-preview-card__meta sx-collection-cell__secondary">
                    <?= Html::a(
                        '<i class="far fa-envelope"></i> '.Html::encode($cmsUser->email),
                        'mailto:'.$cmsUser->email
                    ); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($widget->append) : ?>
            <?= $widget->append; ?>
        <?php endif; ?>
    </div>
</div>

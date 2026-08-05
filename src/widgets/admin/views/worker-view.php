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
use skeeks\cms\widgets\user\UserOnlineWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $cmsUser \skeeks\cms\models\CmsUser */

$widget = $this->context;
$cmsUser = $widget->user;
$name = (string)$cmsUser->shortDisplayNameWithAlias;

$currentTask = null;
$isWorkingNow = (bool)$cmsUser->isWorkingNow;
$isCurrentTaskAvailable = true;

if ($isWorkingNow) {
    $currentTask = $cmsUser->getExecutorTasks()->statusInWork()->one();

    if ($currentTask && \Yii::$app->user->id != $cmsUser->id) {
        $isCurrentTaskAvailable = \Yii::$app->user->can('cms/admin-task/manage', ['model' => $currentTask]);
    }
}

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

$titleContent = Html::encode($name);
$titleContent .= UserOnlineWidget::widget([
    'user'    => $cmsUser,
    'options' => [
        'height' => '8px;',
    ],
]);

if ($isWorkingNow) {
    if ($currentTask) {
        $stateTitle = $isCurrentTaskAvailable ? 'В работе: '.$currentTask->name : 'В работе';
        $titleContent .= Html::tag('span', Html::tag('i', '', ['class' => 'fas fa-play']), [
            'class'       => 'sx-worker-work-state is-task',
            'data-toggle' => 'tooltip',
            'title'       => $stateTitle,
        ]);
    } else {
        $titleContent .= Html::tag('span', Html::tag('i', '', ['class' => 'fas fa-exclamation']), [
            'class'       => 'sx-worker-work-state is-without-task',
            'data-toggle' => 'tooltip',
            'title'       => 'В работе, но задача не запущена',
        ]);
    }
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
            'controllerId' => '/cms/admin-worker',
            'modelId'      => $cmsUser->id,
            'content'      => $mediaContent,
            'options'      => [
                'class'       => 'sx-preview-card__media-link',
                'aria-label'  => $name,
                'data-toggle' => 'tooltip',
                'data-html'   => 'true',
            ],
        ]); ?>
    </div>

    <div class="sx-preview-card__content sx-collection-cell sx-collection-cell--stack">
        <?= BackendEntityLink::widget([
            'controllerId' => '/cms/admin-worker',
            'modelId'      => $cmsUser->id,
            'content'      => $titleContent,
            'tag'          => $widget->tagName,
            'options'      => $titleOptions,
        ]); ?>

        <?php if ($widget->isSmall === false) : ?>
            <?php if ($cmsUser->post) : ?>
                <div class="sx-preview-card__meta sx-collection-cell__secondary">
                    <?= Html::encode($cmsUser->post); ?>
                </div>
            <?php endif; ?>

            <?php if ($widget->append) : ?>
                <?= $widget->append; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

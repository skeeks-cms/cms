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
$cmsUser = $widget->user;

$currentTask = null;
$isWorkingNow = (bool)$cmsUser->isWorkingNow;
$isCurrentTaskAvailable = true;

if ($isWorkingNow) {
    $currentTask = $cmsUser->getExecutorTasks()->statusInWork()->one();

    if ($currentTask && \Yii::$app->user->id != $cmsUser->id) {
        $isCurrentTaskAvailable = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $currentTask]);
    }
}

?>
<div class="d-flex flex-row sx-preview-card sx-preview-card--person">

    <div class="my-auto sx-preview-card__media">
        <?php
        $w = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
            'controllerId'            => '/cms/admin-worker',
            'modelId'                 => $cmsUser->id,
            'content'                 => $cmsUser->shortDisplayNameWithAlias,
            'isRunFirstActionOnClick' => true,
            'options'                 => [
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

    <div class="my-auto sx-preview-card__content sx-collection-cell sx-collection-cell--stack">

        <?php
        $options = \yii\helpers\ArrayHelper::merge([
            'class' => '',
            'href'  => '#',

            'data-toggle' => 'tooltip',
            'data-html'   => 'true',
            'data-pjax'   => '0',
        ], (array)$widget->tagNameOptions);
        \yii\helpers\Html::addCssClass($options, ['sx-preview-card__title', 'sx-collection-cell__primary']);
        ?>

        <? $ajaxWidget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
            'controllerId'            => '/cms/admin-worker',
            'modelId'                 => $cmsUser->id,
            'isRunFirstActionOnClick' => true,
            'tag'                     => $widget->tagName,
            'options'                 => $options,
        ]); ?>
        <?php echo $cmsUser->shortDisplayNameWithAlias; ?>
        <?php echo \skeeks\cms\widgets\user\UserOnlineWidget::widget([
            'user'    => $cmsUser,
            'options' => [
                'height' => '8px;',
                //'style' => 'margin-bottom: 2px;',
            ],

        ]); ?>
        <?php if ($isWorkingNow) : ?>
            <?php if ($currentTask) : ?>
                <span class="sx-worker-work-state is-task" data-toggle="tooltip" title="<?php echo $isCurrentTaskAvailable ? "В работе: ".\yii\helpers\Html::encode($currentTask->name) : "В работе"; ?>">
                    <i class="fas fa-play"></i>
                </span>
            <?php else : ?>
                <span class="sx-worker-work-state is-without-task" data-toggle="tooltip" title="В работе, но задача не запущена">
                    <i class="fas fa-exclamation"></i>
                </span>
            <?php endif; ?>
        <?php endif; ?>
        <?php $ajaxWidget::end(); ?>


        <?php if ($widget->isSmall === false) : ?>
            <?php if ($cmsUser->post) : ?>
                <div class="sx-preview-card__meta sx-collection-cell__secondary"><?php echo $cmsUser->post; ?></div>
            <?php endif; ?>


            <? if ($widget->append) : ?>
                <?php echo $widget->append; ?>
            <? endif; ?>
        <? endif; ?>
    </div>


</div>

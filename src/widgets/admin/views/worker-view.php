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

$class = 'g-brd-gray-light-v4';
$currentTask = null;
$isWorkingNow = (bool)$cmsUser->isWorkingNow;
$isCurrentTaskAvailable = true;

if ($isWorkingNow) {
    $currentTask = $cmsUser->getExecutorTasks()->statusInWork()->one();

    if ($currentTask && \Yii::$app->user->id != $cmsUser->id) {
        $isCurrentTaskAvailable = \Yii::$app->user->can("cms/admin-task/manage", ['model' => $currentTask]);
    }
}

$css = <<<CSS
.sx-worker-work-state {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
    margin-left: 6px;
    border-radius: 50%;
    vertical-align: middle;
    color: #fff;
    font-size: 7px;
    line-height: 1;
}
.sx-worker-work-state.is-task {
    background: #14b8a6;
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgba(20, 184, 166, 0.18);
}
.sx-worker-work-state.is-without-task {
    background: #f59e0b;
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgba(245, 158, 11, 0.18);
}
CSS;
$this->registerCss($css, [], 'sx-worker-work-state');

?>
<div class="d-flex flex-row sx-preview-card">

    <div class="my-auto">
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

    <div class="my-auto">

        <?php
        $options = \yii\helpers\ArrayHelper::merge([
            'style' => 'text-align: left; white-space: nowrap;',
            'class' => '',
            'href'  => '#',

            'data-toggle' => 'tooltip',
            'data-html'   => 'true',
            'data-pjax'   => '0',
        ], (array)$widget->tagNameOptions);
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
                <div style="color: gray; font-size: 12px; text-decoration: none; border-bottom: 0px;"><?php echo $cmsUser->post; ?></div>
            <?php endif; ?>


            <? if ($widget->append) : ?>
                <?php echo $widget->append; ?>
            <? endif; ?>
        <? endif; ?>
    </div>


</div>

<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/* @var $task \skeeks\cms\models\CmsTask */
/* @var $project \skeeks\cms\models\CmsProject */
/* @var $widget \skeeks\cms\widgets\admin\CmsTaskViewWidget */
$widget = $this->context;
$task = $widget->task;

$title = "";

$cmsImage = null;
$project = $task->cmsProject;
$company = $task->cmsCompany;
$client = $task->cmsUser;

$titleData = [];
$letter = $task->name;

if ($project && $project->cmsImage) {
    $cmsImage = $project->cmsImage;
} elseif ($company && $company->cmsImage) {
    $cmsImage = $company->cmsImage;
}

if ($project) {
    $titleData[] = "Проект: ".$project->name;
    $letter = $project->name;
}
if ($company) {
    $titleData[] = "Компания: ".$company->name;
    $letter = $company->name;
}

if ($client) {
    $titleData[] = "Клиент: ".$client->name;
    $letter = $client->name;
}

$actionData = \yii\helpers\Json::encode([
    "isOpenNewWindow" => true,
    "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(["/cms/admin-cms-task/view", "pk" => $task->id])->enableEmptyLayout()->enableNoActions()->url,
]);
$taskTitleOptions = $widget->isAction ? [
    'data-toggle' => 'tooltip',
    'data-html'   => 'true',
    'data-pjax'   => '0',
    'title'       => $task->asText,
    'href'        => \yii\helpers\Url::to(["/cms/admin-cms-task/view", "pk" => $task->id]),
    'onclick'     => new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
    ),
] : [
    'href' => '#',
];
$taskTitleOptions = \yii\helpers\ArrayHelper::merge($taskTitleOptions, $widget->tagNameOptions);
\yii\helpers\Html::addCssClass($taskTitleOptions, ['sx-main-info', 'sx-preview-card__title', 'sx-collection-cell__primary']);
?>
<div class="sx-task-wrapper sx-preview-card sx-preview-card--task">

    <div class="sx-task-info">
        <div class="img-wrapper sx-preview-card__media">

            <? if ($cmsImage) : ?>
                <? if ($widget->isAction) : ?>
                    <a class="sx-preview-card__media-link" href="<?= \yii\helpers\Url::to(["/cms/admin-cms-task/view", "pk" => $task->id]); ?>" data-pjax="0"
                    title="<?= implode("; ", $titleData); ?>" data-toggle="tooltip"
                    onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'
                    >
                <? endif; ?>

                <img src="<?= \Yii::$app->imaging->thumbnailUrlOnRequest($cmsImage ? $cmsImage->src : \skeeks\cms\helpers\Image::getCapSrc(),
                    new \skeeks\cms\components\imaging\filters\Thumbnail([
                        'h' => $widget->prviewImageSize,
                        'w' => $widget->prviewImageSize,
                        'm' => \Imagine\Image\ManipulatorInterface::THUMBNAIL_OUTBOUND,
                    ])); ?>" alt=""
                     class="sx-photo sx-img-size-<?= $widget->prviewImageSize; ?>"
                     title="<?= $title; ?>"
                     data-toggle="tooltip"
                     data-html="true"
                >
                <? if ($widget->isAction) : ?>
                    </a>
                <? endif; ?>

            <? else : ?>
                <div class="sx-no-photo sx-img-size-<?= $widget->prviewImageSize; ?>">
                    <? if ($widget->isAction) : ?>
                    <a class="sx-preview-card__media-link" href="<?= \yii\helpers\Url::to(["/cms/admin-cms-task/view", "pk" => $task->id]); ?>" data-pjax="0"
                       title="<?= implode("; ", $titleData); ?>" data-toggle="tooltip"
                       onclick="<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                       ); ?>"
                    >
                        <? endif; ?>
                        <?= \skeeks\cms\helpers\StringHelper::strtoupper(
                            \skeeks\cms\helpers\StringHelper::substr($letter, 0, 2)
                        ); ?>

                        <? if ($widget->isAction) : ?>
                    </a>
                <? endif; ?>
                </div>
            <? endif; ?>


        </div>

        <div class="sx-preview-card__content sx-collection-cell sx-collection-cell--stack">

            <?= \yii\helpers\Html::tag($widget->tagName, $task->asText, $taskTitleOptions); ?>


            <? if ($widget->isShowOnlyName === false) : ?>
                <? $worked = \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($task->schedules); ?>
                <div class="sx-employee sx-preview-card__meta sx-collection-cell__secondary <?= $worked > $task->plan_duration ? 'sx-preview-card__meta--danger' : ''; ?>">
                    <!--<span title="Создана: <? /*= \Yii::$app->formatter->asDatetime($task->created_at); */ ?>" data-toggle="tooltip"><? /*= \Yii::$app->formatter->asRelativeTime($task->created_at); */ ?></span>-->
                    

                    <? if ($task->plan_start_at) : ?>
                        <span title="Запланирована на это время" data-toggle="tooltip"><?php echo \Yii::$app->formatter->asDatetime($task->plan_start_at, "d MMMM y 'г'., HH:mm"); ?></span>
                        <? if ($task->plan_start_at < time()) : ?>
                            / <span class="sx-preview-card__meta--danger">Просрочена!</span>
                        <? endif; ?>
                    <? else: ?>
                    
                        <? if ($worked) : ?>
                            <span title="Отработано" data-toggle="tooltip">
                            <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($worked); ?>
                        </span> /
                        <? else : ?>
    
                        <? endif; ?>
    
                        <span title="Запланированное время" data-toggle="tooltip">
                            <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($task->plan_duration); ?>
                        </span>
    
                        <?= $worked > $task->plan_duration ? "Переработка!" : ""; ?>
                        
                    <? endif; ?>


                </div>

            <? endif; ?>
        </div>
    </div>

    <?php if($widget->isShowStatus) : ?>
        <div class="sx-task-status">
            <?php echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget([
                'task' => $widget->task,
                'isShort' => $widget->isStatusShort,
            ]); ?>
        </div>
    <?php endif; ?>



</div>



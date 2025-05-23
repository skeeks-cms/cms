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

$class = 'g-brd-gray-light-v4';
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
?>
<div class="sx-task-wrapper sx-preview-card">

    <div class="sx-task-info">
        <div class="img-wrapper">

            <? if ($cmsImage) : ?>
                <? if ($widget->isAction) : ?>
                    <a href="<?= \yii\helpers\Url::to(["/cms/admin-cms-task/view", "pk" => $task->id]); ?>" data-pjax="0" style="border: 0;"
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
                     class="sx-photo <?= $class; ?> sx-img-size-<?= $widget->prviewImageSize; ?>"
                     title="<?= $title; ?>"
                     data-toggle="tooltip"
                     data-html="true"
                >
                <? if ($widget->isAction) : ?>
                    </a>
                <? endif; ?>

            <? else : ?>
                <div class="sx-no-photo g-brd-gray-light-v4 sx-img-size-<?= $widget->prviewImageSize; ?>">
                    <? if ($widget->isAction) : ?>
                    <a href="<?= \yii\helpers\Url::to(["/cms/admin-cms-task/view", "pk" => $task->id]); ?>" data-pjax="0" style="border: 0;"
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

        <div>

            <? if ($widget->isAction) : ?>

                <?= \yii\helpers\Html::tag($widget->tagName, $task->asText, \yii\helpers\ArrayHelper::merge([
                    'data-toggle' => 'tooltip',
                    'data-html'   => 'true',
                    'data-pjax'   => '0',
                    'class'       => 'sx-main-info',
                    'title'       => $task->asText,
                    'href'        => \yii\helpers\Url::to(["/cms/admin-cms-task/view", "pk" => $task->id]),
                    "onclick"     => new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ),
                ], $widget->tagNameOptions)); ?>

            <? else: ?>
                <?= \yii\helpers\Html::tag($widget->tagName, $task->asText, \yii\helpers\ArrayHelper::merge([
                    'href'  => "#",
                    'class' => "sx-main-info",
                ], $widget->tagNameOptions)); ?>
            <? endif; ?>


            <? if ($widget->isShowOnlyName === false) : ?>
                <br/>
                <? $worked = \skeeks\cms\helpers\CmsScheduleHelper::durationBySchedules($task->schedules); ?>
                <div class="sx-employee" style="font-size: 12px; <?= $worked > $task->plan_duration ? "color: var(--color-red-pale);" : "color: var(--color-gray);"; ?>">
                    <!--<span title="Создана: <? /*= \Yii::$app->formatter->asDatetime($task->created_at); */ ?>" data-toggle="tooltip"><? /*= \Yii::$app->formatter->asRelativeTime($task->created_at); */ ?></span>-->
                    <? if ($worked) : ?>
                        <span title="Отработано" data-toggle="tooltip">
                        <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($worked); ?>
                    </span> /
                    <? else : ?>

                    <? endif; ?>

                    <span title="Запланированное время" data-toggle="tooltip">
                    <?= \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($task->plan_duration); ?>
                </span>

                    <?= $worked > $task->plan_duration ? "переработка!" : ""; ?>

                    <? if ($task->plan_start_at) : ?>
                        / <span title="Запланирована на это время" data-toggle="tooltip"><?php echo \Yii::$app->formatter->asDatetime($task->plan_start_at); ?></span>
                        <? if ($task->plan_start_at < time()) : ?>
                            / <span style="color: var(--color-red-pale);">Просрочена!</span>
                        <? endif; ?>
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



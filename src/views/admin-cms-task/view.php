<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsTask */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsTask;
use yii\helpers\Html;

$controller = $this->context;
$action = $controller->action;
$model = $action->model;

$status = \skeeks\cms\widgets\admin\CmsTaskBtnsWidget::widget([
    'task' => $model,
    'isPjax' => false
]);

$model->refresh();
$planStartAt = $model->plan_start_at;
$planStartEndAt = $model->executor_end_at ?: $model->plan_end_at;
$projectClients = $model->cmsProject ? $model->cmsProject->users : [];
if (!$planStartAt && $planStartEndAt && $model->plan_duration) {
    $planStartAt = max(0, (int)$planStartEndAt - (int)$model->plan_duration);
}
// The deadline: the planned end; an open task past it is overdue.
$deadline = $model->plan_end_at ? (int)$model->plan_end_at : null;
$isClosed = in_array($model->status, [CmsTask::STATUS_READY, CmsTask::STATUS_CANCELED], true);
$isOverdue = $deadline && !$isClosed && $deadline < time();

$quickAccessItems = [];
$makeQuickAccessActionUrl = function ($route, $id) {
    return (string) \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
        $route,
        'pk' => $id,
    ])->enableEmptyLayout()->enableNoActions()->url;
};
$makeQuickAccessImageUrl = function ($model) {
    if ($model && $model->cmsImage) {
        return (string) \Yii::$app->imaging->thumbnailUrlOnRequest($model->cmsImage->src, new \skeeks\cms\components\imaging\filters\Thumbnail([
            'w' => 80,
            'h' => 80,
            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
        ]), '', true);
    }

    return null;
};
$linkRelatedTaskUrl = \yii\helpers\Url::to([
    '/cms/admin-cms-task/link-related-task',
    'pk' => $model->id,
]);
$relatedTasks = [];
if ($model->parentCmsTask) {
    $relatedTasks[] = $model->parentCmsTask;
}
foreach ($model->childCmsTasks as $childTask) {
    $relatedTasks[] = $childTask;
}
usort($relatedTasks, function ($a, $b) {
    $aPlanEndAt = $a->plan_end_at ? (int) $a->plan_end_at : PHP_INT_MAX;
    $bPlanEndAt = $b->plan_end_at ? (int) $b->plan_end_at : PHP_INT_MAX;

    if ($aPlanEndAt == $bPlanEndAt) {
        return (int) $b->id <=> (int) $a->id;
    }

    return $aPlanEndAt <=> $bPlanEndAt;
});

$quickAccessCompany = $model->cmsCompany ?: ($model->cmsProject ? $model->cmsProject->cmsCompany : null);

if ($quickAccessCompany) {
    $quickAccessItems[] = [
        'type'   => 'companies',
        'id'     => (int) $quickAccessCompany->id,
        'name'   => (string) $quickAccessCompany->name,
        'url'    => \yii\helpers\Url::to(['/cms/admin-cms-company/view', 'pk' => $quickAccessCompany->id]),
        'action' => $makeQuickAccessActionUrl('/cms/admin-cms-company/view', $quickAccessCompany->id),
        'image'  => $makeQuickAccessImageUrl($quickAccessCompany),
    ];
}

if ($model->cmsProject) {
    $quickAccessItems[] = [
        'type'   => 'projects',
        'id'     => (int) $model->cmsProject->id,
        'name'   => (string) $model->cmsProject->name,
        'url'    => \yii\helpers\Url::to(['/cms/admin-cms-project/view', 'pk' => $model->cmsProject->id]),
        'action' => $makeQuickAccessActionUrl('/cms/admin-cms-project/view', $model->cmsProject->id),
        'image'  => $makeQuickAccessImageUrl($model->cmsProject),
    ];
}

if ($quickAccessItems) {
    $quickAccessItemsJson = \yii\helpers\Json::encode($quickAccessItems);
    $this->registerJs(<<<JS
(function(items) {
    var attempts = 0;
    var addItems = function() {
        attempts++;
        var windows = [window, window.parent, window.top, window.opener];

        for (var w = 0; w < windows.length; w++) {
            try {
                var target = windows[w];
                if (!target || !target.sx || !target.sx.Project || !target.sx.Project.quickAccessAdd) {
                    continue;
                }

                for (var i = 0; i < items.length; i++) {
                    target.sx.Project.quickAccessAdd(items[i]);
                }

                return true;
            } catch (e) {
            }
        }

        if (attempts < 10) {
            setTimeout(addItems, 300);
        }

        return false;
    };

    addItems();
})({$quickAccessItemsJson});
JS
);
}

$this->registerCss(<<<CSS
.sx-task-layout > * {
    min-width: 0;
}
/* The description and the properties are always one height: the row of the layout. */
html[data-sx-theme] .sx-task-layout.sx-detail-layout {
    align-items: stretch;
}
.sx-task-main-surface,
.sx-task-side {
    height: 100%;
}
/* The status buttons and the deadline always at the bottom of the description block. */
.sx-task-main-surface {
    display: flex;
    flex-direction: column;
}
.sx-task-main-surface > .sx-surface__body {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
}
.sx-task-description {
    display: flex;
    width: 100%;
    overflow: auto;
    flex-direction: column;
    gap: 1rem;
}
.sx-task-description-empty {
    color: var(--sx-color-text-muted);
}
.sx-task-main-surface .sx-files img {
    border-radius: var(--sx-radius-sm);
    max-width: 10rem;
    border: 1px solid var(--sx-color-border);
}
.sx-task-main-surface .sx-files .sx-file-item {
    margin-bottom: 0.25rem;
}
.sx-task-main-surface .sx-files .sx-title {
    color: var(--sx-color-text-muted);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}
.sx-task-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    margin-top: auto;
    padding-top: 1rem;
}
/* The deadline over the status buttons: overdue in the danger colour. */
.sx-task-deadline {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--sx-color-text-muted);
}
.sx-task-deadline--overdue {
    color: var(--sx-color-danger, #d64545);
    font-weight: 600;
}
.sx-task-side .sx-properties {
    padding: 0;
}
.sx-task-related-list {
    display: flex;
    flex-direction: column;
}
.sx-task-related-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 6px 0;
    border-top: 1px solid var(--sx-color-border);
}
.sx-task-related-row:first-child {
    border-top: 0;
}
.sx-task-related-row__task {
    flex: 1 1 auto;
    min-width: 0;
}
.sx-task-related-row__meta {
    flex: 0 0 auto;
    color: var(--sx-color-text-muted);
    font-size: 0.8125rem;
    white-space: nowrap;
}
.sx-task-link-scope {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 14px;
    margin-bottom: 10px;
}
.sx-task-link-scope label {
    margin: 0;
    font-weight: normal;
}
@media (max-width: 575px) {
    .sx-task-related-row {
        flex-wrap: wrap;
    }
}
CSS
);
\skeeks\cms\assets\LinkActvationAsset::register($this);
$this->registerJs(<<<JS
new sx.classes.LinkActivation('.sx-task-description');
$("body").on("click", ".sx-task-related-unlink", function(e) {
    e.preventDefault();

    if (!confirm("Отвязать задачу?")) {
        return false;
    }

    var data = {};
    if (window.yii) {
        data[yii.getCsrfParam()] = yii.getCsrfToken();
    }

    $.ajax({
        "url": $(this).data("url"),
        "type": "post",
        "data": data,
        "success": function() {
            window.location.reload();
        },
        "error": function() {
            alert("Не удалось отвязать задачу.");
        }
    });

    return false;
});
$("body").on("click", ".sx-task-related-link-btn", function(e) {
    e.preventDefault();

    var taskId = $("#sx-task-related-link-select").val();
    if (!taskId) {
        alert("Выберите задачу.");
        return false;
    }

    var data = {
        "related_task_id": taskId
    };
    if (window.yii) {
        data[yii.getCsrfParam()] = yii.getCsrfToken();
    }

    $.ajax({
        "url": $(this).data("url"),
        "type": "post",
        "data": data,
        "success": function(response) {
            if (response && response.success === false) {
                alert(response.message || "Не удалось привязать задачу.");
                return;
            }

            window.location.reload();
        },
        "error": function() {
            alert("Не удалось привязать задачу.");
        }
    });

    return false;
});
// Another scope of the search: the chosen task of the old one is dropped.
$("body").on("change", "[name=sx-task-link-scope]", function() {
    $("#sx-task-related-link-select").val(null).trigger("change");
});
JS
);
// «Перенести срок» — the edit window, for who may edit the task.
$canUpdate = $controller instanceof \skeeks\cms\backend\BackendController && ($update = $controller->createAction('update'))
    && (!$update instanceof \skeeks\cms\IHasPermissions || $update->isAllow);
?>

<div class="sx-task-content sx-surface-stack">
<?= \skeeks\cms\source\RecordOriginWidget::widget(['model' => $model]) ?>
<div class="sx-task-layout sx-detail-layout sx-detail-layout--main-first">
    <div class="sx-task-layout__main sx-detail-layout__main">
        <?php BackendSurfaceWidget::begin([
            'raised'     => true,
            'responsive' => true,
            'options'    => ['class' => 'sx-task-main-surface'],
        ]); ?>
            <div class="sx-task-description">
                <?php if ($model->description) : ?>
                    <div><?php echo \skeeks\cms\helpers\TaskContentHelper::render($model->description); ?></div>
                <?php else : ?>
                    <div class="sx-task-description-empty">Описания нет</div>
                <?php endif; ?>

                <?php if ($model->files) : ?>
                    <?php
                    $files = $model->files;
                    $images = [];
                    foreach ($files as $key => $file) {
                        if ($file->isImage()) {
                            $images[] = $file;
                            unset($files[$key]);
                        }
                    }
                    ?>
                    <div class="sx-files">
                        <?php if ($images) : ?>
                            <div class="sx-title">Приложенные изображения</div>
                            <div>
                                <?php foreach ($images as $file) : ?>
                                    <a href="<?php echo $file->src; ?>" target="_blank" data-pjax="0">
                                        <img src="<?php echo $file->src; ?>"/>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($files) : ?>
                            <div class="sx-title">Приложенные файлы</div>
                            <div>
                                <?php foreach ($files as $file) : ?>
                                <div class="sx-file-item">
                                    <a href="<?php echo $file->src; ?>" download="<?php echo Html::encode($file->original_name); ?>" target="_blank" data-pjax="0"><?php echo Html::encode($file->original_name); ?></a>
                                    <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-storage-files/download', 'pk' => $file->id]); ?>" target="_blank" data-pjax="0" class="btn btn-xs btn-default">скачать (<?php echo \Yii::$app->formatter->asShortSize($file->size); ?>)</a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sx-task-actions">
                <?php echo $status; ?>
                <?php if ($deadline) : ?>
                    <span class="sx-task-deadline<?php echo $isOverdue ? ' sx-task-deadline--overdue' : ''; ?>"
                          title="Планируемое завершение">
                        <?php echo \skeeks\cms\backend\helpers\BackendIcon::render('clock', ['size' => 14]); ?>
                        <?php echo $isOverdue ? 'Просрочена: ' : 'Срок: '; ?><?php echo \Yii::$app->formatter->asDatetime($deadline, 'php:d.m.Y H:i'); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php BackendSurfaceWidget::end(); ?>
    </div>
    <div class="sx-task-layout__aside sx-detail-layout__aside">
        <?php BackendSurfaceWidget::begin([
            'raised'      => true,
            'options'     => ['class' => 'sx-task-side'],
            'bodyOptions' => ['class' => 'sx-properties-wrapper sx-columns-1'],
        ]); ?>
            <?php // Only filled properties: an empty «—» row is not shown. ?>
            <ul class="sx-properties">
                <li>
                    <span class="sx-properties--name">Статус</span>
                    <span class="sx-properties--value">
                        <?php echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $model]); ?>
                    </span>
                </li>
                <?php if ($model->executor) : ?>
                <li>
                    <span class="sx-properties--name">Исполнитель</span>
                    <span class="sx-properties--value">
                        <?php echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget(['user' => $model->executor, 'isSmall' => true]); ?>
                    </span>
                </li>
                <?php endif; ?>
                <?php if ($model->createdBy && (int)$model->created_by !== (int)$model->executor_id) : ?>
                <li>
                    <span class="sx-properties--name">Поставил</span>
                    <span class="sx-properties--value">
                        <?php echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget(['user' => $model->createdBy, 'isSmall' => true]); ?>
                    </span>
                </li>
                <?php endif; ?>
                <?php if ($model->cmsLead) : ?>
                <li>
                    <span class="sx-properties--name">Сделка</span>
                    <span class="sx-properties--value">
                        <?php echo \skeeks\cms\backend\widgets\BackendEntityLink::widget([
                            'controllerId' => '/cms/admin-cms-lead',
                            'action'       => 'view',
                            'modelId'      => $model->cmsLead->id,
                            'label'        => $model->cmsLead->displayName,
                            'options'      => [
                                'class'      => 'sx-preview-card__related',
                                'aria-label' => (string)$model->cmsLead->displayName,
                            ],
                        ]); ?>
                    </span>
                </li>
                <?php endif; ?>
                <?php if ($model->cms_company_id && $model->cmsCompany) : ?>
                <li>
                    <span class="sx-properties--name">Компания</span>
                    <span class="sx-properties--value">
                        <?php echo \skeeks\cms\backend\widgets\BackendEntityLink::widget([
                            'controllerId' => '/cms/admin-cms-company',
                            'modelId'      => $model->cmsCompany->id,
                            'label'        => $model->cmsCompany->name,
                            'options'      => [
                                'class'      => 'sx-preview-card__related',
                                'aria-label' => (string)$model->cmsCompany->name,
                            ],
                        ]); ?>
                    </span>
                </li>
                <?php endif; ?>
                <?php if ($model->cms_user_id && $model->cmsUser && !$model->cmsUser->is_worker) : ?>
                <li>
                    <span class="sx-properties--name">Клиент</span>
                    <span class="sx-properties--value">
                        <?php echo \skeeks\cms\backend\widgets\BackendEntityLink::widget([
                            'controllerId' => '/cms/admin-user',
                            'modelId'      => $model->cmsUser->id,
                            'label'        => $model->cmsUser->shortDisplayNameWithAlias,
                            'options'      => ['class' => 'sx-preview-card__related'],
                        ]); ?>
                    </span>
                </li>
                <?php endif; ?>
                <?php if ($model->hasAttribute('cms_deal_id') && $model->cms_deal_id && $model->cmsDeal) : ?>
                <li>
                    <span class="sx-properties--name">Услуга</span>
                    <span class="sx-properties--value">
                        <?php echo \skeeks\cms\backend\widgets\BackendEntityLink::widget([
                            'controllerId' => '/cms/admin-cms-deal',
                            'modelId'      => $model->cmsDeal->id,
                            'label'        => $model->cmsDeal->name,
                            'options'      => [
                                'class'      => 'sx-preview-card__related',
                                'aria-label' => (string)$model->cmsDeal->name,
                            ],
                        ]); ?>
                        <?php if ($resource = $model->dealResource) : ?>
                            <div class="sx-text-muted"><?= Html::encode($resource->title); ?></div>
                        <?php endif; ?>
                        <?php if ($model->is_internal) : ?>
                            <div class="sx-text-muted">Внутренняя: клиент не видит</div>
                        <?php endif; ?>
                    </span>
                </li>
                <?php
                $dealClients = $model->is_internal ? [] : \Yii::$app->dealAccess
                    ->usersQuery($model->cmsDeal, \skeeks\cms\deal\DealAccess::AREA_TASKS)
                    ->limit(20)
                    ->all();
                ?>
                <?php if ($dealClients) : ?>
                <li>
                    <span class="sx-properties--name">
                        Видят клиенты
                        <i class="far fa-question-circle sx-hint-icon" data-toggle="tooltip"
                           title="Задачи этой услуги открыты этим людям клиента. Учитывайте это при заполнении описания и комментариев."></i>
                    </span>
                    <span class="sx-properties--value">
                        <?php foreach ($dealClients as $index => $client) : ?>
                            <?php if ($index) : ?><br><?php endif; ?>
                            <?php echo \skeeks\cms\backend\widgets\BackendEntityLink::widget([
                                'controllerId' => '/cms/admin-user',
                                'modelId'      => $client->id,
                                'label'        => $client->shortDisplayNameWithAlias,
                                'options'      => ['class' => 'sx-preview-card__related'],
                            ]); ?>
                        <?php endforeach; ?>
                    </span>
                </li>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($model->cms_project_id && $model->cmsProject) : ?>
                <li>
                    <span class="sx-properties--name">Проект</span>
                    <span class="sx-properties--value">
                        <?php echo \skeeks\cms\backend\widgets\BackendEntityLink::widget([
                            'controllerId' => '/cms/admin-cms-project',
                            'modelId'      => $model->cmsProject->id,
                            'label'        => $model->cmsProject->name,
                            'options'      => [
                                'class'      => 'sx-preview-card__related',
                                'aria-label' => (string)$model->cmsProject->name,
                            ],
                        ]); ?>
                    </span>
                </li>
                <?php endif; ?>
                <?php if ($projectClients) : ?>
                    <?php
                    $clientVisibilityHint = 'Эта задача доступна перечисленным клиентам, потому что они добавлены в проект «'.
                        $model->cmsProject->name.'». Учитывайте это при заполнении описания и комментариев.';
                    ?>
                    <li>
                        <span class="sx-properties--name">
                            Клиенты
                            <i class="far fa-question-circle sx-hint-icon" data-toggle="tooltip"
                               title="<?php echo Html::encode($clientVisibilityHint); ?>"
                               aria-label="<?php echo Html::encode($clientVisibilityHint); ?>"></i>
                        </span>
                        <span class="sx-properties--value">
                            <?php foreach ($projectClients as $index => $client) : ?>
                                <?php if ($index) : ?><br><?php endif; ?>
                                <?php echo \skeeks\cms\backend\widgets\BackendEntityLink::widget([
                                    'controllerId' => '/cms/admin-user',
                                    'modelId'      => $client->id,
                                    'label'        => $client->shortDisplayNameWithAlias,
                                    'options'      => [
                                        'class'      => 'sx-preview-card__related',
                                        'aria-label' => (string)$client->shortDisplayNameWithAlias,
                                    ],
                                ]); ?>
                            <?php endforeach; ?>
                        </span>
                    </li>
                <?php endif; ?>
                <?php if ($planStartAt) : ?>
                <li>
                    <span class="sx-properties--name">Начало по плану</span>
                    <span class="sx-properties--value"><?php echo \Yii::$app->formatter->asDatetime($planStartAt); ?></span>
                </li>
                <?php endif; ?>
                <?php if ($model->plan_duration) : ?>
                <li>
                    <span class="sx-properties--name">Длительность по плану</span>
                    <span class="sx-properties--value"><?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($model->plan_duration); ?></span>
                </li>
                <?php endif; ?>
                <?php if ($model->schedules) : ?>
                <li>
                    <span class="sx-properties--name">Отработано</span>
                    <span class="sx-properties--value"><?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsTextBySchedules($model->schedules); ?></span>
                </li>
                <?php endif; ?>
                <?php if ($model->executor_end_at) : ?>
                <li>
                    <span class="sx-properties--name">
                        Время завершения <i class="far fa-question-circle sx-hint-icon" data-toggle="tooltip" title="Примерное время завершения задачи. Учитывается рабочий график исполнителя и загруженность по другим задачам."></i>
                    </span>
                    <span class="sx-properties--value"><?php echo \Yii::$app->formatter->asDatetime($model->executor_end_at); ?></span>
                </li>
                <?php endif; ?>
            </ul>
        <?php BackendSurfaceWidget::end(); ?>
    </div>
</div>

<?php if ($relatedTasks) : ?>
    <?php // Only when there are: the parent and the child tasks, compact; «Привязать задачу» is in the block of actions. ?>
    <?php BackendSurfaceWidget::begin([
        'title'      => 'Связанные задачи',
        'titleTag'   => 'h3',
        'raised'     => true,
        'responsive' => true,
        'options'    => ['class' => 'sx-task-related'],
    ]); ?>
        <div class="sx-task-related-list">
            <?php foreach ($relatedTasks as $relatedTask) : ?>
                <?php
                $unlinkTaskId = ((int)$model->parent_cms_task_id === (int)$relatedTask->id) ? $model->id : $relatedTask->id;
                $unlinkRelatedTaskUrl = \yii\helpers\Url::to(['/cms/admin-cms-task/unlink-related-task', 'pk' => $unlinkTaskId]);
                ?>
                <div class="sx-task-related-row">
                    <div class="sx-task-related-row__task">
                        <?php echo \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $relatedTask]); ?>
                    </div>
                    <?php if ($relatedTask->plan_end_at) : ?>
                        <span class="sx-task-related-row__meta"><?php echo \Yii::$app->formatter->asDatetime((int)$relatedTask->plan_end_at, 'php:d.m.Y H:i'); ?></span>
                    <?php endif; ?>
                    <?php echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $relatedTask, 'isShort' => true]); ?>
                    <?php if ($relatedTask->executor) : ?>
                        <?php echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget(['user' => $relatedTask->executor, 'isSmall' => true]); ?>
                    <?php endif; ?>
                    <a href="#" class="btn btn-xs btn-default sx-task-related-unlink" data-url="<?php echo $unlinkRelatedTaskUrl; ?>" title="Отвязать задачу" aria-label="Отвязать задачу" data-toggle="tooltip">
                        <i class="fas fa-unlink"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>
<?php endif; ?>

<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id'      => 'sx-comments',
    'options' => ['class' => 'sx-surface-stack sx-activity-thread'],
]); ?>
    <?php // The block of the card as in a company: the comment in one line, the result pinned (CrmCardActionsWidget::taskConfig()). ?>
    <?= \skeeks\cms\widgets\admin\CrmCardActionsWidget::widget(\skeeks\cms\widgets\admin\CrmCardActionsWidget::taskConfig($model, $canUpdate)); ?>
    <?php // The result (pinned comments) once, right under the block; the feed below skips it. ?>
    <?php $taskResultQuery = $model->getLogs()->activity()->pinned(); ?>
    <?php if ($taskResultQuery->count()) : ?>
        <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
            'query'                => $taskResultQuery,
            'is_show_model'        => false,
            'is_show_pin_controls' => true,
        ]); ?>
    <?php endif; ?>
    <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
        'query'                => $model->getLogs()->activity()->notPinned(),
        'is_show_model'        => false,
        'is_show_pin_controls' => true,
    ]); ?>
<?php $pjax::end(); ?>
</div>

<?php // «Привязать задачу»: search by the deal, the company of the task or everywhere (the tasks the employee sees). ?>
<?php
$scopes = array_filter([
    'lead'    => $model->cms_lead_id ? 'По сделке' : null,
    'company' => $model->cms_company_id ? 'По компании' : null,
    'all'     => 'Все задачи',
]);
$scopeDefault = (string)array_key_first($scopes);
$linkModal = \yii\bootstrap\Modal::begin([
    'header'       => 'Привязать задачу',
    'id'           => 'sx-task-link-modal',
    'size'         => \yii\bootstrap\Modal::SIZE_DEFAULT,
    'toggleButton' => false,
]);
?>
    <?php if (count($scopes) > 1) : ?>
        <div class="sx-task-link-scope" role="radiogroup" aria-label="Где искать">
            <?php foreach ($scopes as $scope => $label) : ?>
                <label><?= Html::radio('sx-task-link-scope', $scope === $scopeDefault, ['value' => $scope]); ?> <?= Html::encode($label); ?></label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="form-group">
        <?php echo \skeeks\cms\widgets\AjaxSelectModel::widget([
            'name' => 'related_task_id',
            'modelClass' => CmsTask::class,
            'modelShowAttribute' => 'name',
            'options' => ['id' => 'sx-task-related-link-select'],
            'placeholder' => 'Найдите задачу по названию или номеру',
            'searchQuery' => function ($word = '') use ($model) {
                $table = CmsTask::tableName();
                $query = CmsTask::find()->forManager()->andWhere(['!=', $table.'.id', $model->id]);
                $scope = (string)\Yii::$app->request->get('scope');
                if ($scope === 'lead' && $model->cms_lead_id) {
                    $query->andWhere([$table.'.cms_lead_id' => (int)$model->cms_lead_id]);
                } elseif ($scope === 'company' && $model->cms_company_id) {
                    $query->andWhere([$table.'.cms_company_id' => (int)$model->cms_company_id]);
                }
                if ($word) {
                    $query->search($word);
                }

                return $query->orderBy([$table.'.created_at' => SORT_DESC]);
            },
            'pluginOptions' => [
                'dropdownParent' => new \yii\web\JsExpression('$("#sx-task-link-modal")'),
                'ajax' => [
                    'data' => new \yii\web\JsExpression('function(params) {
                        return {q: params.term, scope: $("[name=sx-task-link-scope]:checked").val() || '.\yii\helpers\Json::encode($scopeDefault).'};
                    }'),
                ],
            ],
        ]); ?>
    </div>
    <button type="button" class="sx-button sx-button--primary sx-task-related-link-btn" data-url="<?php echo $linkRelatedTaskUrl; ?>">
        <i class="fas fa-link"></i> Привязать
    </button>
<?php \yii\bootstrap\Modal::end(); ?>

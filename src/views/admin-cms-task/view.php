<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsTask */
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
if (!$planStartAt && $planStartEndAt && $model->plan_duration) {
    $planStartAt = max(0, (int)$planStartEndAt - (int)$model->plan_duration);
}

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
$createRelatedTaskUrl = (string) \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
    '/cms/admin-cms-task/create',
    'parent_cms_task_id' => $model->id,
])->enableEmptyLayout()->enableNoActions()->url;
$createRelatedTaskActionData = \yii\helpers\Json::encode([
    "isOpenNewWindow" => true,
    "url"             => $createRelatedTaskUrl,
]);
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
.sx-task-description {
    height: 100%;
    display: flex;
    width: 100%;
    
    overflow: auto;
    /*align-items: center;
    justify-content: center;
    */
    flex-direction: column;
    padding: 1rem;
    border: 1px solid #ebebeb;
    border-radius: var(--border-radius);
}
.sx-task-description-empty {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-gray);
}

.sx-block-task div {
    margin-bottom: 1rem;
}
.sx-block-task div:last-child {
    margin-bottom: 0;
}


.sx-block-task .sx-files img {
    border-radius: var(--border-radius);
    max-width: 10rem;
    border: 1px solid var(--color-light-gray);
}

.sx-block-task .sx-files .sx-file-item {
    margin-bottom: 0.25rem;
}
.sx-block-task .sx-files .sx-title {
    color: var(--color-gray);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
    margin-top: 1rem;
}
.sx-block-task .sx-files .sx-files-block {
    background: var(--bg-color-light);
    padding: 1rem;
    border-radius: var(--border-radius);
}
.sx-task-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1rem;
}
.sx-task-related-create {
    white-space: nowrap;
}
.sx-task-related-title {
    color: var(--color-gray);
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}
.sx-task-related-table {
    margin-bottom: 0;
}
.sx-task-related-table th {
    font-weight: normal;
    color: var(--color-gray);
    border-top: 0;
}
.sx-task-related-table td {
    vertical-align: middle;
}
.sx-task-related-actions {
    width: 1%;
    white-space: nowrap;
    text-align: right;
}
.sx-task-related-link {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.sx-task-related-link .select2-container {
    flex: 1 1 auto;
}
.sx-task-related-link .btn {
    white-space: nowrap;
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
JS
);
?>

<div class="row">
    <div class="col-12 col-sm-8">
        <div class="sx-block sx-block-task" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
            <div class="sx-task-description" style="">
                <? if ($model->description) : ?>
                    <?php echo $model->description; ?>
                <? else : ?>
                    <div class="sx-task-description-empty">Нет описания задачи...</div>
                <? endif; ?>

                <?php if ($model->files) : ?>

                    <?
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
                            <div class="sx-title">Приложенные изображения:</div>
                            <div class="sx-files-block">
                                <? foreach ($images as $key => $file) : ?>
                                    <?
                                    /**
                                     * @var $file \skeeks\cms\models\CmsStorageFile
                                     */
                                    ?>
                                    <a href="<?php echo $file->src; ?>" target="_blank" data-pjax="0">
                                        <img src="<?php echo $file->src; ?>"/>
                                    </a>
                                <? endforeach; ?>
                            </div>
                        <?php endif; ?>


                        <?php if ($files) : ?>
                            <div class="sx-title">Приложенные файлы:</div>
                            <div class="sx-files-block">
                                <? foreach ($files as $key => $file) : ?>
                                <div class="sx-file-item">
                                    <a href="<?php echo $file->src; ?>" download="<?php echo $file->original_name; ?>" target="_blank" data-pjax="0"><?php echo $file->original_name; ?></a>
                                    <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-storage-files/download', 'pk' => $file->id]); ?>" target="_blank" data-pjax="0" class="btn btn-xs btn-default">скачать (<?php echo \Yii::$app->formatter->asShortSize($file->size); ?>)</a>
                                </div>
                                <? endforeach; ?>
                            </div>
                        <?php endif; ?>


                    </div>
                <?php endif; ?>

            </div>
            
            <div class="sx-task-actions">
                <?php echo $status; ?>

                <a href="<?php echo $createRelatedTaskUrl; ?>" class="btn btn-default sx-task-related-create" data-pjax="0" onclick='new sx.classes.backend.widgets.Action(<?php echo $createRelatedTaskActionData; ?>).go(); return false;'>
                    <i class="fas fa-plus"></i> Связанная задача
                </a>
            </div>

        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="sx-properties-wrapper sx-columns-1 sx-block" style="height: 100%;">
            <ul class="sx-properties" style="padding: 10px;">
                <li>
            <span class="sx-properties--name">
                Статус 
            </span>
                    <span class="sx-properties--value">
                <?php
                if ($model->createdBy) {
                    echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget([
                        'task'    => $model,
                    ]);
                } else {
                    echo "—";
                }
                ?>
            </span>
                </li>
                <li>
            <span class="sx-properties--name">
                Поставил 
            </span>
                    <span class="sx-properties--value">
                <?php
                if ($model->createdBy) {
                    echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                        'user'    => $model->createdBy,
                        'isSmall' => true,
                    ]);
                } else {
                    echo "—";
                }
                ?>
            </span>
                </li>
                <li>
            <span class="sx-properties--name">
                Исполнитель
            </span>
                    <span class="sx-properties--value">
                <?php
                if ($model->executor) {
                    echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                        'user'    => $model->executor,
                        'isSmall' => true,
                    ]);
                } else {
                    echo "—";
                }
                ?>
            </span>
                </li>
                <li>
            <span class="sx-properties--name">
                Длительност по плану
            </span>
                    <span class="sx-properties--value">
                <?php echo \skeeks\cms\helpers\CmsScheduleHelper::durationAsText($model->plan_duration); ?>
            </span>
                </li>
                <li>
                    <span class="sx-properties--name">
                        Отработано
                    </span>
                    <span class="sx-properties--value">
                <?php echo $model->schedules ? \skeeks\cms\helpers\CmsScheduleHelper::durationAsTextBySchedules($model->schedules) : "—"; ?>
            </span>
                </li>

                <li>
                    <span class="sx-properties--name">
                        Начало по плану
                    </span>
                    <span class="sx-properties--value">
                        <?php echo $planStartAt ? \Yii::$app->formatter->asDatetime($planStartAt) : "—"; ?>
                    </span>
                </li>

                <?php if($model->executor_end_at) : ?>
                    <li>
                    <span class="sx-properties--name">
                        Время завершения <i class="far fa-question-circle" style="margin-left: 5px; color: silver;" data-toggle="tooltip" title="" data-original-title="Примерное время завершения задачи. Учитывается рабочий график исполнителя и загруженность по другим задачам."></i>
                    </span>
                            <span class="sx-properties--value" >
                        <?php echo \Yii::$app->formatter->asDatetime($model->executor_end_at); ?>
                    </span>
                        </li>
                <?php endif; ?>

                <li>
                    <span class="sx-properties--name">
                        Компания
                    </span>
                    <span class="sx-properties--value">
                        <?php if ($model->cms_company_id) : ?>
                            <?php $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                'controllerId'            => '/cms/admin-cms-company',
                                'modelId'                 => $model->cmsCompany->id,
                                'isRunFirstActionOnClick' => true,
                                'options'                 => [
                                    'class' => 'sx-dashed',
                                    'style' => 'cursor: pointer; border-bottom: 1px dashed;',
                                ],
                            ]); ?>
                            <?php echo $model->cmsCompany->name; ?>
                            <?php $widget::end(); ?>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </span>
                </li>
                <li>
                    <span class="sx-properties--name">
                        Проект
                    </span>
                    <span class="sx-properties--value">
                        <?php if ($model->cms_project_id) : ?>
                            <?php $widget = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                'controllerId'            => '/cms/admin-cms-project',
                                'modelId'                 => $model->cmsProject->id,
                                'isRunFirstActionOnClick' => true,
                                'options'                 => [
                                    'class' => 'sx-dashed',
                                    'style' => 'cursor: pointer; border-bottom: 1px dashed;',
                                ],
                            ]); ?>
                            <?php echo $model->cmsProject->name; ?>
                            <?php $widget::end(); ?>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 1rem;">
    <div class="col-12">
        <div class="sx-block sx-task-related">
            <div class="sx-task-related-title">Связанные задачи</div>
            <div class="sx-task-related-link">
                <?php echo \skeeks\cms\widgets\AjaxSelectModel::widget([
                    'name' => 'related_task_id',
                    'modelClass' => \skeeks\cms\models\CmsTask::class,
                    'modelShowAttribute' => 'name',
                    'options' => [
                        'id' => 'sx-task-related-link-select',
                    ],
                    'placeholder' => 'Выбрать существующую задачу',
                    'searchQuery' => function ($word = '') use ($model) {
                        $query = \skeeks\cms\models\CmsTask::find()
                            ->forManager()
                            ->andWhere(['!=', \skeeks\cms\models\CmsTask::tableName().'.id', $model->id]);

                        if ($word) {
                            $query->search($word);
                        }

                        return $query->orderBy([\skeeks\cms\models\CmsTask::tableName().'.created_at' => SORT_DESC]);
                    },
                ]); ?>
                <button type="button" class="btn btn-default sx-task-related-link-btn" data-url="<?php echo $linkRelatedTaskUrl; ?>">
                    <i class="fas fa-link"></i> Привязать
                </button>
            </div>

            <?php if ($relatedTasks) : ?>
                <div class="table-responsive">
                    <table class="table table-hover sx-task-related-table">
                        <thead>
                        <tr>
                            <th>Задача</th>
                            <th>Планируемое завершение</th>
                            <th>Отработанное время</th>
                            <th>Статус</th>
                            <th>Исполнитель</th>
                            <th class="sx-task-related-actions"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($relatedTasks as $relatedTask) : ?>
                            <?php
                                $unlinkTaskId = ((int)$model->parent_cms_task_id === (int)$relatedTask->id) ? $model->id : $relatedTask->id;
                                $unlinkRelatedTaskUrl = \yii\helpers\Url::to([
                                    '/cms/admin-cms-task/unlink-related-task',
                                    'pk' => $unlinkTaskId,
                                ]);
                            ?>
                            <tr>
                                <td>
                                    <?php echo \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $relatedTask]); ?>
                                </td>
                                <td>
                                    <?php echo $relatedTask->plan_end_at ? \Yii::$app->formatter->asDatetime((int) $relatedTask->plan_end_at, "php:d.m.Y H:i") : " - "; ?>
                                </td>
                                <td>
                                    <?php echo $relatedTask->schedules ? \skeeks\cms\helpers\CmsScheduleHelper::durationAsTextBySchedules($relatedTask->schedules) : " - "; ?>
                                </td>
                                <td>
                                    <?php echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $relatedTask, 'isShort' => true]); ?>
                                </td>
                                <td>
                                    <?php echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget(['user' => $relatedTask->executor, 'isSmall' => true]); ?>
                                </td>
                                <td class="sx-task-related-actions">
                                    <a href="#" class="btn btn-xs btn-default sx-task-related-unlink" data-url="<?php echo $unlinkRelatedTaskUrl; ?>" title="Отвязать задачу" data-toggle="tooltip">
                                        <i class="fas fa-unlink"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id' => 'sx-comments',
]); ?>

    <div class="row" style="margin-top: 1rem;">
        <div class="col-12">
            <div class="sx-block">
                <?php echo \skeeks\cms\widgets\admin\CmsCommentWidget::widget([
                    'model' => $model,
                ]); ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                'query'         => $model->getLogs()->comments(),
                'is_show_model' => false,
            ]); ?>
        </div>
    </div>

<?php $pjax::end(); ?>

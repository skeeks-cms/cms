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

CSS
);
\skeeks\cms\assets\LinkActvationAsset::register($this);
$this->registerJs(<<<JS
new sx.classes.LinkActivation('.sx-task-description');
JS
);
?>

<div class="row">
    <div class="col-8">
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
                                    <a href="<?php echo $file->src; ?>" target="_blank" data-pjax="0"><?php echo $file->original_name; ?></a>
                                    <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-storage-files/download', 'pk' => $file->id]); ?>" target="_blank" data-pjax="0" class="btn btn-xs btn-default">скачать (<?php echo \Yii::$app->formatter->asShortSize($file->size); ?>)</a>
                                </div>
                                <? endforeach; ?>
                            </div>
                        <?php endif; ?>


                    </div>
                <?php endif; ?>

            </div>
            
            <?php echo $status; ?>
            
        </div>
    </div>
    <div class="col-4">
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
                        Начало по плану
                    </span>
                    <span class="sx-properties--value">
                        <?php echo $model->plan_start_at ? \Yii::$app->formatter->asDatetime($model->plan_start_at) : "—"; ?>
                    </span>
                </li>

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
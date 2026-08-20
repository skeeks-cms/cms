<?php
/**
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsTask
 * @var $comment \skeeks\cms\models\CmsLog
 */

use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\widgets\admin\CmsCommentWidget;
use skeeks\cms\widgets\admin\CmsLogListWidget;
use skeeks\cms\widgets\admin\CmsTaskStatusWidget;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use yii\helpers\Html;

$worked = $model->schedules ? CmsScheduleHelper::durationAsTextBySchedules($model->schedules) : '—';
$planStartAt = $model->plan_start_at;
$planEndAt = $model->executor_end_at ?: $model->plan_end_at;
$showWorkTime = $model->cmsProject && $model->cmsProject->is_work_time_visible_for_clients;
if (!$planStartAt && $planEndAt && $model->plan_duration) {
    $planStartAt = max(0, (int)$planEndAt - (int)$model->plan_duration);
}

$taskResultQuery = $model->getLogs()->comments()->results();

\skeeks\cms\assets\CmsUpaSupportAsset::register($this);
?>

<div class="sx-upa-task-content">
    <div class="sx-upa-task-layout">
    <div class="sx-upa-task-layout__main">
        <div class="sx-surface sx-surface--padded sx-upa-task-main">
            <div class="sx-upa-task-description">
                <?php if ($model->description) : ?>
                    <?php echo $model->description; ?>
                <?php else : ?>
                    <div class="sx-upa-task-description-empty">Нет описания задачи...</div>
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
                    <div class="sx-upa-task-files">
                        <?php if ($images) : ?>
                            <div class="sx-upa-task-files-title">Приложенные изображения</div>
                            <?php foreach ($images as $file) : ?>
                                <a href="<?php echo $file->src; ?>" target="_blank" data-pjax="0">
                                    <img src="<?php echo $file->src; ?>" alt="">
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($files) : ?>
                            <div class="sx-upa-task-files-title">Приложенные файлы</div>
                            <?php foreach ($files as $file) : ?>
                                <div>
                                    <a href="<?php echo $file->src; ?>" target="_blank" data-pjax="0"><?php echo Html::encode($file->original_name); ?></a>
                                    <small><?php echo \Yii::$app->formatter->asShortSize($file->size); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="sx-upa-task-layout__aside">
        <div class="sx-properties-wrapper sx-columns-1 sx-surface sx-surface--padded sx-upa-task-side">
            <ul class="sx-properties">
                <li>
                    <span class="sx-properties--name">Статус</span>
                    <span class="sx-properties--value"><?php echo CmsTaskStatusWidget::widget(['task' => $model]); ?></span>
                </li>
                <li>
                    <span class="sx-properties--name">Поставил</span>
                    <span class="sx-properties--value">
                        <?php echo $model->createdBy ? CmsWorkerViewWidget::widget(['user' => $model->createdBy, 'isSmall' => true]) : '—'; ?>
                    </span>
                </li>
                <li>
                    <span class="sx-properties--name">Исполнитель</span>
                    <span class="sx-properties--value">
                        <?php echo $model->executor ? CmsWorkerViewWidget::widget(['user' => $model->executor, 'isSmall' => true]) : '—'; ?>
                    </span>
                </li>
                <?php if ($showWorkTime) : ?>
                <li>
                    <span class="sx-properties--name">Длительность по плану</span>
                    <span class="sx-properties--value"><?php echo CmsScheduleHelper::durationAsText($model->plan_duration); ?></span>
                </li>
                <li>
                    <span class="sx-properties--name">Отработано</span>
                    <span class="sx-properties--value"><?php echo $worked; ?></span>
                </li>
                <?php endif; ?>
                <li>
                    <span class="sx-properties--name">Начало по плану</span>
                    <span class="sx-properties--value"><?php echo $planStartAt ? \Yii::$app->formatter->asDatetime($planStartAt) : '—'; ?></span>
                </li>
                <?php if ($showWorkTime) : ?>
                <li>
                    <span class="sx-properties--name">Время завершения</span>
                    <span class="sx-properties--value"><?php echo $planEndAt ? \Yii::$app->formatter->asDatetime($planEndAt) : '—'; ?></span>
                </li>
                <?php endif; ?>
                <li>
                    <span class="sx-properties--name">Компания</span>
                    <span class="sx-properties--value"><?php echo $model->cmsCompany ? Html::encode($model->cmsCompany->name) : '—'; ?></span>
                </li>
                <li>
                    <span class="sx-properties--name">Проект</span>
                    <span class="sx-properties--value"><?php echo $model->cmsProject ? Html::encode($model->cmsProject->name) : '—'; ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id' => 'sx-upa-task-comments',
]); ?>

<?php if ($taskResultQuery->count()) : ?>
    <section class="sx-upa-task-section">
        <div class="sx-upa-task-section-title">Результат по задаче</div>
        <?php echo CmsLogListWidget::widget([
            'query' => $taskResultQuery,
            'is_show_model' => false,
            'is_show_pin_controls' => false,
        ]); ?>
    </section>
<?php endif; ?>

<div class="sx-upa-task-comment-block">
    <div class="sx-surface sx-surface--padded">
        <?php echo CmsCommentWidget::widget([
            'model' => $model,
            'backend_url' => ['add-comment', 'pk' => $model->id, '_sxb' => \Yii::$app->request->get('_sxb')],
        ]); ?>
    </div>
</div>

<?php if ($model->getLogs()->comments()->count()) : ?>
    <div class="sx-upa-task-section">
        <?php echo CmsLogListWidget::widget([
            'query' => $model->getLogs()->comments(),
            'is_show_model' => false,
            'is_show_pin_controls' => false,
        ]); ?>
    </div>
<?php endif; ?>

<?php $pjax::end(); ?>
</div>

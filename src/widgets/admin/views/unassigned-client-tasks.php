<?php

use skeeks\cms\widgets\admin\CmsTaskStatusWidget;
use skeeks\cms\widgets\admin\CmsTaskViewWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $count int */
/* @var $tasks \skeeks\cms\models\CmsTask[] */

$this->registerCss(<<<CSS
.sx-client-task-triage {
    border-left: 3px solid var(--sx-color-warning, #e3a008);
    margin-bottom: 18px;
}
.sx-client-task-triage__header {
    align-items: center;
    display: flex;
    gap: 16px;
    justify-content: space-between;
    padding: 14px 18px;
}
.sx-client-task-triage__title {
    color: var(--sx-color-text);
    font-size: 16px;
    font-weight: 600;
}
.sx-client-task-triage__description {
    color: var(--sx-color-text-muted);
    font-size: 13px;
    margin-top: 2px;
}
.sx-client-task-triage__link {
    border-bottom: 1px dotted currentColor;
    color: var(--sx-color-primary);
    white-space: nowrap;
}
.sx-client-task-triage__table {
    margin-bottom: 0;
}
.sx-client-task-triage__table td {
    vertical-align: middle !important;
}
.sx-client-task-triage__status {
    text-align: center;
    width: 50px;
}
CSS
);
?>

<section class="sx-surface sx-surface--raised sx-client-task-triage">
    <div class="sx-client-task-triage__header">
        <div>
            <div class="sx-client-task-triage__title">Неразобранные задачи от клиентов — <?php echo $count; ?></div>
            <div class="sx-client-task-triage__description">Назначьте исполнителя, чтобы задача попала в рабочий календарь.</div>
        </div>
        <a class="sx-client-task-triage__link" href="<?php echo Url::to(['/cms/admin-cms-task/index', 'findex' => ['ready' => 'unassigned_client']]); ?>">Открыть все</a>
    </div>
    <table class="table sx-table sx-client-task-triage__table">
        <tbody>
        <?php foreach ($tasks as $task) : ?>
            <tr>
                <td><?php echo CmsTaskViewWidget::widget(['task' => $task]); ?></td>
                <td class="sx-client-task-triage__status"><?php echo CmsTaskStatusWidget::widget(['task' => $task, 'isShort' => true]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

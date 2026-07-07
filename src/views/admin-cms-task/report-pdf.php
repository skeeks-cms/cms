<?php
/**
 * @var yii\web\View $this
 * @var array $report
 */

use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$columns = (array)ArrayHelper::getValue($report, 'params.columns', []);
$labels = (array)ArrayHelper::getValue($report, 'columns', []);
$taskView = (string)ArrayHelper::getValue($report, 'params.task_view', 'list');
$showTime = (bool)array_intersect(['fact_time', 'fact_hours'], $columns);
$params = (array)ArrayHelper::getValue($report, 'params', []);
$filterRows = [];

if (!empty($params['cms_company_id'])) {
    $model = CmsCompany::findOne((int)$params['cms_company_id']);
    if ($model) {
        $filterRows[] = ['Компания', (string)$model->asText];
    }
}
if (!empty($params['cms_project_id'])) {
    $model = CmsProject::findOne((int)$params['cms_project_id']);
    if ($model) {
        $filterRows[] = ['Проект', (string)$model->asText];
    }
}
if (!empty($params['cms_user_id'])) {
    $model = CmsUser::findOne((int)$params['cms_user_id']);
    if ($model) {
        $filterRows[] = ['Клиент', (string)$model->asText];
    }
}
if (!empty($params['executor_id'])) {
    $model = CmsUser::findOne((int)$params['executor_id']);
    if ($model) {
        $filterRows[] = ['Исполнитель', (string)$model->asText];
    }
}
if (!empty($params['status'])) {
    $statuses = CmsTask::statuses();
    $statusNames = [];
    foreach ((array)$params['status'] as $status) {
        $statusNames[] = ArrayHelper::getValue($statuses, $status, $status);
    }
    if ($statusNames) {
        $filterRows[] = ['Статус', implode(', ', $statusNames)];
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: dejavusans, sans-serif;
            font-size: 10pt;
            color: #222;
        }
        h1 {
            font-size: 18pt;
            margin: 0 0 8px;
        }
        .summary {
            margin: 0 0 16px;
            padding: 10px;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            vertical-align: top;
        }
        th {
            background: #f1f3f5;
            font-weight: bold;
        }
        .result {
            white-space: pre-line;
        }
        .task-list {
            margin-top: 8px;
        }
        .task-item {
            border-bottom: 1px solid #ddd;
            padding: 0 0 10px;
            margin-bottom: 10px;
        }
        .task-title {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 5px;
        }
        .task-meta {
            color: #555;
            font-size: 9pt;
            margin: 0 0 7px;
        }
        .task-meta span {
            display: inline-block;
            margin-right: 12px;
        }
        .task-result {
            border-left: 3px solid #ccc;
            background: #f7f7f7;
            margin: 0;
            padding: 7px 9px;
            white-space: pre-line;
        }
    </style>
</head>
<body>
<h1><?php echo Html::encode(ArrayHelper::getValue($report, 'title', 'Отчет по задачам')); ?></h1>
<div class="summary">
    Период: <?php echo Html::encode(ArrayHelper::getValue($report, 'params.period')); ?><br>
    <?php foreach ($filterRows as $filterRow) : ?>
        <?php echo Html::encode($filterRow[0]); ?>: <?php echo Html::encode($filterRow[1]); ?><br>
    <?php endforeach; ?>
    Задач: <?php echo (int)ArrayHelper::getValue($report, 'summary.tasks'); ?><?php if ($showTime) : ?><br>
    Отработанное время: <?php echo Html::encode(CmsScheduleHelper::durationAsText((int)ArrayHelper::getValue($report, 'summary.duration'))); ?><br>
    Отработано часов: <?php echo Html::encode(\Yii::$app->formatter->asDecimal((float)ArrayHelper::getValue($report, 'summary.hours'), 1)); ?><?php endif; ?>
</div>

<?php if ($taskView == 'list') : ?>
    <div class="task-list">
        <?php foreach ((array)ArrayHelper::getValue($report, 'rows') as $row) : ?>
            <div class="task-item">
                <div class="task-title"><?php echo Html::encode(ArrayHelper::getValue($row, 'name')); ?></div>
                <div class="task-meta">
                    <?php foreach ($columns as $column) : ?>
                        <?php if (in_array($column, ['name', 'result'])) {
                            continue;
                        } ?>
                        <?php $value = ArrayHelper::getValue($row, $column); ?>
                        <?php if ($value === null || $value === '') {
                            continue;
                        } ?>
                        <span><b><?php echo Html::encode(ArrayHelper::getValue($labels, $column, $column)); ?>:</b> <?php echo Html::encode($value); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if (in_array('result', $columns) && ArrayHelper::getValue($row, 'result') !== '') : ?>
                    <div class="task-result"><?php echo Html::encode(ArrayHelper::getValue($row, 'result')); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$report['rows']) : ?>
            <div>По выбранным условиям данных нет.</div>
        <?php endif; ?>
    </div>
<?php else : ?>
    <table>
        <thead>
        <tr>
            <?php foreach ($columns as $column) : ?>
                <th><?php echo Html::encode(ArrayHelper::getValue($labels, $column, $column)); ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ((array)ArrayHelper::getValue($report, 'rows') as $row) : ?>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <td class="<?php echo $column == 'result' ? 'result' : ''; ?>">
                        <?php echo Html::encode(ArrayHelper::getValue($row, $column)); ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$report['rows']) : ?>
            <tr>
                <td colspan="<?php echo max(1, count($columns)); ?>">По выбранным условиям данных нет.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>

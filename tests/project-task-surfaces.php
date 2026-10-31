<?php

$projectView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-project/view.php');
$taskView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-task/view.php');
// «Мой календарь» is a thin view around CmsTaskCalendarWidget (tasks, step Т-В): check both.
$calendarView = file_get_contents(dirname(__DIR__).'/src/views/admin-worker/tasks-calendar.php')
    .file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/task-calendar.php');
$reportView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-task/report.php');

function projectTaskSurfaceExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach ([
    'Project' => $projectView,
    'Task' => $taskView,
    'Task calendar' => $calendarView,
    'Task report' => $reportView,
] as $name => $view) {
    projectTaskSurfaceExpect(strpos($view, 'sx-block') === false, $name.' still contains deprecated sx-block markup.');
    projectTaskSurfaceExpect(strpos($view, 'sx-panel') === false, $name.' still contains deprecated sx-panel markup.');
    projectTaskSurfaceExpect(strpos($view, 'sx-surface') !== false, $name.' does not use canonical surfaces.');
    projectTaskSurfaceExpect(strpos($view, '--sx-surface-stack-gap') === false, $name.' relies on a nonexistent surface stack gap token.');
}

projectTaskSurfaceExpect(strpos($projectView, 'BackendSurfaceWidget::begin') !== false, 'Project view does not use the canonical surface widget.');
projectTaskSurfaceExpect(strpos($projectView, 'sx-project-content sx-surface-stack') !== false, 'Project view does not use the canonical surface stack.');
projectTaskSurfaceExpect(strpos($projectView, 'sx-surface sx-project-overview-item') !== false, 'Project overview items are not canonical surfaces.');
projectTaskSurfaceExpect(strpos($taskView, 'BackendSurfaceWidget::begin') !== false, 'Task view does not use the canonical surface widget.');
projectTaskSurfaceExpect(strpos($taskView, 'sx-task-content sx-surface-stack') !== false, 'Task view does not use the canonical root surface stack.');
projectTaskSurfaceExpect(strpos($taskView, 'sx-task-layout sx-detail-layout sx-detail-layout--main-first') !== false, 'Task view does not use the main-first shared detail composition.');
projectTaskSurfaceExpect(strpos($taskView, 'sx-task-layout__main sx-detail-layout__main') !== false, 'Task main column does not use the semantic detail-layout contract.');
projectTaskSurfaceExpect(strpos($taskView, 'sx-task-layout__aside sx-detail-layout__aside') !== false, 'Task aside column does not use the semantic detail-layout contract.');
// The description is the body of the task's raised main surface (BackendSurfaceWidget), not a surface nested in it.
projectTaskSurfaceExpect(preg_match("/BackendSurfaceWidget::begin\(\[\s*'raised'\s*=> true,[^\]]*'options'\s*=> \['class' => 'sx-task-main-surface'\]/s", $taskView) === 1
    && preg_match("/'sx-task-main-surface'\],\s*\]\);\s*\?>\s*<div class=\"sx-task-description\">/s", $taskView) === 1, 'Task description is not the body of the raised main surface.');
projectTaskSurfaceExpect(strpos($taskView, 'sx-surface sx-task-description') === false, 'Task description is a surface nested in the main surface.');
projectTaskSurfaceExpect(strpos($calendarView, 'sx-surface sx-surface--raised sx-task-calendar__month') !== false
    && strpos($calendarView, 'sx-surface sx-surface--raised sx-task-calendar__timeline') !== false, 'Calendar grids are not raised surfaces.');
projectTaskSurfaceExpect(strpos($reportView, 'sx-task-report sx-surface-stack') !== false, 'Task report does not use the canonical root surface stack.');
projectTaskSurfaceExpect(strpos($reportView, 'sx-report-card sx-surface sx-surface--raised sx-surface--padded') !== false, 'Report cards are not raised padded surfaces.');

echo "CMS project and task surface migration contract: OK\n";

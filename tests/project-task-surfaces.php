<?php

$projectView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-project/view.php');
$taskView = file_get_contents(dirname(__DIR__).'/src/views/admin-cms-task/view.php');
$calendarView = file_get_contents(dirname(__DIR__).'/src/views/admin-worker/tasks-calendar.php');
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
projectTaskSurfaceExpect(strpos($taskView, 'sx-surface sx-task-description') !== false, 'Task description is not a canonical surface.');
projectTaskSurfaceExpect(strpos($calendarView, "'sx-surface', 'sx-surface--raised', 'sx-worker-calendar-day'") !== false, 'Calendar day cards are not raised surfaces.');
projectTaskSurfaceExpect(strpos($reportView, 'sx-task-report sx-surface-stack') !== false, 'Task report does not use the canonical root surface stack.');
projectTaskSurfaceExpect(strpos($reportView, 'sx-report-card sx-surface sx-surface--raised sx-surface--padded') !== false, 'Report cards are not raised padded surfaces.');

echo "CMS project and task surface migration contract: OK\n";

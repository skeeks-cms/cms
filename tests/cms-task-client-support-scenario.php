<?php

$taskSource = file_get_contents(__DIR__.'/../src/models/CmsTask.php');
$querySource = file_get_contents(__DIR__.'/../src/models/queries/CmsTaskQuery.php');
$controllerSource = file_get_contents(__DIR__.'/../src/controllers/AdminCmsTaskController.php');
$calendarSource = file_get_contents(__DIR__.'/../src/views/admin-worker/tasks-calendar.php');
$taskListSource = file_get_contents(__DIR__.'/../src/widgets/admin/views/worker-tasks-calendar.php');
$triageWidgetSource = file_get_contents(__DIR__.'/../src/widgets/admin/CmsUnassignedClientTasksWidget.php');
$triageViewSource = file_get_contents(__DIR__.'/../src/widgets/admin/views/unassigned-client-tasks.php');

$checks = [
    'client support scenario is declared' => strpos($taskSource, "SCENARIO_CLIENT_SUPPORT = 'client-support'") !== false,
    'executor stays required outside client support' => strpos($taskSource, "[['executor_id'], 'required', 'except' => self::SCENARIO_CLIENT_SUPPORT]") !== false,
    'triage scope requires an empty executor' => strpos($querySource, "->andWhere([\$taskTable.'.executor_id' => null])") !== false,
    'triage scope requires a client' => strpos($querySource, "->andWhere(['not', [\$taskTable.'.cms_user_id' => null]])") !== false,
    'triage scope identifies a client-created task' => strpos($querySource, "created_by = '.\$taskTable.'.cms_user_id") !== false,
    'admin filter uses the reusable triage scope' => strpos($controllerSource, '$query->unassignedFromClients()') !== false,
    'admin task list keeps manager access scope' => strpos($controllerSource, '$query->forManager()') !== false,
    'unassigned client task notifies admin users' => strpos($taskSource, 'notifyAdminUsersAboutUnassignedClientTask') !== false,
    'notification uses standard web notify model' => strpos($taskSource, 'new CmsWebNotify()') !== false,
    'notification recipients use admin permission' => strpos($taskSource, 'PERMISSION_ROLE_ADMIN_ACCESS') !== false,
    'notification does not target the task author' => strpos($taskSource, "(int)\$adminUserId === (int)\$this->created_by") !== false,
    'triage widget is restricted to admin permission' => strpos($triageWidgetSource, 'PERMISSION_ROLE_ADMIN_ACCESS') !== false,
    'calendar uses shared triage widget' => strpos($calendarSource, 'CmsUnassignedClientTasksWidget') !== false,
    'task list uses shared triage widget' => strpos($taskListSource, 'CmsUnassignedClientTasksWidget') !== false,
    'shared widget shows the client triage queue' => strpos($triageViewSource, 'Неразобранные задачи от клиентов') !== false,
];

foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

echo "cms-task-client-support-scenario: OK\n";

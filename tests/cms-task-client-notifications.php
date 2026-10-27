<?php

$skeeksSource = file_get_contents(__DIR__.'/../src/Skeeks.php');
$taskSource = file_get_contents(__DIR__.'/../src/models/CmsTask.php');
$notifySource = file_get_contents(__DIR__.'/../src/models/CmsWebNotify.php');

$checks = [
    'task comments create web notifications' => strpos($skeeksSource, 'Добавлен новый комментарий к задаче') !== false,
    'comment author is excluded from recipients' => strpos($skeeksSource, '$id === $currentUserId') !== false,
    'review status notifies the task author' => strpos($skeeksSource, 'if ($status == CmsTask::STATUS_ON_CHECK)') !== false,
    'ready status notifies the task author' => strpos($skeeksSource, 'if ($status == CmsTask::STATUS_READY)') !== false,
    'task notifications use registered kinds' => strpos($skeeksSource, "\$notifications->notify(\$model->created_by, 'task.ready'") !== false,
    'client task status notifications use the client cabinet URL' => strpos($skeeksSource, '$clientUrl = $model->isClientAuthored() ? $model->getClientViewUrl() : null;') !== false
        && substr_count($skeeksSource, "'url' => \$clientUrl") >= 2,
    'client task comment notification deep-links to its log' => strpos($skeeksSource, '$model->getClientViewUrl((int)$sender->id)') !== false,
    'client task URL uses the reusable UPA support route' => strpos($taskSource, "/cms/upa-support/view?") !== false,
    'client task URL supports canonical activity anchors' => strpos($taskSource, "['sx-log-id' => \$logId]") !== false,
    'legacy client task notifications fall back to the UPA card' => strpos($notifySource, '$model instanceof CmsTask') !== false,
];

foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

echo "cms-task-client-notifications: OK\n";

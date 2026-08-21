<?php

$skeeksSource = file_get_contents(__DIR__.'/../src/Skeeks.php');
$taskSource = file_get_contents(__DIR__.'/../src/models/CmsTask.php');
$notifySource = file_get_contents(__DIR__.'/../src/models/CmsWebNotify.php');

$checks = [
    'task comments create web notifications' => strpos($skeeksSource, 'Добавлен новый комментарий к задаче') !== false,
    'comment author is excluded from recipients' => strpos($skeeksSource, '(int)$id !== $currentUserId') !== false,
    'review status notifies the task author' => strpos($skeeksSource, 'if ($status == CmsTask::STATUS_ON_CHECK)') !== false,
    'ready status notifies the task author' => strpos($skeeksSource, 'if ($status == CmsTask::STATUS_READY)') !== false,
    'ready notification uses standard web notify model' => strpos($skeeksSource, '$notify->name = "Ваша задача готова";') !== false,
    'client task status notifications use the client cabinet URL' => substr_count($skeeksSource, '$notify->url = $model->getClientViewUrl();') >= 2,
    'client task comment notification deep-links to its log' => strpos($skeeksSource, '$notifyTmp->url = $model->getClientViewUrl((int)$sender->id);') !== false,
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

<?php

$skeeksSource = file_get_contents(__DIR__.'/../src/Skeeks.php');

$checks = [
    'task comments create web notifications' => strpos($skeeksSource, 'Добавлен новый комментарий к задаче') !== false,
    'comment author is excluded from recipients' => strpos($skeeksSource, 'if ($id != \Yii::$app->user->id)') !== false,
    'review status notifies the task author' => strpos($skeeksSource, 'if ($status == CmsTask::STATUS_ON_CHECK)') !== false,
    'ready status notifies the task author' => strpos($skeeksSource, 'if ($status == CmsTask::STATUS_READY)') !== false,
    'ready notification uses standard web notify model' => strpos($skeeksSource, '$notify->name = "Ваша задача готова";') !== false,
];

foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

echo "cms-task-client-notifications: OK\n";

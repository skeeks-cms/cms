<?php
namespace skeeks\cms\console\controllers;

class UserSessionsController extends \yii\console\Controller
{
    /** Remove one bounded batch of long-expired/revoked sessions in the configured realm. */
    public function actionCleanup($retentionDays = 30, $limit = 500)
    {
        $count = \Yii::$app->userSessions->cleanup((int)$retentionDays * 86400, (int)$limit);
        $this->stdout("Removed sessions: $count\n");
        return 0;
    }
}

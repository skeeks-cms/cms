<?php
namespace skeeks\cms\jobs;

use skeeks\cms\job\contracts\JobReporterInterface;
use skeeks\cms\job\handlers\AbstractJobHandler;
use skeeks\cms\job\runtime\JobContext;

/**
 * Removes read notifications older than notifications.retentionDays (90 by default).
 */
class NotifyCleanupJobHandler extends AbstractJobHandler
{
    public function run(JobContext $context, JobReporterInterface $reporter): void
    {
        $reporter->setTotal(null);
        $removed = \Yii::$app->notifications->cleanup();
        if ($removed) {
            $reporter->countSuccess($removed);
        }
        $reporter->setResult(['removed' => $removed]);
        $reporter->setStage('completed');
    }
}

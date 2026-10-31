<?php
namespace skeeks\cms\jobs;

use skeeks\cms\job\contracts\JobReporterInterface;
use skeeks\cms\job\handlers\AbstractJobHandler;
use skeeks\cms\job\runtime\JobContext;
use skeeks\cms\services\TaskReminders;

/**
 * Sends the reminders of tasks whose time came (every minute, the agent cms/task/remind).
 */
class TaskRemindJobHandler extends AbstractJobHandler
{
    public function run(JobContext $context, JobReporterInterface $reporter): void
    {
        $reporter->setTotal(null);
        $sent = (new TaskReminders())->sendDue();
        if ($sent) {
            $reporter->countSuccess($sent);
        }
        $reporter->setResult(['sent' => $sent]);
        $reporter->setStage('completed');
    }
}

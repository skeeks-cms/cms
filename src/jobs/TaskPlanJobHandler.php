<?php
namespace skeeks\cms\jobs;

use skeeks\cms\job\contracts\JobReporterInterface;
use skeeks\cms\job\handlers\AbstractJobHandler;
use skeeks\cms\job\runtime\JobContext;
use skeeks\cms\services\TaskPlanner;

/**
 * Lays out the active tasks of every executor again: auto tasks whose time passed move on to free time.
 */
class TaskPlanJobHandler extends AbstractJobHandler
{
    public function run(JobContext $context, JobReporterInterface $reporter): void
    {
        $reporter->setTotal(null);
        $count = TaskPlanner::hasStartMode() ? (new TaskPlanner())->planAll() : 0;
        if ($count) {
            $reporter->countSuccess($count);
        }
        $reporter->setResult(['executors' => $count]);
        $reporter->setStage('completed');
    }
}

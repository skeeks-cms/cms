<?php
namespace skeeks\cms\console\controllers;

use skeeks\cms\models\CmsUser;
use skeeks\cms\services\TaskPlanner;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Tasks.
 *
 *   php yii cms/task/plan            # lay out the active tasks of every executor again (by hand: tasks move only on events)
 *   php yii cms/task/plan --user=110 # one executor
 *   php yii cms/task/remind          # send the reminders whose time came (the agent cms/task/remind)
 */
class TaskController extends Controller
{
    /** @var int plan: only this executor */
    public $user;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), $actionID === 'plan' ? ['user'] : []);
    }

    /** Sends the reminders whose time came (the agent cms/task/remind). */
    public function actionRemind()
    {
        $this->stdout('Sent: '.(new \skeeks\cms\services\TaskReminders())->sendDue()."\n");

        return ExitCode::OK;
    }

    public function actionPlan()
    {
        $planner = new TaskPlanner();
        if ($this->user) {
            $user = CmsUser::findOne((int)$this->user);
            if (!$user) {
                $this->stderr("User not found.\n");
                return ExitCode::DATAERR;
            }
            $planner->plan($user);
            $this->stdout("Laid out: 1\n");
            return ExitCode::OK;
        }

        $this->stdout('Laid out: '.$planner->planAll()."\n");

        return ExitCode::OK;
    }
}

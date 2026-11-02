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
 *   php yii cms/task/to-day          # dry run: which fixed tasks would become «на день», who keeps a pin
 *   php yii cms/task/to-day --apply  # move them (owner's decision 02.11.2026; production only after the owner's «да»)
 */
class TaskController extends Controller
{
    /** @var int plan, to-day: only this executor */
    public $user;

    /** @var bool to-day: move the tasks; without it only a dry run */
    public $apply = false;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), in_array($actionID, ['plan', 'to-day'], true) ? ['user'] : [],
            $actionID === 'to-day' ? ['apply'] : []);
    }

    /**
     * The fixed tasks without a reminder and not a call or a meeting become «на день» of their date
     * (services\TaskDayMigration). A dry run by default: counts per executor and examples of both kinds.
     */
    public function actionToDay()
    {
        if (!TaskPlanner::hasPlanDay()) {
            $this->stderr("The migration of plan_day is not applied.\n");
            return ExitCode::UNAVAILABLE;
        }
        $migration = new \skeeks\cms\services\TaskDayMigration();
        $migration->userId = $this->user ? (int)$this->user : null;
        $summary = $migration->plan();
        $move = $keep = $past = 0;
        foreach ($summary as $id => $row) {
            $move += $row['move'];
            $keep += $row['keep'];
            $past += $row['past'];
            $this->stdout(sprintf("#%d %s: на день %d (из них день прошёл %d), с булавкой %d\n", $id, $row['name'], $row['move'], $row['past'], $row['keep']));
            foreach ($row['examples_move'] as $name) {
                $this->stdout('    → на день: '.$name."\n");
            }
            foreach ($row['examples_keep'] as [$name, $reason, $start]) {
                $this->stdout('    = булавка ('.$reason.'): '.$name.' ('.$start.")\n");
            }
        }
        $this->stdout(sprintf("Всего: на день %d (день прошёл %d), остаются с булавкой %d\n", $move, $past, $keep));
        if (!$this->apply) {
            $this->stdout("Пробный запуск: ничего не изменено. Перевод — с --apply.\n");
            return ExitCode::OK;
        }
        $this->stdout('Переведено: '.$migration->apply()."\n");

        return ExitCode::OK;
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

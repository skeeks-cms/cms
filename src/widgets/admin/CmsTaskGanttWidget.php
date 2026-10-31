<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\assets\admin\TaskGanttAsset;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\services\TaskPlanner;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Gantt of an employee's active tasks (owner's decision 31.10.2026): rows of tasks, grouped by project or
 * client, bars from the start to the end on a day, week or month scale. Dragging a bar moves the start,
 * dragging its right edge changes the duration (AdminCmsTaskController::actionReschedule); the moved task
 * becomes fixed. Closed tasks and tasks in work do not move; without the right to manage a task it is read-only.
 */
class CmsTaskGanttWidget extends Widget
{
    public const SCALES = ['day', 'week', 'month'];

    /** @var CmsUser whose tasks */
    public $user;

    /** @var CmsUser[] employees the viewer may choose (self, subordinates; all for an administrator) */
    public $users = [];

    public $options = [];

    public function run()
    {
        TaskGanttAsset::register($this->view);

        $this->options['id'] = $this->id;
        Html::addCssClass($this->options, 'sx-task-gantt');
        $this->options['data-sx-task-gantt'] = Json::encode([
            'tasks'      => $this->tasksData(),
            'work'       => $this->workData(),
            'now'        => time(),
            'tz'         => \Yii::$app->formatter->timeZone,
            'moveUrl'    => \yii\helpers\Url::to(['/cms/admin-cms-task/reschedule']),
            'dataUrl'    => \yii\helpers\Url::current(['sx-gantt-data' => 1]),
        ]);

        return $this->render('task-gantt', ['widget' => $this]);
    }

    /**
     * The data the page refreshes after a move: the planner (run by the move) may have moved other auto tasks.
     */
    public function data(): array
    {

        return ['tasks' => $this->tasksData(), 'work' => $this->workData(), 'now' => time()];
    }

    protected function tasksData(): array
    {
        $tasks = CmsTask::find()
            ->andWhere(['executor_id' => (int)$this->user->id, 'status' => CmsTask::activeStatuses()])
            ->with(['cmsProject', 'cmsCompany', 'cmsUser'])
            ->orderBy(['plan_start_at' => SORT_ASC, 'executor_sort' => SORT_ASC])
            ->limit(1000)
            ->all();

        $isSelf = (int)\Yii::$app->user->id === (int)$this->user->id;
        $result = [];
        foreach ($tasks as $task) {
            $canManage = $isSelf || \Yii::$app->user->can('cms/admin-task/manage', ['model' => $task]);
            $client = $task->cmsCompany ? $task->cmsCompany->name : ($task->cmsUser ? $task->cmsUser->shortDisplayName : '');
            $start = (int)$task->plan_start_at;
            $result[] = [
                'id'        => (int)$task->id,
                'name'      => (string)$task->name,
                'start'     => $start,
                'end'       => max((int)$task->plan_end_at, $start + max(300, (int)$task->plan_duration)),
                'duration'  => (int)$task->plan_duration,
                'fixed'     => !$task->hasAttribute('is_fixed_start') || (bool)$task->is_fixed_start,
                'status'    => (string)$task->status,
                'statusText'=> (string)$task->statusAsText,
                'inWork'    => $task->status === CmsTask::STATUS_IN_WORK,
                'late'      => $task->status !== CmsTask::STATUS_IN_WORK && $start < time() - 60,
                'movable'   => $canManage && $task->status !== CmsTask::STATUS_IN_WORK,
                'project'   => $task->cmsProject ? (string)$task->cmsProject->name : '',
                'client'    => (string)$client,
                'reminder'  => $task->hasAttribute('remind_at') && $task->remind_at ? $task->reminderText : '',
                'reminded'  => $task->hasAttribute('reminded_at') && (bool)$task->reminded_at,
                'replanned' => $task->replannedText,
                'url'       => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-cms-task/view', 'pk' => $task->id])
                    ->enableEmptyLayout()->enableNoActions()->url,
            ];
        }

        return $result;
    }

    /**
     * Working periods of the employee from a week ago to three months ahead: [start, end] pairs.
     */
    protected function workData(): array
    {
        $schedule = is_array($this->user->work_shedule) ? array_values(array_filter($this->user->work_shedule, static function ($row) {
            return !empty($row['day']);
        })) : [];
        $schedule = $schedule ?: TaskPlanner::DEFAULT_SCHEDULE;
        $tz = new \DateTimeZone(\Yii::$app->formatter->timeZone);
        $day = (new \DateTime('today', $tz))->modify('-7 days');
        $result = [];
        for ($i = 0; $i < 100; $i++) {
            foreach (\skeeks\cms\helpers\CmsScheduleHelper::getSchedulesByWorktimeForDate($schedule, $day->format('Y-m-d')) as $period) {
                $result[] = [(int)$period->start_at, (int)$period->end_at];
            }
            $day->modify('+1 day');
        }

        return $result;
    }
}

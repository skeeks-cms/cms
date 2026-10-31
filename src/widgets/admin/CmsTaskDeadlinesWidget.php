<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\assets\admin\TaskCalendarAsset;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\services\TaskPlanner;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * «Сроки»: active tasks of an employee in columns by their start — overdue, today, tomorrow, this week, later.
 * A card moved to another column gets the first free time of that column's first day and becomes fixed
 * (AdminCmsTaskController::actionReschedule with `day`).
 */
class CmsTaskDeadlinesWidget extends Widget
{
    /** @var CmsUser */
    public $user;

    /** @var int cards drawn in a column */
    public $columnLimit = 60;

    public $options = [];

    public function run()
    {
        TaskCalendarAsset::register($this->view);
        if (TaskPlanner::hasStartMode()) {
            (new TaskPlanner())->plan($this->user);
        }

        $tz = new \DateTimeZone(\Yii::$app->formatter->timeZone);
        $today = new \DateTime('today', $tz);
        $tomorrow = (clone $today)->modify('+1 day');
        $afterTomorrow = (clone $today)->modify('+2 days');
        $nextWeek = (clone $today)->modify('+'.(8 - (int)$today->format('N')).' days');

        $f = \Yii::$app->formatter;
        $columns = [
            'late'     => ['title' => 'Просрочены', 'day' => null, 'sub' => '', 'tasks' => []],
            'today'    => ['title' => 'Сегодня', 'day' => $today->format('Y-m-d'), 'sub' => $f->asDate($today->getTimestamp(), 'php:D, j M'), 'tasks' => []],
            'tomorrow' => ['title' => 'Завтра', 'day' => $tomorrow->format('Y-m-d'), 'sub' => $f->asDate($tomorrow->getTimestamp(), 'php:D, j M'), 'tasks' => []],
        ];
        if ($afterTomorrow < $nextWeek) {
            $columns['week'] = ['title' => 'На этой неделе', 'day' => $afterTomorrow->format('Y-m-d'),
                'sub' => 'до '.$f->asDate((clone $nextWeek)->modify('-1 day')->getTimestamp(), 'php:D, j M'), 'tasks' => []];
        }
        $columns['later'] = ['title' => 'Позже', 'day' => max($nextWeek, $afterTomorrow)->format('Y-m-d'),
            'sub' => 'с '.$f->asDate(max($nextWeek, $afterTomorrow)->getTimestamp(), 'php:j M'), 'tasks' => []];

        $tasks = CmsTask::find()
            ->andWhere(['executor_id' => (int)$this->user->id, 'status' => CmsTask::activeStatuses()])
            ->orderBy(['plan_start_at' => SORT_ASC, 'executor_sort' => SORT_ASC])
            ->all();
        $now = time();
        foreach ($tasks as $task) {
            $start = (int)$task->plan_start_at;
            if ($task->status !== CmsTask::STATUS_IN_WORK && $start < $now - 60) {
                $key = 'late';
            } elseif ($start < $tomorrow->getTimestamp()) {
                $key = 'today';
            } elseif ($start < $afterTomorrow->getTimestamp()) {
                $key = 'tomorrow';
            } elseif (isset($columns['week']) && $start < $nextWeek->getTimestamp()) {
                $key = 'week';
            } else {
                $key = 'later';
            }
            $columns[$key]['tasks'][] = $task;
        }

        $this->options['id'] = $this->id;
        Html::addCssClass($this->options, 'sx-task-deadlines');

        return $this->render('task-deadlines', ['widget' => $this, 'columns' => $columns]);
    }

    public function canMove(CmsTask $task): bool
    {
        return $task->status !== CmsTask::STATUS_IN_WORK
            && ((int)\Yii::$app->user->id === (int)$this->user->id || \Yii::$app->user->can('cms/admin-task/manage', ['model' => $task]));
    }
}

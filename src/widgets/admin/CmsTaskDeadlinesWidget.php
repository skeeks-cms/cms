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

    /** @var TaskViewContext|null whose tasks and the search; null — the employee $user, fixed */
    public $context;

    /** Day columns after «Просрочены»: today, tomorrow and the rest of a week. */
    public const DAYS = 7;

    /** @var int cards drawn in a column */
    public $columnLimit = 60;

    public $options = [];

    public function run()
    {
        TaskCalendarAsset::register($this->view);
        if (!$this->context) {
            $this->context = TaskViewContext::forUser($this->user);
        }
        $this->user = $this->context->user;

        $tz = new \DateTimeZone(\Yii::$app->formatter->timeZone);
        $today = new \DateTime('today', $tz);
        $f = \Yii::$app->formatter;

        // Overdue, then a column for every day of the coming week (owner, 31.10.2026), then «Позже».
        $columns = ['late' => ['title' => 'Просрочены', 'day' => null, 'sub' => '', 'from' => null, 'tasks' => []]];
        for ($i = 0; $i < static::DAYS; $i++) {
            $day = (clone $today)->modify('+'.$i.' day');
            $title = $i === 0 ? 'Сегодня' : ($i === 1 ? 'Завтра' : mb_convert_case($f->asDate($day->getTimestamp(), 'php:l'), MB_CASE_TITLE));
            $columns['d'.$i] = [
                'title'   => $title,
                'day'     => $day->format('Y-m-d'),
                'sub'     => $f->asDate($day->getTimestamp(), $i < 2 ? 'php:D, j M' : 'php:j M'),
                'from'    => $day->getTimestamp(),
                'weekend' => (int)$day->format('N') >= 6,
                'tasks'   => [],
            ];
        }
        $laterDay = (clone $today)->modify('+'.static::DAYS.' day');
        $columns['later'] = ['title' => 'Позже', 'day' => $laterDay->format('Y-m-d'),
            'sub' => 'с '.$f->asDate($laterDay->getTimestamp(), 'php:j M'), 'from' => $laterDay->getTimestamp(), 'tasks' => []];

        $query = CmsTask::find()
            ->andWhere(['executor_id' => (int)$this->user->id, 'status' => CmsTask::activeStatuses()])
            ->with(['cmsProject', 'cmsCompany', 'cmsUser'])
            ->orderBy(['plan_start_at' => SORT_ASC, 'executor_sort' => SORT_ASC]);
        $tasks = $this->context->applySearch($query)->all();
        $now = time();
        foreach ($tasks as $task) {
            $start = (int)$task->plan_start_at;
            $key = 'late';
            // Only a fixed time is overdue: an auto task whose time passed stays in its day.
            $isFixed = !$task->hasAttribute('is_fixed_start') || $task->is_fixed_start;
            if ($task->status === CmsTask::STATUS_IN_WORK || !$isFixed || $start >= $now - 60) {
                $key = 'd0';
                foreach ($columns as $code => $column) {
                    if ($column['from'] !== null && $start >= $column['from']) {
                        $key = $code;
                    }
                }
            }
            $columns[$key]['tasks'][] = $task;
        }

        // A day whose tasks «на день» go past its working time: «перегружен, +N ч» in its column's head.
        $overload = CmsTaskGanttWidget::overloadData($this->user);
        foreach ($columns as $code => $column) {
            $columns[$code]['overload'] = $code !== 'later' && $column['day'] ? (int)($overload[$column['day']] ?? 0) : 0;
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

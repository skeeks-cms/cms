<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\services;

use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;

/**
 * One-time move of the fixed tasks to «на день» (owner's decision 02.11.2026): when every open task with a start
 * became fixed (m261031_100000), the plans of projects (articles, SEO stages) got pinned to a time nobody chose.
 *
 * A task moves when it is open and not in work, fixed, without a reminder and without a call or a meeting in its
 * name (KEEP_WORDS: calls and meetings keep their time): it becomes «на день» of the date of its start. A day
 * already passed makes it overdue — it waits today, first, «Просрочена на N дн.» (rule 3).
 *
 * plan() only counts; apply() moves, writes a history record per task and lays out every executor once.
 * A repeated run changes nothing.
 */
class TaskDayMigration
{
    /**
     * Words of a name that keep a task fixed: a call or a meeting is promised for a time. «звон» covers «Позвонить»,
     * «Перезвонить», «Созвон», «звонок».
     */
    public const KEEP_WORDS = ['звон', 'встреч'];

    /** @var int|null only this executor */
    public $userId;

    /** Fixed open tasks not in work, with what decides them. */
    protected function fixedQuery(): \yii\db\ActiveQuery
    {
        $query = CmsTask::find()
            ->andWhere(['status' => array_values(array_diff(CmsTask::activeStatuses(), [CmsTask::STATUS_IN_WORK]))])
            ->andWhere(['is_fixed_start' => 1])
            ->andWhere(['not', ['executor_id' => null]]);
        if ($this->userId) {
            $query->andWhere(['executor_id' => (int)$this->userId]);
        }

        return $query;
    }

    /** Why a fixed task stays fixed: '' — it moves. */
    public static function keepReason(CmsTask $task): string
    {
        if ($task->hasAttribute('remind_at') && $task->remind_at) {
            return 'напоминание';
        }
        $name = mb_strtolower((string)$task->name);
        foreach (static::KEEP_WORDS as $word) {
            if (mb_strpos($name, $word) !== false) {
                return 'звонок или встреча';
            }
        }

        return '';
    }

    /**
     * @return array executor id => [name, move (count), keep (count), past (moved ones whose day passed),
     *               examples_move (names), examples_keep ([name, reason, start])]
     */
    public function plan(int $examples = 5): array
    {
        $result = [];
        $today = \Yii::$app->formatter->asDate(time(), 'php:Y-m-d');
        foreach ($this->fixedQuery()->orderBy(['executor_id' => SORT_ASC, 'plan_start_at' => SORT_ASC])->each(200) as $task) {
            $id = (int)$task->executor_id;
            if (!isset($result[$id])) {
                $user = CmsUser::findOne($id);
                $result[$id] = ['name' => $user ? (string)$user->shortDisplayName : '#'.$id, 'move' => 0, 'keep' => 0, 'past' => 0,
                    'examples_move' => [], 'examples_keep' => []];
            }
            $reason = static::keepReason($task);
            $start = \Yii::$app->formatter->asDatetime((int)$task->plan_start_at, 'php:d.m.Y H:i');
            if ($reason !== '') {
                $result[$id]['keep']++;
                if (count($result[$id]['examples_keep']) < $examples) {
                    $result[$id]['examples_keep'][] = [(string)$task->name, $reason, $start];
                }
                continue;
            }
            $result[$id]['move']++;
            if (\Yii::$app->formatter->asDate((int)$task->plan_start_at, 'php:Y-m-d') < $today) {
                $result[$id]['past']++;
            }
            if (count($result[$id]['examples_move']) < $examples) {
                $result[$id]['examples_move'][] = (string)$task->name.' ('.$start.')';
            }
        }

        return $result;
    }

    /**
     * Moves the tasks and lays out each executor once.
     *
     * @return int tasks moved
     */
    public function apply(): int
    {
        $f = \Yii::$app->formatter;
        $moved = 0;
        $executors = [];
        foreach ($this->fixedQuery()->orderBy(['executor_id' => SORT_ASC, 'plan_start_at' => SORT_ASC])->each(200) as $task) {
            if (static::keepReason($task) !== '') {
                continue;
            }
            $day = $f->asDate((int)$task->plan_start_at, 'php:Y-m-d');
            $transaction = CmsTask::getDb()->beginTransaction();
            try {
                if (!TaskPlanner::lockExecutors([(int)$task->executor_id])) {
                    throw new \yii\base\Exception(TaskPlanner::BUSY_MESSAGE);
                }
                CmsTask::updateAll(['plan_day' => $day, 'is_fixed_start' => 0], ['id' => (int)$task->id, 'is_fixed_start' => 1]);
                $log = new CmsLog();
                $log->model_code = $task->skeeksModelCode;
                $log->model_id = $task->id;
                $log->model_as_text = $task->asText;
                $log->log_type = CmsLog::LOG_TYPE_UPDATE;
                $log->data = ['plan_day' => [
                    'name' => 'Начало',
                    'old'  => 'закреплено на '.$f->asDate((int)$task->plan_start_at, 'php:j M').' '.$f->asTime((int)$task->plan_start_at, 'php:H:i'),
                    'new'  => 'На день '.$f->asDate($day, 'php:j M').' (перевод закреплённых задач)',
                ]];
                $log->save(false);
                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
            $executors[(int)$task->executor_id] = true;
            $moved++;
        }

        $planner = new TaskPlanner();
        foreach (array_keys($executors) as $id) {
            if ($user = CmsUser::findOne($id)) {
                $planner->plan($user);
            }
        }

        return $moved;
    }
}

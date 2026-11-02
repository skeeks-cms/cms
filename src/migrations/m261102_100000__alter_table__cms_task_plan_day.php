<?php

use yii\db\Migration;

/**
 * The third mode of a task's start (owner's decision 02.11.2026): «на день» — only the day is fixed, the planner
 * puts the task into that day's free time. `plan_day` keeps the day even while an unfinished task waits today
 * («Просрочена на N дн.»); `is_fixed_start` stays 0 for it.
 *
 * Rollback: a task «на день» becomes fixed on its current start (the nearest former meaning), the column goes.
 */
class m261102_100000__alter_table__cms_task_plan_day extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%cms_task}}', true);
        if ($schema && $schema->getColumn('plan_day') === null) {
            $this->addColumn('{{%cms_task}}', 'plan_day', $this->date()->null()->comment('День задачи (режим «на день»)'));
            $this->createIndex('cms_task__executor_plan_day', '{{%cms_task}}', ['executor_id', 'plan_day']);
        }

        return true;
    }

    public function safeDown()
    {
        $this->update('{{%cms_task}}', ['is_fixed_start' => 1], ['not', ['plan_day' => null]]);
        $this->dropIndex('cms_task__executor_plan_day', '{{%cms_task}}');
        $this->dropColumn('{{%cms_task}}', 'plan_day');

        return true;
    }
}

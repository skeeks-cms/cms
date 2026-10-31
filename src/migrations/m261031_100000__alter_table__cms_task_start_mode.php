<?php

use yii\db\Migration;

/**
 * Every task has a start (owner's decision 31.10.2026): `is_fixed_start` tells a time chosen by a person
 * from a start the planner gives. Old tasks: an active task with a start becomes fixed; a closed task
 * without one gets the start of its first work interval, else its creation time (history only);
 * active tasks without a start are laid out by TaskPlanner in their current queue order.
 */
class m261031_100000__alter_table__cms_task_start_mode extends Migration
{
    public function safeUp()
    {
        $table = '{{%cms_task}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema && $schema->getColumn('is_fixed_start') === null) {
            $this->addColumn($table, 'is_fixed_start', $this->tinyInteger(1)->notNull()->defaultValue(0)
                ->comment('Начало выбрано человеком и не сдвигается'));
            $this->createIndex('cms_task__executor_status_start', $table, ['executor_id', 'status', 'plan_start_at']);
        }

        $active = ['new', 'accepted', 'in_work', 'on_pouse'];

        $this->update($table, ['is_fixed_start' => 1], ['and', ['status' => $active], ['not', ['plan_start_at' => null]]]);

        $closedWhere = ['and', ['not', ['status' => $active]], ['plan_start_at' => null]];
        $this->update($table, [
            'plan_start_at'  => new \yii\db\Expression('COALESCE((SELECT MIN(s.start_at) FROM {{%cms_task_schedule}} s WHERE s.cms_task_id = {{%cms_task}}.id), {{%cms_task}}.created_at)'),
            'is_fixed_start' => 1,
        ], $closedWhere);
        $this->update($table, [
            'plan_end_at' => new \yii\db\Expression('plan_start_at + COALESCE(plan_duration, 0)'),
        ], ['and', ['not', ['status' => $active]], ['plan_end_at' => null], ['not', ['plan_start_at' => null]]]);

        if (class_exists(\skeeks\cms\services\TaskPlanner::class) && \Yii::$app->has('formatter')) {
            $this->db->getTableSchema($table, true);
            echo '    > laid out executors: '.(new \skeeks\cms\services\TaskPlanner())->planAll()."\n";
        }

        return true;
    }

    public function safeDown()
    {
        $this->dropIndex('cms_task__executor_status_start', '{{%cms_task}}');
        $this->dropColumn('{{%cms_task}}', 'is_fixed_start');

        return true;
    }
}

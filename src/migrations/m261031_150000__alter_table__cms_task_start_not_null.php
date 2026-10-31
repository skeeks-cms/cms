<?php

use yii\db\Migration;

/**
 * Every task has a start (owner's decision 31.10.2026): the column becomes NOT NULL. Tasks still without one get
 * it first — active ones from TaskPlanner (end of the executor's queue), closed ones from their first work
 * interval or creation. Down makes the column nullable again; the starts stay.
 */
class m261031_150000__alter_table__cms_task_start_not_null extends Migration
{
    public function safeUp()
    {
        $table = '{{%cms_task}}';
        $active = ['new', 'accepted', 'in_work', 'on_pouse'];

        if (class_exists(\skeeks\cms\services\TaskPlanner::class) && \Yii::$app->has('formatter')
            && (new \yii\db\Query())->from($table)->where(['plan_start_at' => null, 'status' => $active])->exists($this->db)) {
            $this->db->getTableSchema($table, true);
            echo '    > laid out executors: '.(new \skeeks\cms\services\TaskPlanner())->planAll()."\n";
        }
        $this->update($table, [
            'plan_start_at' => new \yii\db\Expression('COALESCE((SELECT MIN(s.start_at) FROM {{%cms_task_schedule}} s WHERE s.cms_task_id = {{%cms_task}}.id), {{%cms_task}}.created_at, UNIX_TIMESTAMP())'),
        ], ['plan_start_at' => null]);

        $this->alterColumn($table, 'plan_start_at', $this->integer()->notNull()->comment('Начало задачи'));

        return true;
    }

    public function safeDown()
    {
        $this->alterColumn('{{%cms_task}}', 'plan_start_at', $this->integer()->null()->comment('Планируемое начало'));

        return true;
    }
}

<?php

use yii\db\Migration;

/**
 * Owner's clarifications 31.10.2026: `remind_by` — who set the reminder (a reminder a person set for themself
 * ignores their quiet hours); `replanned_from` / `replanned_at` — the previous start of an auto task the planner
 * moved noticeably, so people see that it moved and from when.
 */
class m261031_130000__alter_table__cms_task_replanned extends Migration
{
    public function safeUp()
    {
        $table = '{{%cms_task}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema && $schema->getColumn('remind_by') === null) {
            $this->addColumn($table, 'remind_by', $this->integer()->null()->comment('Кто поставил напоминание'));
        }
        if ($schema && $schema->getColumn('replanned_from') === null) {
            $this->addColumn($table, 'replanned_from', $this->integer()->null()->comment('Прежнее начало перенесённой задачи'));
            $this->addColumn($table, 'replanned_at', $this->integer()->null()->comment('Когда система перенесла задачу'));
        }

        return true;
    }

    public function safeDown()
    {
        foreach (['replanned_at', 'replanned_from', 'remind_by'] as $column) {
            $this->dropColumn('{{%cms_task}}', $column);
        }

        return true;
    }
}

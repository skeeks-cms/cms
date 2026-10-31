<?php

use yii\db\Migration;

/**
 * A reminder of a task (owner's decision 31.10.2026): `remind_at` — when; `remind_offset` — seconds before
 * the start when the reminder follows the start (null — a time of its own); `remind_author` — the author is
 * reminded too; `reminded_at` — when it was sent (a moved reminder is sent again).
 */
class m261031_120000__alter_table__cms_task_reminder extends Migration
{
    public function safeUp()
    {
        $table = '{{%cms_task}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema && $schema->getColumn('remind_at') === null) {
            $this->addColumn($table, 'remind_at', $this->integer()->null()->comment('Когда напомнить'));
            $this->addColumn($table, 'remind_offset', $this->integer()->null()->comment('Напомнить за столько секунд до начала'));
            $this->addColumn($table, 'remind_author', $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Напомнить и автору'));
            $this->addColumn($table, 'reminded_at', $this->integer()->null()->comment('Когда напоминание отправлено'));
            $this->createIndex('cms_task__remind', $table, ['reminded_at', 'remind_at']);
        }

        return true;
    }

    public function safeDown()
    {
        $this->dropIndex('cms_task__remind', '{{%cms_task}}');
        foreach (['reminded_at', 'remind_author', 'remind_offset', 'remind_at'] as $column) {
            $this->dropColumn('{{%cms_task}}', $column);
        }

        return true;
    }
}

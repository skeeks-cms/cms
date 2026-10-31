<?php

use yii\db\Migration;

/**
 * An optional deadline of a task (owner's decision 31.10.2026): when the planned end is after it, the task shows
 * the red mark «не успевает»; nothing is sent.
 */
class m261031_140000__alter_table__cms_task_deadline extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%cms_task}}', true);
        if ($schema && $schema->getColumn('deadline_at') === null) {
            $this->addColumn('{{%cms_task}}', 'deadline_at', $this->integer()->null()->comment('Крайний срок'));
        }

        return true;
    }

    public function safeDown()
    {
        $this->dropColumn('{{%cms_task}}', 'deadline_at');

        return true;
    }
}

<?php

use yii\db\Migration;

class m260629_230000__alter_table__cms_log__is_pinned extends Migration
{
    public function safeUp()
    {
        $tableName = '{{%cms_log}}';
        $table = $this->db->getTableSchema($tableName, true);

        if (!$table || $table->getColumn('is_pinned')) {
            return true;
        }

        $this->addColumn($tableName, 'is_pinned', $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Закрепленный комментарий'));
        $this->createIndex('cms_log__is_pinned', $tableName, 'is_pinned');

        return true;
    }

    public function safeDown()
    {
        $tableName = '{{%cms_log}}';
        $table = $this->db->getTableSchema($tableName, true);

        if (!$table || !$table->getColumn('is_pinned')) {
            return true;
        }

        $this->dropIndex('cms_log__is_pinned', $tableName);
        $this->dropColumn($tableName, 'is_pinned');

        return true;
    }
}

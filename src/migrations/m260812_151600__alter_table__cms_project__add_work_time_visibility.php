<?php

use yii\db\Migration;

class m260812_151600__alter_table__cms_project__add_work_time_visibility extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_project';
        $schema = $this->db->getTableSchema($tableName, true);

        if (!$schema || isset($schema->columns['is_work_time_visible_for_clients'])) {
            return true;
        }

        $this->addColumn(
            $tableName,
            'is_work_time_visible_for_clients',
            $this->boolean()->notNull()->defaultValue(0)->after('is_private')->comment('Клиенты видят рабочее время?')
        );

        return true;
    }

    public function safeDown()
    {
        $tableName = 'cms_project';
        $schema = $this->db->getTableSchema($tableName, true);

        if (!$schema || !isset($schema->columns['is_work_time_visible_for_clients'])) {
            return true;
        }

        $this->dropColumn($tableName, 'is_work_time_visible_for_clients');

        return true;
    }
}

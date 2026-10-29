<?php

use yii\db\Migration;

/**
 * The queue of «Неразобранное» of a pipeline (п. 13б, crm\InboundQueue): the
 * sources whose requests land in it, who sorts them (employees, departments)
 * and how (all of the list / by turns). Empty — the default of the CRM
 * settings «Входящие заявки». Existing pipelines keep the default.
 */
class m261029_220000__alter_table__cms_lead_pipeline_inbound extends Migration
{
    public function safeUp()
    {
        $tableName = '{{%cms_lead_pipeline}}';
        $schema = $this->db->getTableSchema($tableName, true);
        if ($schema === null) {
            return true;
        }
        if ($schema->getColumn('inbound_source_types') === null) {
            $this->addColumn($tableName, 'inbound_source_types', $this->text()->null()->comment('Источники заявок воронки, JSON'));
        }
        if ($schema->getColumn('inbound_worker_ids') === null) {
            $this->addColumn($tableName, 'inbound_worker_ids', $this->text()->null()->comment('Кто разбирает: сотрудники, JSON'));
        }
        if ($schema->getColumn('inbound_department_ids') === null) {
            $this->addColumn($tableName, 'inbound_department_ids', $this->text()->null()->comment('Кто разбирает: отделы, JSON'));
        }
        if ($schema->getColumn('inbound_mode') === null) {
            $this->addColumn($tableName, 'inbound_mode', $this->string(16)->null()->comment('all | round; пусто — как в настройках CRM'));
        }
    }

    public function safeDown()
    {
        $tableName = '{{%cms_lead_pipeline}}';
        foreach (['inbound_mode', 'inbound_department_ids', 'inbound_worker_ids', 'inbound_source_types'] as $column) {
            $this->dropColumn($tableName, $column);
        }
    }
}

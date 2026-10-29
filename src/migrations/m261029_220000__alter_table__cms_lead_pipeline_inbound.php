<?php

use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;

/**
 * The queue of «Неразобранное» of a pipeline (п. 13б, crm\InboundQueue): the
 * sources whose requests land in it, who sorts them (employees, departments)
 * and how (all of the list / by turns). Empty — the default of the CRM
 * settings «Входящие заявки». Existing pipelines keep the default.
 *
 * safeDown refuses before the first DDL while a pipeline has its own queue
 * settings (release audit F5, 29.10.2026): dropping the columns would erase
 * them silently; a site rolls such settings back from its backup. Empty
 * values (NULL, '', JSON [], {} or null) are the default and may go. A repeated or a
 * stopped rollback drops only the columns still there.
 */
class m261029_220000__alter_table__cms_lead_pipeline_inbound extends Migration
{
    use RepeatableMigrationTrait;

    private const TABLE = '{{%cms_lead_pipeline}}';

    public function safeUp()
    {
        if (!$this->hasTable(self::TABLE)) {
            return true;
        }
        $this->addColumnIfMissing(self::TABLE, 'inbound_source_types', $this->text()->null()->comment('Источники заявок воронки, JSON'));
        $this->addColumnIfMissing(self::TABLE, 'inbound_worker_ids', $this->text()->null()->comment('Кто разбирает: сотрудники, JSON'));
        $this->addColumnIfMissing(self::TABLE, 'inbound_department_ids', $this->text()->null()->comment('Кто разбирает: отделы, JSON'));
        $this->addColumnIfMissing(self::TABLE, 'inbound_mode', $this->string(16)->null()->comment('all | round; пусто — как в настройках CRM'));
    }

    public function safeDown()
    {
        $columns = ['inbound_mode', 'inbound_department_ids', 'inbound_worker_ids', 'inbound_source_types'];
        $set = ['or'];
        foreach ($columns as $column) {
            if ($this->hasColumn(self::TABLE, $column)) {
                $name = $this->db->quoteColumnName($column);
                $set[] = "$name IS NOT NULL AND TRIM($name) NOT IN ('', '[]', '{}', 'null')";
            }
        }
        $this->refuseRollbackWithData([
            'воронки со своими настройками очереди «Неразобранного»' => count($set) > 1 ? (new \yii\db\Query())->from(self::TABLE)->andWhere($set) : null,
        ]);
        foreach ($columns as $column) {
            $this->dropColumnIfExists(self::TABLE, $column);
        }
    }
}

<?php

use yii\db\Migration;

/**
 * П2б: sales pipelines (воронки) with their own stages and the history of
 * stage transitions of a deal (CmsLead).
 *
 * Only adds tables and empty columns. A site without pipelines keeps working
 * as before: its stages and deals have no pipeline and form one implicit
 * pipeline. Pipelines and the moving of existing deals are created by
 * `php yii cms/lead-pipeline/init` (a report by default, --apply to apply).
 */
class m261028_130000__create_table__cms_lead_pipeline extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $pipeline = '{{%cms_lead_pipeline}}';
        $this->createTable($pipeline, [
            'id'           => $this->primaryKey(),
            'created_by'   => $this->integer()->null(),
            'updated_by'   => $this->integer()->null(),
            'created_at'   => $this->integer()->null(),
            'updated_at'   => $this->integer()->null(),
            'cms_site_id'  => $this->integer()->notNull(),
            'name'         => $this->string(255)->notNull(),
            'code'         => $this->string(32)->null()->comment('Код воронки для скриптов: inbound, outbound'),
            'sort'         => $this->integer()->notNull()->defaultValue(100),
            'is_default'   => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Сюда попадают новые обращения'),
            'success_rule' => $this->string(16)->notNull()->defaultValue('manual')->comment('Критерий успеха: manual, order, payment'),
        ], $tableOptions);
        $this->createIndex('cms_lead_pipeline__site_sort', $pipeline, ['cms_site_id', 'sort']);
        $this->createIndex('cms_lead_pipeline__site_code', $pipeline, ['cms_site_id', 'code'], true);
        foreach (['created_by', 'updated_by'] as $column) {
            $this->addForeignKey('cms_lead_pipeline__'.$column, $pipeline, $column, '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        }
        $this->addForeignKey('cms_lead_pipeline__cms_site_id', $pipeline, 'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable($pipeline, 'Воронки продаж (сделки CmsLead)');

        // Stages belong to a pipeline; the same name may repeat in different pipelines.
        $stage = '{{%cms_lead_stage}}';
        $this->addColumn($stage, 'cms_lead_pipeline_id', $this->integer()->null()->after('cms_site_id')->comment('Воронка'));
        $this->addForeignKey('cms_lead_stage__cms_lead_pipeline_id', $stage, 'cms_lead_pipeline_id', $pipeline, 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('cms_lead_stage__site_pipeline_name', $stage, ['cms_site_id', 'cms_lead_pipeline_id', 'name'], true);
        $this->dropIndex('cms_lead_stage__site_name', $stage);

        $lead = '{{%cms_lead}}';
        $this->addColumn($lead, 'cms_lead_pipeline_id', $this->integer()->null()->after('status')->comment('Воронка'));
        $this->addColumn($lead, 'stage_changed_at', $this->integer()->null()->comment('Когда сделка попала на текущий этап'));
        $this->createIndex('cms_lead__pipeline_status', $lead, ['cms_lead_pipeline_id', 'status']);
        // Deleting a pipeline is refused while deals use it; SET NULL is the last safety net.
        $this->addForeignKey('cms_lead__cms_lead_pipeline_id', $lead, 'cms_lead_pipeline_id', $pipeline, 'id', 'SET NULL', 'CASCADE');

        $history = '{{%cms_lead_stage_history}}';
        $this->createTable($history, [
            'id'               => $this->primaryKey(),
            'cms_site_id'      => $this->integer()->notNull(),
            'cms_lead_id'      => $this->integer()->notNull(),
            'created_at'       => $this->integer()->notNull()->comment('Когда переход случился'),
            'created_by'       => $this->integer()->null()->comment('Кто перевёл; пусто — система'),
            'kind'             => $this->string(16)->notNull()->comment('create, take, stage, pipeline, success, reject, reopen, migrated'),
            'from_status'      => $this->string(32)->null(),
            'to_status'        => $this->string(32)->notNull(),
            'from_pipeline_id' => $this->integer()->null(),
            'to_pipeline_id'   => $this->integer()->null(),
            'from_stage_id'    => $this->integer()->null(),
            'to_stage_id'      => $this->integer()->null(),
            'from_stage_name'  => $this->string(255)->null()->comment('Название этапа на момент перехода'),
            'to_stage_name'    => $this->string(255)->null(),
            'is_migrated'      => $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('Состояние на дату миграции, а не настоящий переход'),
        ], $tableOptions);
        $this->createIndex('cms_lead_stage_history__lead', $history, ['cms_lead_id', 'id']);
        $this->createIndex('cms_lead_stage_history__site_time', $history, ['cms_site_id', 'created_at']);
        $this->createIndex('cms_lead_stage_history__pipeline_time', $history, ['to_pipeline_id', 'created_at']);
        // Stage and pipeline ids have no foreign keys: the history outlives a deleted stage (its name is kept).
        $this->addForeignKey('cms_lead_stage_history__cms_lead_id', $history, 'cms_lead_id', $lead, 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('cms_lead_stage_history__created_by', $history, 'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        $this->addForeignKey('cms_lead_stage_history__cms_site_id', $history, 'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable($history, 'История переходов сделки по этапам и воронкам');
    }

    public function safeDown()
    {
        // MySQL does not roll DDL back: check that the old unique (site, name) index can come back
        // before anything is dropped. Pipelines allow the same stage name twice on one site.
        $duplicates = (new \yii\db\Query())
            ->select(['cms_site_id', 'name', 'count' => 'COUNT(*)'])
            ->from('{{%cms_lead_stage}}')
            ->groupBy(['cms_site_id', 'name'])
            ->having(['>', 'COUNT(*)', 1])
            ->all($this->db);
        if ($duplicates) {
            $list = implode('; ', array_map(static fn(array $row) => 'site '.$row['cms_site_id'].' «'.$row['name'].'» ×'.$row['count'], $duplicates));
            throw new \yii\base\Exception('Откат невозможен: на сайте есть одноимённые этапы в разных воронках ('.$list.'). '
                .'Переименуйте или удалите лишние этапы, чтобы названия на сайте не повторялись, и повторите откат. Схема не изменена.');
        }

        $this->dropTable('{{%cms_lead_stage_history}}');
        $this->dropForeignKey('cms_lead__cms_lead_pipeline_id', '{{%cms_lead}}');
        $this->dropIndex('cms_lead__pipeline_status', '{{%cms_lead}}');
        $this->dropColumn('{{%cms_lead}}', 'stage_changed_at');
        $this->dropColumn('{{%cms_lead}}', 'cms_lead_pipeline_id');
        $this->createIndex('cms_lead_stage__site_name', '{{%cms_lead_stage}}', ['cms_site_id', 'name'], true);
        $this->dropIndex('cms_lead_stage__site_pipeline_name', '{{%cms_lead_stage}}');
        $this->dropForeignKey('cms_lead_stage__cms_lead_pipeline_id', '{{%cms_lead_stage}}');
        $this->dropColumn('{{%cms_lead_stage}}', 'cms_lead_pipeline_id');
        $this->dropTable('{{%cms_lead_pipeline}}');
    }
}

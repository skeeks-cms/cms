<?php

use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;

/**
 * П2а, owner's decision 28.10.2026 (ARCHITECTURE.md, П1/П2 пп. 10–11):
 * - the main responsible: exactly one link of a company or a client is
 *   `is_main` (always "full"); existing records — the link of the author of the
 *   record, else the earliest link; no links — no main;
 * - levels deal | edit | full: the former "sales" becomes "edit";
 * - «Доступ только по согласованию» of a company and of a client.
 */
class m261028_140000__alter_table__crm_main_responsible extends Migration
{
    use RepeatableMigrationTrait;

    public function safeUp()
    {
        $this->addColumnIfMissing('{{%cms_company2manager}}', 'is_main', $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->comment('Основной ответственный'));
        $this->addColumnIfMissing('{{%cms_user2manager}}', 'is_main', $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->comment('Основной ответственный'));
        $this->addColumnIfMissing('{{%cms_company}}', 'crm_access_by_approval', $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->comment('Доступ только по согласованию'));
        $this->addColumnIfMissing('{{%cms_user}}', 'crm_access_by_approval', $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->comment('Доступ только по согласованию'));

        foreach (['{{%cms_company2manager}}', '{{%cms_user2manager}}'] as $table) {
            $this->update($table, ['access_level' => 'edit'], ['access_level' => 'sales']);
            $this->alterColumn($table, 'access_level', $this->string(16)->notNull()->defaultValue('full')->comment('Уровень: deal, edit, full'));
        }
        $this->alterColumn('{{%cms_crm_access_request}}', 'level', $this->string(16)->null()->comment('Уровень: deal, edit, full'));
        $this->update('{{%cms_crm_access_request}}', ['level' => 'edit'], ['level' => 'sales']);

        $this->backfill('{{%cms_company2manager}}', 'cms_company_id', 'cms_user_id', '{{%cms_company}}');
        $this->backfill('{{%cms_user2manager}}', 'client_id', 'worker_id', '{{%cms_user}}');
    }

    /**
     * The author's link, else the earliest one, becomes main and "full" — only for
     * a record without a main yet, so a repeated run keeps the mains chosen since.
     */
    private function backfill(string $table, string $recordColumn, string $userColumn, string $recordTable): void
    {
        $db = $this->db;
        $rows = (new \yii\db\Query())->from(['m' => $table])
            ->leftJoin(['r' => $recordTable], 'r.id = m.'.$recordColumn)
            ->select(['m.id', 'record' => 'm.'.$recordColumn, 'user' => 'm.'.$userColumn, 'author' => 'r.created_by'])
            ->andWhere(['not in', 'm.'.$recordColumn, (new \yii\db\Query())->select($recordColumn)->from($table)->andWhere(['is_main' => 1])])
            ->orderBy(['m.id' => SORT_ASC])->all($db);
        $main = [];
        foreach ($rows as $row) {
            $record = (int)$row['record'];
            if (!isset($main[$record])) {
                $main[$record] = (int)$row['id'];
            }
            if ($row['author'] !== null && (int)$row['author'] === (int)$row['user']) {
                $main[$record] = (int)$row['id'];
            }
        }
        foreach (array_chunk(array_values($main), 500) as $ids) {
            $this->update($table, ['is_main' => 1, 'access_level' => 'full'], ['id' => $ids]);
        }
    }

    public function safeDown()
    {
        $this->refuseRollbackWithData([
            'основные ответственные компаний' => $this->hasColumn('{{%cms_company2manager}}', 'is_main')
                ? (new \yii\db\Query())->from('{{%cms_company2manager}}')->andWhere(['is_main' => 1]) : null,
            'основные ответственные клиентов' => $this->hasColumn('{{%cms_user2manager}}', 'is_main')
                ? (new \yii\db\Query())->from('{{%cms_user2manager}}')->andWhere(['is_main' => 1]) : null,
            'компании «по согласованию»' => $this->hasColumn('{{%cms_company}}', 'crm_access_by_approval')
                ? (new \yii\db\Query())->from('{{%cms_company}}')->andWhere(['crm_access_by_approval' => 1]) : null,
            'клиенты «по согласованию»' => $this->hasColumn('{{%cms_user}}', 'crm_access_by_approval')
                ? (new \yii\db\Query())->from('{{%cms_user}}')->andWhere(['crm_access_by_approval' => 1]) : null,
        ]);
        $this->update('{{%cms_crm_access_request}}', ['level' => 'sales'], ['level' => 'edit']);
        foreach (['{{%cms_company2manager}}', '{{%cms_user2manager}}'] as $table) {
            $this->update($table, ['access_level' => 'sales'], ['access_level' => ['edit', 'deal']]);
        }
        $this->dropColumnIfExists('{{%cms_user}}', 'crm_access_by_approval');
        $this->dropColumnIfExists('{{%cms_company}}', 'crm_access_by_approval');
        $this->dropColumnIfExists('{{%cms_user2manager}}', 'is_main');
        $this->dropColumnIfExists('{{%cms_company2manager}}', 'is_main');
    }
}

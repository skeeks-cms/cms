<?php

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
    public function safeUp()
    {
        $this->addColumn('{{%cms_company2manager}}', 'is_main', $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->comment('Основной ответственный'));
        $this->addColumn('{{%cms_user2manager}}', 'is_main', $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->comment('Основной ответственный'));
        $this->addColumn('{{%cms_company}}', 'crm_access_by_approval', $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->comment('Доступ только по согласованию'));
        $this->addColumn('{{%cms_user}}', 'crm_access_by_approval', $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)->comment('Доступ только по согласованию'));

        foreach (['{{%cms_company2manager}}', '{{%cms_user2manager}}'] as $table) {
            $this->update($table, ['access_level' => 'edit'], ['access_level' => 'sales']);
            $this->alterColumn($table, 'access_level', $this->string(16)->notNull()->defaultValue('full')->comment('Уровень: deal, edit, full'));
        }
        $this->alterColumn('{{%cms_crm_access_request}}', 'level', $this->string(16)->null()->comment('Уровень: deal, edit, full'));
        $this->update('{{%cms_crm_access_request}}', ['level' => 'edit'], ['level' => 'sales']);

        $this->backfill('{{%cms_company2manager}}', 'cms_company_id', 'cms_user_id', '{{%cms_company}}');
        $this->backfill('{{%cms_user2manager}}', 'client_id', 'worker_id', '{{%cms_user}}');
    }

    /** The author's link, else the earliest one, becomes main and "full". */
    private function backfill(string $table, string $recordColumn, string $userColumn, string $recordTable): void
    {
        $db = $this->db;
        $rows = (new \yii\db\Query())->from(['m' => $table])
            ->leftJoin(['r' => $recordTable], 'r.id = m.'.$recordColumn)
            ->select(['m.id', 'record' => 'm.'.$recordColumn, 'user' => 'm.'.$userColumn, 'author' => 'r.created_by'])
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
            'основные ответственные компаний' => (new \yii\db\Query())->from('{{%cms_company2manager}}')->andWhere(['is_main' => 1]),
            'основные ответственные клиентов' => (new \yii\db\Query())->from('{{%cms_user2manager}}')->andWhere(['is_main' => 1]),
            'компании «по согласованию»' => (new \yii\db\Query())->from('{{%cms_company}}')->andWhere(['crm_access_by_approval' => 1]),
            'клиенты «по согласованию»' => (new \yii\db\Query())->from('{{%cms_user}}')->andWhere(['crm_access_by_approval' => 1]),
        ]);
        $this->update('{{%cms_crm_access_request}}', ['level' => 'sales'], ['level' => 'edit']);
        foreach (['{{%cms_company2manager}}', '{{%cms_user2manager}}'] as $table) {
            $this->update($table, ['access_level' => 'sales'], ['access_level' => ['edit', 'deal']]);
        }
        $this->dropColumn('{{%cms_user}}', 'crm_access_by_approval');
        $this->dropColumn('{{%cms_company}}', 'crm_access_by_approval');
        $this->dropColumn('{{%cms_user2manager}}', 'is_main');
        $this->dropColumn('{{%cms_company2manager}}', 'is_main');
    }

    /**
     * MySQL does not roll DDL back, so the data this rollback would destroy is
     * checked before the first DDL (release audit F06, 29.10.2026): with any of
     * it the rollback is refused and the schema stays as it is.
     *
     * @param array $checks label => a query counting the rows that would be lost
     */
    private function refuseRollbackWithData(array $checks): void
    {
        $found = [];
        foreach ($checks as $label => $query) {
            $count = (int)$query->count('*', $this->db);
            if ($count > 0) {
                $found[] = $label.': '.$count;
            }
        }
        if ($found) {
            throw new \yii\base\Exception('Откат невозможен: он безвозвратно удалит данные CRM ('.implode('; ', $found).'). Схема не изменена. '
                .'Чтобы откатить: снимите дамп базы (mysqldump) и сохраните эти данные; если они действительно не нужны, удалите их вручную '
                .'и повторите откат. Вернуть их можно только из резервной копии базы.');
        }
    }
}

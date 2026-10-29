<?php

use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;

/**
 * «Входящие заявки» by turns (п. 13б, crm\InboundQueue): the last employee of
 * the queue who got a request, one row per site. The row is locked
 * (SELECT … FOR UPDATE) in the transaction that creates the deal, so two
 * requests at the same moment never go to the same employee by mistake.
 *
 * A repeated run after a stop restores every part on its own: the table, the
 * unique (site, queue), the foreign key, the comment (release audit F4,
 * 29.10.2026). Rows written while the unique was missing are merged first:
 * the most recently updated row of a queue stays.
 *
 * safeDown drops the table with its rows: they are only the cursor «кому ушла
 * последняя», not settings. Without it the next request of a queue goes to
 * its first employee and the turns go on from there; nothing users entered is
 * lost.
 */
class m261029_210000__create_table__cms_crm_inbound_rotation extends Migration
{
    use RepeatableMigrationTrait;

    private const TABLE = '{{%cms_crm_inbound_rotation}}';
    private const UNIQUE = 'cms_crm_inbound_rotation__site_queue';

    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null;
        $this->createTableIfMissing(self::TABLE, [
            'id' => $this->primaryKey(),
            'cms_site_id' => $this->integer()->notNull(),
            'queue' => $this->string(64)->notNull()->defaultValue('default')->comment('Очередь: default — настройки CRM'),
            'last_user_id' => $this->integer()->null()->comment('Кому ушла последняя заявка'),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);
        if (!$this->hasIndex(self::TABLE, self::UNIQUE)) {
            $this->mergeDuplicateQueues();
            $this->createIndex(self::UNIQUE, self::TABLE, ['cms_site_id', 'queue'], true);
        }
        $this->addForeignKeyIfMissing('cms_crm_inbound_rotation__cms_site_id', self::TABLE, 'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable(self::TABLE, 'Входящие заявки по кругу: кому ушла последняя');
    }

    public function safeDown()
    {
        $this->dropTableIfExists(self::TABLE);
    }

    /**
     * One row per site and queue: the most recently updated stays (then the latest id). The groups
     * come from the database, so queues equal by its collation are merged as the unique would see them.
     */
    private function mergeDuplicateQueues(): void
    {
        $groups = (new \yii\db\Query())->select(['cms_site_id', 'queue'])->from(self::TABLE)
            ->groupBy(['cms_site_id', 'queue'])->having(['>', 'COUNT(*)', 1])->all($this->db);
        $extra = [];
        foreach ($groups as $group) {
            $ids = (new \yii\db\Query())->select('id')->from(self::TABLE)
                ->andWhere(['cms_site_id' => $group['cms_site_id'], 'queue' => $group['queue']])
                ->orderBy(['updated_at' => SORT_DESC, 'id' => SORT_DESC])->column($this->db);
            $extra = array_merge($extra, array_map('intval', array_slice($ids, 1)));
        }
        if ($extra) {
            echo '    > merged '.count($extra)." duplicate queue row(s) before the unique index\n";
            $this->delete(self::TABLE, ['id' => $extra]);
        }
    }
}

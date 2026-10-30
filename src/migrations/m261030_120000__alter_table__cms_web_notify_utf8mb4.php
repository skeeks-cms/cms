<?php

use yii\db\Migration;

/**
 * Notifications carry names and texts of people (chat messages, letters):
 * emoji are 4-byte characters that the utf8 table refused, and the failed
 * notification rolled back the message it was written for.
 */
class m261030_120000__alter_table__cms_web_notify_utf8mb4 extends Migration
{
    public function safeUp()
    {
        if ($this->db->driverName !== 'mysql') {
            return true;
        }
        $collation = $this->db->createCommand("SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table",
            [':table' => $this->db->schema->getRawTableName('{{%cms_web_notify}}')])->queryScalar();
        if ($collation === false || stripos((string)$collation, 'utf8mb4') === 0) {
            return true;
        }

        $this->execute('ALTER TABLE {{%cms_web_notify}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        return true;
    }

    public function safeDown()
    {
        // Emoji written since cannot go back to utf8: the table stays utf8mb4.
        return true;
    }
}

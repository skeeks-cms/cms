<?php

use yii\db\Migration;

class m260609_180000_alter_cms_log_text_to_utf8mb4 extends Migration
{
    public function safeUp()
    {
        if ($this->db->driverName !== 'mysql') {
            return true;
        }

        $this->execute(
            'ALTER TABLE {{%cms_log}} '
            . 'MODIFY [[comment]] TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL, '
            . 'MODIFY [[data]] LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL'
        );

        return true;
    }

    public function safeDown()
    {
        if ($this->db->driverName !== 'mysql') {
            return true;
        }

        $this->execute(
            'ALTER TABLE {{%cms_log}} '
            . 'MODIFY [[comment]] TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL, '
            . 'MODIFY [[data]] LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL'
        );

        return true;
    }
}

<?php

use yii\db\Schema;
use yii\db\Migration;

class m150520_103210_cms_alter_user_data extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_user%}} DROP `role`;");
        $this->execute("ALTER TABLE {{%cms_user%}} DROP `status`;");

        $this->execute("ALTER TABLE {{%cms_user%}} DROP `count_subscribe`;");
        $this->execute("ALTER TABLE {{%cms_user%}} DROP `users_subscribers`;");
        $this->execute("ALTER TABLE {{%cms_user%}} DROP `count_comment`;");

        $this->execute("ALTER TABLE {{%cms_user%}} ADD `active` CHAR(1) NOT NULL DEFAULT 'Y' ;");
    }

    public function down()
    {
        echo "m150520_103210_cms_alter_user_data cannot be reverted.\n";
        return false;
    }
}

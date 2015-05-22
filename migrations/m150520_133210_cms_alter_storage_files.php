<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150520_133210_cms_alter_storage_files extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `status`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `status_adult`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `count_subscribe`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `users_subscribers`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `count_vote`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `result_vote`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `users_votes_up`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `users_votes_down`;");
        $this->execute("ALTER TABLE {{%cms_storage_file%}} DROP `count_comment`;");
    }

    public function down()
    {
        echo "m150520_133210_cms_alter_storage_files cannot be reverted.\n";
        return false;
    }
}

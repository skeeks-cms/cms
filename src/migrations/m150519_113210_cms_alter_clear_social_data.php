<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150519_113210_cms_alter_clear_social_data extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `count_comment`;");
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `count_subscribe`;");
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `users_subscribers`;");
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `count_vote`;");
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `result_vote`;");
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `users_votes_up`;");
        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `users_votes_down`;");

        $this->execute("ALTER TABLE {{%cms_publication%}} DROP `count_comment`;");
        $this->execute("ALTER TABLE {{%cms_publication%}} DROP `count_subscribe`;");
        $this->execute("ALTER TABLE {{%cms_publication%}} DROP `users_subscribers`;");
        $this->execute("ALTER TABLE {{%cms_publication%}} DROP `count_vote`;");
        $this->execute("ALTER TABLE {{%cms_publication%}} DROP `result_vote`;");
        $this->execute("ALTER TABLE {{%cms_publication%}} DROP `users_votes_up`;");
        $this->execute("ALTER TABLE {{%cms_publication%}} DROP `users_votes_down`;");
    }

    public function down()
    {
        echo "m150519_113210_cms_alter_clear_social_data cannot be reverted.\n";
        return false;
    }
}

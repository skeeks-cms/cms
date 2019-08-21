<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m191020_015515__alter_table__cms_user_email extends Migration
{

    public function safeUp()
    {
        $this->dropForeignKey("cms_user_email_user_id", "{{%cms_user_email}}");
        $this->dropIndex("user_id", "{{%cms_user_email}}");

        $this->renameColumn("{{%cms_user_email}}", "user_id", "cms_user_id");
        $this->createIndex("cms_user_id", "{{%cms_user_email}}", "cms_user_id");

        $this->addForeignKey(
            "cms_user_email__cms_user_id", "{{%cms_user_email}}",
            'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m190621_015515__alter_table__cms_site_domain cannot be reverted.\n";
        return false;
    }
}
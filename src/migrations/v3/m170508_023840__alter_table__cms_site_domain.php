<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170508_023840__alter_table__cms_site_domain extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey("cms_site_domain_site_code", "{{%cms_site_domain}}");
        $this->dropColumn("{{%cms_site_domain}}", "site_code");

        $this->dropForeignKey("cms_site_domain__cms_site_id", "{{%cms_site_domain}}");

        $this->alterColumn("{{%cms_site_domain}}", "cms_site_id", $this->integer()->notNull());

        $this->addForeignKey(
            'cms_site_domain__cms_site_id', "{{%cms_site_domain}}",
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m170508_023840__alter_table__cms_site_domain cannot be reverted.\n";
        return false;
    }
}
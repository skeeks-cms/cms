<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170508_013840__alter_table__cms_site_domain extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_site_domain}}", "cms_site_id", $this->integer());
        $this->createIndex('cms_site_id', "{{%cms_site_domain}}", ['cms_site_id']);

        $this->addForeignKey(
            'cms_site_domain__cms_site_id', "{{%cms_site_domain}}",
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->db->createCommand("UPDATE cms_site_domain JOIN cms_site ON cms_site.code = cms_site_domain.site_code SET cms_site_domain.cms_site_id = cms_site.id")->execute();

        //$this->alterColumn("{{%cms_site_domain}}", "cms_site_id", $this->integer()->notNull());

    }

    public function safeDown()
    {
        echo "m170508_013840__alter_table__cms_site_domain cannot be reverted.\n";
        return false;
    }
}
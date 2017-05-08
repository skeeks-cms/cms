<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170507_103840__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_tree}}", "cms_site_id", $this->integer());
        $this->createIndex('cms_site_id', "{{%cms_tree}}", ['cms_site_id']);

        $this->addForeignKey(
            'cms_tree__cms_site_id', "{{%cms_tree}}",
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->db->createCommand("UPDATE cms_tree JOIN cms_site ON cms_site.code = cms_tree.site_code SET cms_tree.cms_site_id = cms_site.id")->execute();


    }

    public function safeDown()
    {
        echo "m170507_103840__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}
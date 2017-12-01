<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170507_133840__alter_table__cms_component_settings extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_component_settings}}", "cms_site_id", $this->integer());
        $this->createIndex('cms_site_id', "{{%cms_component_settings}}", ['cms_site_id']);

        $this->addForeignKey(
            'cms_component_settings__cms_site_id', "{{%cms_component_settings}}",
            'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->db->createCommand("UPDATE cms_component_settings JOIN cms_site ON cms_site.code = cms_component_settings.site_code SET cms_component_settings.cms_site_id = cms_site.id")->execute();


    }

    public function safeDown()
    {
        echo "m170507_133840__alter_table__cms_component_settings cannot be reverted.\n";
        return false;
    }
}
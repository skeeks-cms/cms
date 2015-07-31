<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150731_213230_create_table__cms_event_email_template_site extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_event_email_template_site}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_event_email_template_site}}", [

            'event_email_template_id'                   => Schema::TYPE_INTEGER . ' NOT NULL',
            'site_code'                                 => "CHAR(15) NOT NULL",

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_event_email_template_site}} ADD UNIQUE (event_email_template_id, site_code);");
        $this->execute("ALTER TABLE {{%cms_event_email_template_site}} ADD INDEX(event_email_template_id);");
        $this->execute("ALTER TABLE {{%cms_event_email_template_site}} ADD INDEX(site_code);");

        $this->addForeignKey(
            'cms_event_email_template_site_site_code', "{{%cms_event_email_template_site}}",
            'site_code', '{{%cms_site}}', 'code', 'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            'cms_event_email_template_template_id', "{{%cms_event_email_template_site}}",
            'event_email_template_id', '{{%cms_event_email_template}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_event_email_template_site_site_code", "{{%cms_event_email_template_site}}");
        $this->dropForeignKey("cms_event_email_template_template_id", "{{%cms_event_email_template_site}}");

        $this->dropTable('{{%cms_event_email_template_site}}');
    }
}

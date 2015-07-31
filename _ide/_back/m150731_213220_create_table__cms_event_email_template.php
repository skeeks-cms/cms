<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150731_213220_create_table__cms_event_email_template extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_event_email_template}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_event_email_template}}", [

            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'event_name'            => Schema::TYPE_STRING . '(255) NOT NULL',

            'active'                => "CHAR(1) NOT NULL DEFAULT 'Y'",

            'email_from'            => Schema::TYPE_STRING . "(255) NOT NULL DEFAULT '#EMAIL_FROM#'",
            'email_to'              => Schema::TYPE_STRING . "(255) NOT NULL DEFAULT '#EMAIL_TO#'",
            'subject'               => Schema::TYPE_STRING . "(255) NULL",
            'message'               => "LONGTEXT NULL",
            'body_type'             => Schema::TYPE_STRING . "(6) NOT NULL DEFAULT 'html'",
            'bcc'                   => Schema::TYPE_TEXT . " NULL",
            'reply_to'              => Schema::TYPE_STRING . "(255) NULL",
            'cc'                    => Schema::TYPE_STRING . "(255) NULL",
            'in_reply_to'           => Schema::TYPE_STRING . "(255) NULL",

            'priority'              => Schema::TYPE_STRING . "(50) NULL",

            'field1_name'           => Schema::TYPE_STRING . "(50) NULL",
            'field1_value'          => Schema::TYPE_STRING . "(255) NULL",

            'field2_name'           => Schema::TYPE_STRING . "(50) NULL",
            'field2_value'          => Schema::TYPE_STRING . "(255) NULL",

            'message_php'           => "LONGTEXT NULL",
            'template'              => Schema::TYPE_STRING . "(255) NULL",
            'additional_field'      => Schema::TYPE_TEXT . " NULL",

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(created_by);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(event_name);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(active);");

        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(email_from);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(email_to);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(subject);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(body_type);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(bcc);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(reply_to);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(cc);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(in_reply_to);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(field1_name);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(field1_value);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(field2_name);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(field2_value);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(message_php);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(template);");
        $this->execute("ALTER TABLE {{%cms_event_email_template}} ADD INDEX(additional_field);");



        $this->addForeignKey(
            'cms_event_email_template_created_by', "{{%cms_event_email_template}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_event_email_template_updated_by', "{{%cms_event_email_template}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_event_email_template_event_name', "{{%cms_event_email_template}}",
            'event_name', '{{%cms_event}}', 'event_name', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_event_email_template_created_by", "{{%cms_event_email_template}}");
        $this->dropForeignKey("cms_event_email_template_updated_by", "{{%cms_event_email_template}}");
        $this->dropForeignKey("cms_event_email_template_event_name", "{{%cms_event_email_template}}");

        $this->dropTable('{{%cms_event_email_template}}');
    }
}

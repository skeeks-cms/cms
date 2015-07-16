<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150715_103220_create_table__cms_agent extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_agent}}", true);
        if ($tableExist)
        {
            return true;
        }


        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_agent}}", [

            'id'                    => Schema::TYPE_PK,

            'last_exec_at'          => Schema::TYPE_INTEGER . ' NULL',
            'next_exec_at'          => Schema::TYPE_INTEGER . ' NOT NULL',

            'name'                  => Schema::TYPE_TEXT . " NOT NULL",
            'description'           => Schema::TYPE_TEXT . " NULL",

            'agent_interval'        => Schema::TYPE_INTEGER . " NOT NULL DEFAULT '86400'",
            'priority'              => Schema::TYPE_INTEGER . " NOT NULL DEFAULT '100'",

            'active'                => "CHAR(1) NOT NULL DEFAULT 'Y'",
            'is_period'             => "CHAR(1) NOT NULL DEFAULT 'Y'",
            'is_running'            => "CHAR(1) NOT NULL DEFAULT 'N'",

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_agent}} ADD INDEX(last_exec_at);");
        $this->execute("ALTER TABLE {{%cms_agent}} ADD INDEX(next_exec_at);");
        $this->execute("ALTER TABLE {{%cms_agent}} ADD INDEX(agent_interval);");
        $this->execute("ALTER TABLE {{%cms_agent}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_agent}} ADD INDEX(active);");
        $this->execute("ALTER TABLE {{%cms_agent}} ADD INDEX(is_period);");
        $this->execute("ALTER TABLE {{%cms_agent}} ADD INDEX(is_running);");

        $this->execute("ALTER TABLE {{%cms_agent}} COMMENT = 'Агенты';");
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_agent}}');
    }
}

<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150730_213220_create_table__cms_event extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_event}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_event}}", [

            'id'                    => Schema::TYPE_PK,

            'event_name'            => Schema::TYPE_STRING . '(255) NOT NULL',
            'name'                  => Schema::TYPE_STRING . '(100) NULL',
            'description'           => Schema::TYPE_TEXT . ' NULL',

            'priority'              => Schema::TYPE_INTEGER. "(11) NOT NULL DEFAULT '150'",

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_event}} ADD UNIQUE (event_name);");
        $this->execute("ALTER TABLE {{%cms_event}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_event}} ADD INDEX(name);");
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_event}}');
    }
}

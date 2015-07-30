<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150730_103220_create_table__cms_session extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_session}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_session}}", [

            'id'                    => 'CHAR(64) NOT NULL PRIMARY KEY',

            'expire'                => Schema::TYPE_INTEGER,
            'data'                  => 'LONGTEXT NULL',
            //'data'                  => 'BLOB',

            'created_at'            => Schema::TYPE_INTEGER,
            'updated_at'            => Schema::TYPE_INTEGER,

            'ip'                    => Schema::TYPE_STRING . '(32) NULL',

            'data_server'           => Schema::TYPE_TEXT . ' NULL',
            'data_cookie'           => Schema::TYPE_TEXT . ' NULL',

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_session}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_session}} ADD INDEX(updated_at);");
        $this->execute("ALTER TABLE {{%cms_session}} ADD INDEX(expire);");
        $this->execute("ALTER TABLE {{%cms_session}} ADD INDEX(ip);");
/*
        $this->execute("CREATE TABLE {{%cms_session}}
          (
              id CHAR(64) NOT NULL PRIMARY KEY,
              expire INTEGER,
              data BLOB
          )");*/
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_session}}');
    }
}

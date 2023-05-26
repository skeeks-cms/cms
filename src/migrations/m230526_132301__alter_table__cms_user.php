<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m230526_132301__alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_user";

        $this->alterColumn($tableName, "password_hash", $this->string(255)->null());
        $this->alterColumn($tableName, "gender", $this->string(11)->null());
        $this->addColumn($tableName, "birthday_at", $this->integer()->null());

        $this->createIndex($tableName.'__birthday_at', $tableName, 'birthday_at');
        $this->createIndex($tableName.'__gender', $tableName, 'gender');

        $subQuery = $this->db->createCommand("
            UPDATE 
                `cms_user` as c
            SET 
                c.password_hash = null
        ")->execute();
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m160320_103837__alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        //m220319_103837__alter_table__cms_user
        if ($this->db->createCommand('SELECT * FROM migration WHERE version="m220319_103837__alter_table__cms_user"')->queryOne())
        {
            $this->db->createCommand()->delete('migration', 'version = "m220319_103837__alter_table__cms_user"')->execute();
            return true;
        }


        $this->dropIndex("city", "{{%cms_user}}");
        $this->dropIndex("address", "{{%cms_user}}");

        $this->dropColumn("{{%cms_user}}", "city");
        $this->dropColumn("{{%cms_user}}", "address");
        $this->dropColumn("{{%cms_user}}", "info");
        $this->dropColumn("{{%cms_user}}", "files");
        $this->dropColumn("{{%cms_user}}", "status_of_life");
    }

    public function safeDown()
    {
        echo "m220319_103837__alter_table__cms_user cannot be reverted.\n";
        return false;
    }
}
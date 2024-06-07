<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m240607_172718_alter_table__cms_callcheck_message extends Migration
{
    public function safeUp()
    {
        //$tableName = '{{%cms_session}}';
        $tableName = 'cms_callcheck_message';
        $this->alterColumn($tableName, "user_ip", $this->string(255));
    }

    public function safeDown()
    {
        $this->dropTable("{{%cms_callcheck_message}}");
    }
}

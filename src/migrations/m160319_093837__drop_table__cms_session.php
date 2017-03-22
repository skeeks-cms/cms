<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m160319_093837__drop_table__cms_session extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_session}}", true);
        if ($tableExist)
        {
            $this->dropTable('{{%cms_session}}');
        }

    }

    public function safeDown()
    {
        echo "m150707_114030_alter_table__big_text cannot be reverted.\n";
        return false;
    }
}
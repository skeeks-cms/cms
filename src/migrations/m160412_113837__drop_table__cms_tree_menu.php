<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\ArrayHelper;

class m160412_113837__drop_table__cms_tree_menu extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_tree_menu}}", true);
        if ($tableExist)
        {
            $this->dropTable("{{%cms_tree_menu}}");
        }

        return true;
    }

    public function safeDown()
    {
        echo "m160412_113837__drop_table__cms_tree_menu cannot be reverted.\n";
        return false;
    }
}
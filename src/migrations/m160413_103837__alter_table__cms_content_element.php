<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m160413_103837__alter_table__cms_content_element extends Migration
{
    public function safeUp()
    {
        $this->dropColumn("{{%cms_content_element}}", "files_depricated");
        $this->dropColumn("{{%cms_tree}}", "files_depricated");
    }

    public function safeDown()
    {
        echo "m160413_103837__alter_table__cms_content_element cannot be reverted.\n";
        return false;
    }
}
<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170507_113840__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey("cms_tree_site_code", "{{%cms_tree}}");
        $this->dropColumn("{{%cms_tree}}", "site_code");
    }

    public function safeDown()
    {
        echo "m170507_103840__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}